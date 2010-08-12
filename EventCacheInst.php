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
        'adapter' => 'EventCacheAdapterApc',
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
        require_once dirname(__FILE__) . '/adapters/'. 'Apc.php';
        require_once dirname(__FILE__) . '/adapters/'. 'File.php';
        require_once dirname(__FILE__) . '/adapters/'. 'Memcached.php';
        require_once dirname(__FILE__) . '/adapters/'. 'Redis.php';

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
            $this->setAdapter($val);
        }

        $this->_config[$key] = $val;

        return true;
    }

    public function setAdapter ($adapter) {
        $this->Cache = new $adapter(array(
            'servers' => $this->_config['servers'],
        ));

        if (true !== ($res = $this->Cache->init())) {
            trigger_error(sprintf(
                'Unable to use the %s adapter because: %s',
                $adapter,
                $res
            ), E_USER_ERROR);
            return false;
        }

        return true;
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
                $events = array_merge(
                    $events,
                    (array)$this->_config['motherEvents']
                );
            }

            $this->register($key, $events);

            $this->debug('Set key: %s with val: %s', $key, $val);
        }

        $kKey = $this->safeKey('key', $key);
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

        $kKey = $this->safeKey('key', $key);
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
     * @param <type> $ulistKey
     * @param <type> $key
     * @param <type> $val
     * @param <type> $ttl
     * 
     * @return <type>
     */
    public function ulistSet ($ulistKey, $key = null, $val = null, $ttl = 0) {
        $safeUlistKey = $this->safeKey('key', $ulistKey);
        $this->debug('Add item: %s with value: %s to ulist: %s. ttl: %s',
            $key,
            $val,
            $safeUlistKey,
            $ttl
        );
        return $this->_ulistSet($safeUlistKey, $key, $val, $ttl);
    }

    public function getUlist ($ulistKey) {
        $safeUlistKey = $this->safeKey('key', $ulistKey);
        return $this->_getUlist($safeUlistKey);
    }
    
    public function getList ($listKey) {
        $safeListKey = $this->safeKey('key', $listKey);
        return $this->_getList($safeListKey);
    }
    public function listAdd ($listKey, $item) {
        $safeListKey = $this->safeKey('key', $listKey);
        return $this->_listAdd($safeListKey, $item);
    }
    
    /**
     * Delete a key
     *
     * @param <type> $key
     * @return <type>
     */
    public function delete ($key, $events = array()) {
        $kKey = $this->safeKey('key', $key);
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
            $this->err('You need to enable the slower "trackEvents" option for this');
            return false;
        }

        if ($events === null) {
            $events = $this->getTracksEvents();
            if (!empty($events)) {
               return $this->clear($events);
            }
            return null;
        }

        $events = (array)$events;
        $safeTrackKey  = $this->safeKey('events', 'track');
        foreach ($events as $eKey => $event) {
            $safeKeys = $this->_getEventsInternalKeys($event);

            // Delete Event's keys
            $this->_del($safeKeys);

            // Delete Event
            $this->_del($eKey);

            // Delete event from tracked
            $this->_ulistDel($safeTrackKey, $eKey);
        }
    }

    /**
     * Kills everything in (mem) cache. Everything!! 
     *
     * @return <type>
     */
    public function flush () {
        if ($this->Cache->isFlushSafe()) {
            return $this->_flush();
        }
        return null;
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
     *
     * @return boolean
     */
    public function register ($key, $events = array(), $del = false) {
        if (empty($events)) {
            return false;
        }
        $events = (array)$events;
        if ($this->_config['trackEvents']) {
            // Slows down performance
            $safeTrackKey = $this->safeKey('events', 'track');
            foreach ($events as $event) {
                $safeEventKey = $this->safeKey('event', $event);
                if ($del) {
                    $this->_ulistDel($safeTrackKey, $safeEventKey);
                } else {
                    $this->_ulistSet($safeTrackKey, $safeEventKey, $event);
                }
            }
        }

        foreach ($events as $event) {
            $safeEventKey = $this->safeKey('event', $event);
            $safeItemKey  = $this->safeKey('key', $key);
            if ($del) {
                $this->_ulistDel($safeEventKey, $safeItemKey);
            } else {
                $this->_ulistSet($safeEventKey, $safeItemKey, $key);
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
        $safeKeys = $this->_getEventsInternalKeys($event);
        return $this->_del($safeKeys);
    }

    public function getAdapter () {
        return get_class($this->Cache);
    }

    public function getTracksEvents () {
        if (!$this->_config['trackEvents']) {
            $this->err('You need to enable the slow "trackEvents" option for this');
            return false;
        }

        $safeTrackKey = $this->safeKey('events', 'track');
        $events       = $this->_getUlist($safeTrackKey);
        return $events ? $events : array();
    }

    /**
     * Get event's keys
     *
     * @param <type> $event
     * @return <type>
     */
    public function getEventsKeys ($event) {
        $safeEventKey = $this->safeKey('event', $event);
        $keys         = $this->_getUlist($safeEventKey);
        return $keys ? $keys : array();
    }

    /**
     * Get internal keys
     *
     * @param <type> $event
     * @return <type>
     */
    protected function _getEventsInternalKeys ($event) {
        $ulist = $this->getEventsKeys($event);
        if (!is_array($ulist)) {
            return $ulist;
        }
        return array_keys($ulist);
    }


    /**
     * Returns a (mem)cache-ready key
     *
     * @param <type> $type
     * @param <type> $key
     * @return <type>
     */
    public function safeKey ($type, $key) {
        // Local cache for cKeys
        static $safeKeys;
        if (isset($safeKeys[$type.','.$key])) {
            return $safeKeys[$type.','.$key];
        }

        $safeKey = $this->_config['app'] .
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

//        if (strlen($safeKey) > 250) {
//            $safeKey = md5($safeKey);
//        }

        $safeKeys[$type.','.$key] = $safeKey;
        return $safeKey;
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
        self::_log(self::LOG_ERR, array_shift($args), $args);
        return false;
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
        foreach ($args as $k => $arg) {
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
            return $this->listAdd($this->_config['logInKey'], $log);
        } elseif (!empty($this->_config['logOnScreen'])) {
            return $this->out($log);
        } elseif (!empty($this->_config['logInVar'])) {
            $this->log[] = $log;
        }
    }

    public function getLogs () {
        if (!empty($this->_config['logInKey'])) {
            $logs = $this->getList($this->_config['logInKey']);
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
     * Deletes item from a unique list (associative array)
     *
     * @param <type> $ulistKey
     * @param <type> $safeKey
     *
     * @return <type>
     */
    protected function _ulistDel ($ulistKey, $safeKey) {
        return $this->Cache->ulistDelete($ulistKey, $safeKey);
    }

    /**
     * Adds item to a unique list (associative array)
     *
     * @param <type> $ulistKey
     * @param <type> $safeKey  leave null to add item at the end of numerically indexed array
     * @param <type> $val
     * @param <type> $ttl
     * 
     * @return mixed boolean or null
     */
    protected function _ulistSet ($ulistKey, $safeKey = null, $val = null, $ttl = 0) {
        return $this->Cache->ulistSet($ulistKey, $safeKey, $val, $ttl);
    }

    /**
     * Retries entire unique list (associative array)
     *
     * @param <type> $ulistKey
     * @param <type> $safeKey  leave null to add item at the end of numerically indexed array
     * @param <type> $val
     * @param <type> $ttl
     *
     * @return mixed boolean or null
     */
    protected function _getUlist ($ulistKey) {
        return $this->Cache->getUlist($ulistKey);
    }

    protected function _listAdd ($listKey, $val = null) {
        return $this->Cache->listAdd($listKey, $val);
    }
    protected function _getList ($listKey) {
        return $this->Cache->getList($listKey);
    }

    /**
     * Delete real key
     *
     * @param <type> $safeKeys
     * @param <type> $ttl
     *
     * @return <type>
     */
    protected function _del ($safeKeys) {
        if (empty($safeKeys)) {
            return null;
        }
        if (is_array($safeKeys)) {
            $errors = array();
            foreach($safeKeys as $safeKey) {
                if (!$this->_del($safeKey)) {
                    $errors[] = $safeKey;
                }
            }

            if (count($errors) === count($safeKeys)) {
                return false;
            }

            return count($safeKeys) - count($errors);
        }

        unset($this->_localCache[$safeKeys]);
        if (!$this->Cache->delete($safeKeys)) {
            return false;
        }

        return true;
    }
    /**
     * Set real key
     *
     * @param <type> $safeKey
     * @param <type> $val
     * @param <type> $ttl
     * @return <type>
     */
    protected function _set ($safeKey, $val, $ttl = 0) {
        // Set local cache
        if (@$this->_localCache[$safeKey] === $val) {
            return null;
        }
        $this->_localCache[$safeKey] = $val;

        return $this->Cache->set($safeKey, $val, $ttl);
    }
    /**
     * Get real key
     *
     * @param <type> $safeKey
     * @return <type>
     */
    protected function _get ($safeKey) {
        // Try local cache first
        if (isset($this->_localCache[$safeKey])) {
            return $this->_localCache[$safeKey];
        }
        
        return $this->Cache->get($safeKey);
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