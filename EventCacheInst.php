<?php
/**
 * Main EventCache Class
 *
 */
class EventCacheInst {
    const LOG_EMERG = 0;
    const LOG_ALERT = 1;
    const LOG_CRIT = 2;
    const LOG_ERR = 3;
    const LOG_WARNING = 4;
    const LOG_NOTICE = 5;
    const LOG_INFO = 6;
    const LOG_DEBUG = 7;

    protected $_logLevels = array(
        self::LOG_EMERG => 'emerg',
        self::LOG_ALERT => 'alert',
        self::LOG_CRIT => 'crit',
        self::LOG_ERR => 'err',
        self::LOG_WARNING => 'warning',
        self::LOG_NOTICE => 'notice',
        self::LOG_INFO => 'info',
        self::LOG_DEBUG => 'debug'
    );

    public $log = array();

    protected $_config = array(
        'app' => 'base',
        'delimiter' => '-',
        'adapter' => 'EventCacheMemcachedAdapter',
        'logInKey' => false,
        'logInVar' => false,
        'logHits' => false,
        'logMicroseconds' => false,
        'logOnScreen' => false,
        'ttl' => 0,
        'flag' => MEMCACHE_COMPRESSED,
        'trackEvents' => false,
        'motherEvents' => array(),
        'disable' => false,
        'servers' => array(
            '127.0.0.1',
        ),
    );

    protected $_dir        = null;
    protected $_localCache = array();
    public    $Cache       = null;

    /**
     * Init
     *
     * @param <type> $config
     */
    public function  __construct ($config) {
        require_once dirname(__FILE__) . '/adapters/'. 'File.php';
        require_once dirname(__FILE__) . '/adapters/'. 'Memcached.php';

        $this->_config = array_merge($this->_config, $config);

        $this->setAdapter($this->_config['adapter']);
    }
    /**
     * Set options
     *
     * @param <type> $key
     * @param <type> $val
     * @return <type>
     */
    public function setOption ($key, $val = null) {
        if (is_array($key) && $val === null) {
            foreach ($key as $k => $v) {
                if (!$this->setOption($k, $v)) {
                    return false;
                }
            }
            return true;
        }

        if ($key === 'adapter' && $this->_config['adapter'] !== $val) {
            trigger_error('Changing adapter to '.$val);
            $this->setAdapter($val);
        }

        $this->_config[$key] = $val;


        return true;
    }

    public function setAdapter ($adapter) {
        $this->Cache   = new $adapter(array(
            'servers' => $this->_config['servers'],
        ));
    }

    /**
     * Save a key
     *
     * @param <type> $key
     * @param <type> $val
     * @param <type> $events
     * @param <type> $options
     * @return <type>
     */
    public function write ($key, $val, $events = array(), $options = array()) {
        if (!empty($this->_config['disable'])) {
            return false;
        }

        if (!isset($options['ttl'])) $options['ttl'] = $this->_config['ttl'];

        if (empty($options['lightning'])) {
            // In case of 'null' e.g.
            if (empty($events)) {
                $events = array();
            }

            // Mother events are attached to all keys
            if (!empty($this->_config['motherEvents'])) {
                $events = array_merge($events, (array)$this->_config['motherEvents']);
            }

            $this->register($key, $events);

            $this->debug('Set key: %s with val: %s', $key, $val);
        }

        $kKey = $this->cKey('key', $key);
        return $this->_set($kKey, $val, $options['ttl']);
    }
    /**
     * Get a key
     *
     * @param <type> $key
     * @return <type>
     */
    public function read ($key) {
        if (!empty($this->_config['disable'])) {
            return false;
        }

        $kKey = $this->cKey('key', $key);
        $val  = $this->_get($kKey);

        if (empty($options['lightning']) && empty($options['logHits'])) {
            if ($val === false) {
                $this->debug(sprintf("%s miss", $key));
            } else {
                $this->debug(sprintf("%s hit", $key));
            }
        }

        return $val;
    }
    /**
     * Adds array item
     *
     * @param <type> $listKey
     * @param <type> $key
     * @param <type> $val
     * @param <type> $ttl
     * @return <type>
     */
    public function listAdd ($listKey, $key = null, $val = null, $ttl = 0) {
        $memKey = $this->cKey('key', $listKey);
        $this->debug('Add item: %s with value: %s to list: %s. ttl: %s',
            $key,
            $val,
            $memKey,
            $ttl
        );
        return $this->_listAdd($memKey, $key, $val, $ttl);
    }
    /**
     * Delete a key
     *
     * @param <type> $key
     * @return <type>
     */
    public function delete ($key, $events = array()) {
        $kKey = $this->cKey('key', $key);
        $this->debug('Del key: %s', $kKey);

        if (empty($events)) {
            $events = array();
        }

        // Mother events are attached to all keys
        if (!empty($this->_config['motherEvents'])) {
            $events = array_merge($events, (array)$this->_config['motherEvents']);
        }

        $this->unregister($key, $events);

        return $this->_del($kKey);
    }
    /**
     * Clears All EventCache keys. (Only works with 'trackEvents' enabled)
     *
     * @param <type> $events
     * @return <type>
     */
    public function clear ($events = null) {
        if (!$this->_config['trackEvents']) {
            $this->err('You need to enable the slow "trackEvents" option for this');
            return false;
        }

        if ($events === null) {
            $events = $this->getEvents();
            if (!empty($events)) {
               $this->clear($events);
            }
        } else {
            $events = (array)$events;
            foreach($events as $eKey=>$event) {
                $cKeys = $this->getCKeys($event);
                $this->_del($cKeys);

                $this->_del($eKey);
            }
        }
    }

    /**
     * Kills everything in (mem) cache. Everything!
     *
     * @return <type>
     */
    public function flush () {
        return $this->_flush();
    }

    /**
     * DisAssociate keys with events
     *
     * @param <type> $key
     * @param <type> $events
     */
    public function unregister ($key, $events = array()) {
        return $this->register($key, $events, true);
    }

    /**
     * Associate keys with events (if you can't do it immediately with 'write')
     *
     * @param <type> $key
     * @param <type> $events
     * @param <type> $del
     */
    public function register ($key, $events = array(), $del = false) {
        if (empty($events)) {
            return false;
        }
        $events = (array)$events;
        if ($this->_config['trackEvents']) {
            // Slows down performance
            $etKey = $this->cKey('events', 'track');
            foreach($events as $event) {
                $eKey = $this->cKey('event', $event);
                if ($del) {
                    $this->_listDel($etKey, $eKey);
                } else {
                    $this->_listAdd($etKey, $eKey, $event);
                }
            }
        }

        foreach ($events as $event) {
            $eKey = $this->cKey('event', $event);
            $kKey = $this->cKey('key', $key);
            if ($del) {
                $this->_listDel($eKey, $kKey);
            } else {
                $this->_listAdd($eKey, $kKey, $key);
            }
        }
        return true;
    }

    /**
     * Call this function when your event has fired
     *
     * @param <type> $event
     */
    public function trigger ($event) {
        $cKeys = $this->getCKeys($event);
        return $this->_del($cKeys);
    }

    // Get events
    public function getEvents () {
        if (!$this->_config['trackEvents']) {
            $this->err('You need to enable the slow "trackEvents" option for this');
            return false;
        }

        $etKey  = $this->cKey('events', 'track');
        $events = $this->_get($etKey);
        return $events ? $events : array();
    }

    /**
     * Get event's keys
     *
     * @param <type> $event
     * @return <type>
     */
    public function getKeys ($event) {
        $eKey = $this->cKey('event', $event);
        $keys = $this->_get($eKey);
        return $keys ? $keys : array();
    }

    /**
     * Get internal keys
     *
     * @param <type> $event
     * @return <type>
     */
    public function getCKeys ($event) {
        $list = $this->getKeys($event);
        if (!is_array($list)) {
            return $list;
        }
        return array_keys($list);
    }


    /**
     * Returns a (mem)cache-ready key
     *
     * @param <type> $type
     * @param <type> $key
     * @return <type>
     */
    public function cKey ($type, $key) {
        // Local cache for cKeys
        static $cKeys;
        if (isset($cKeys[$type.','.$key])) {
            return $cKeys[$type.','.$key];
        }

        $cKey = $this->_config['app'] .
            $this->_config['delimiter'] .
            $type .
            $this->_config['delimiter'] .
            $this->sane($key);

        // http://groups.google.com/group/memcached/browse_thread/thread/4c9e28eb9e71620a
        // From: Brian Moon <br...@moonspot.net>
        // Date: Sun, 26 Apr 2009 22:59:29 -0500
        // Local: Mon, Apr 27 2009 5:59 am
        // Subject: Re: what is the maximum of memcached key size now ?
        //
        // pecl/memcache will handle your keys being too long.  I forget what it
        // does (md5 maybe) but it silently deals with it.

//        if (strlen($cKey) > 250) {
//            $cKey = md5($cKey);
//        }

        $cKeys[$type.','.$key] = $cKey;
        return $cKey;
    }
    /**
     * Sanitizes a string
     *
     * @param <type> $str
     * @return <type>
     */
    public function sane ($str) {
        if (is_array($str)) {
            foreach($str as $k => $v) {
                $str[$k] = $this->sane($v);
            }
            return $str;
        } else {
            // Local cache for sane results
            static $sanitation;
            if (isset($sanitation[$str])) {
                return $sanitation[$str];
            }

            $allowed = array(
                '0-9' => true,
                'a-z' => true,
                'A-Z' => true,
                '\-' => true,
                '\_' => true,
                '\.' => true,
                '\@' => true,
            );

            if (isset($allowed['\\'.$this->_config['delimiter']])) {
                unset($allowed['\\'.$this->_config['delimiter']]);
            }

            $sanitation[$str] = preg_replace('/[^'.join('', array_keys($allowed)).']/', '_', $str);

            return $sanitation[$str];
        }
    }

    /**
     * Log debug messages.
     *
     * @param <type> $str
     * @return <type>
     */
    public function debug ($str) {
        $args = func_get_args();
        return self::_log(self::LOG_DEBUG, array_shift($args), $args);
    }
    /**
     * Log error messages
     *
     * @param <type> $str
     * @return <type>
     */
    public function err ($str) {
        $args = func_get_args();
        return self::_log(self::LOG_ERR, array_shift($args), $args);
    }
    /**
     * Real function used by err, debug, etc, wrappers
     *
     * @param <type> $level
     * @param <type> $str
     * @param <type> $args
     * @return <type>
     */
    protected function _log ($level, $str, $args) {
        foreach ($args as $k=>$arg) {
            if (is_array($arg)) {
                $args[$k] = substr(var_export($arg, true), 0, 30);
            }
        }

        if ($this->_config['logMicroseconds']) {
            // From: http://www.php.net/manual/en/function.date.php#93891
            $t     = microtime(true);
            $micro = sprintf("%06d",($t - floor($t)) * 1000000);
            $d     = new DateTime( date('Y-m-d H:i:s.'.$micro,$t) );
            $date  = $d->format("Y-m-d H:i:s.u");
        } else {
            $date = date('M d H:i:s');
        }
        
        $log  = '';
        $log .= '';
        $log .= '['.$date.']';
        $log .= ' ';
        $log .= str_pad($this->_logLevels[$level], 8, ' ', STR_PAD_LEFT);
        $log .= ': ';
        $log .= vsprintf($str, $args);

        if (!empty($this->_config['logInKey'])) {
            return $this->_listAdd($this->cKey('key', $this->_config['logInKey']), null, $log);
        } elseif (!empty($this->_config['logOnScreen'])) {
            return $this->out($log);
        } elseif (!empty($this->_config['logInVar'])) {
            $this->log[] = $log;
        }
    }

    public function getLogs () {
        if (!empty($this->_config['logInKey'])) {
            $keys = $this->getKeys($this->_config['logInKey']);
            foreach ($keys as $cKey=>$key) {
                $vals[$key] = $this->read($key);
            }
            return $vals;
        } elseif (!empty($this->_config['logOnScreen'])) {
            // Need to read on screen
            return array('You need to read on screen');
        } elseif (!empty($this->_config['logInVar'])) {
            return $this->log;
        }
    }

    public function out ($str) {
        echo $str . "\n";
        return true;
    }

    /**
     * Add remove element from an array in cache
     *
     * @param <type> $memKey
     * @param <type> $cKey
     * @param <type> $ttl
     * @return <type>
     */
    protected function _listDel ($memKey, $cKey, $ttl = 0) {
        $list = $this->_get($memKey);
        if (is_array($list) && array_key_exists($cKey, $list)) {
            unset($list[$cKey]);
            return $this->_set($memKey, $list, $ttl);
        }
        // Didn't have to remove non-existing key
        return null;
    }

    /**
     * Add one element to an array in cache
     *
     * @param <type> $memKey
     * @param <type> $cKey
     * @param <type> $val
     * @param <type> $ttl
     * @return <type>
     */
    protected function _listAdd ($memKey, $cKey = null, $val = null, $ttl = 0) {
        if ($val === null) {
            $val = time();
        }
        $list = $this->_get($memKey);
        if (empty($list)) {
            $list = array();
        }
        if ($cKey === null) {
            $list[] = $val;
        } else {
            $list[$cKey] = $val;
        }
        return $this->_set($memKey, $list, $ttl);
    }

    /**
     * Add real key
     *
     * @param <type> $cKey
     * @param <type> $val
     * @param <type> $ttl
     * @return <type>
     */
    protected function _add ($cKey, $val, $ttl = 0) {
        return $this->Cache->add($cKey, $val, $ttl);
    }
    /**
     * Delete real key
     *
     * @param <type> $cKeys
     * @param <type> $ttl
     *
     * @return <type>
     */
    protected function _del ($cKeys, $ttl = 0) {
        if (empty($cKeys)) {
            return null;
        }
        if (is_array($cKeys)) {
            $errors = array();
            foreach($cKeys as $cKey) {
                if (!$this->_del($cKey)) {
                    $errors[] = $cKey;
                }
            }

            if (count($errors) === count($cKeys)) {
                return false;
            }

            return count($cKeys) - count($errors);
        }

        unset($this->_localCache[$cKeys]);
        if (!$this->Cache->delete($cKeys, $ttl)) {
            #trigger_error('Cant delete '.$cKeys, E_USER_NOTICE);
            return false;
        }

        return true;
    }
    /**
     * Set real key
     *
     * @param <type> $cKey
     * @param <type> $val
     * @param <type> $ttl
     * @return <type>
     */
    protected function _set ($cKey, $val, $ttl = 0) {
        // Set local cache
        if (@$this->_localCache[$cKey] === $val) {
            return null;
        }
        $this->_localCache[$cKey] = $val;

        return $this->Cache->set($cKey, $val, $ttl);
    }
    /**
     * Get real key
     *
     * @param <type> $cKey
     * @return <type>
     */
    protected function _get ($cKey) {
        // Try local cache first
        if (isset($this->_localCache[$cKey])) {
            return $this->_localCache[$cKey];
        }
        
        return $this->Cache->get($cKey);
    }
    /**
     * Flush!
     *
     * @return <type>
     */
    protected function _flush () {
        $this->_localCache = array();
        return $this->Cache->flush();
    }
}