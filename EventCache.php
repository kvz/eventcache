<?php
/**
 * Easy static access to EventCacheInst
 *
 */
class EventCache {
    static public $instanceClass = 'EventCacheInst';
    static public $config = array();
    static public $Instance = null;

    static public function getInstance() {
        if (EventCache::$Instance === null) {
            require_once dirname(__FILE__) . '/'. 'EventCacheInst.php';
            EventCache::$Instance = new EventCache::$instanceClass(EventCache::$config);
        }

        return EventCache::$Instance;
    }
    static public function setOption($key, $val = null) {
        if (is_array($key)) {
            EventCache::$config = $key;
        } else {
            EventCache::$config[$key] = $val;
        }

        $_this = EventCache::getInstance();
        return $_this->setOption($key, $val);
    }
    static public function read($key) {
        $_this = EventCache::getInstance();
        return $_this->read($key);
    }
    static public function getKeys ($event) {
        $_this = EventCache::getInstance();
        return $_this->getKeys($event);
    }
    static public function getEvents () {
        $_this = EventCache::getInstance();
        return $_this->getEvents();
    }
    static public function getAdapter () {
        $_this = EventCache::getInstance();
        return $_this->getAdapter();
    }

    /*
    // PHP 5.3
    static public function  __callStatic($name, $arguments) {
        $_this = EventCache::getInstance();
        $call = array($_this, $name);
        if (is_callable($call)) {
            return call_user_func_array($call, $arguments);
        }

        return false;
    }
    */

    static public function squashArrayTo1Dim ($array) {
        foreach($array as $k=>$v) {
            if (is_array($v)) {
                $array[$k] = crc32(json_encode($v));
            }
        }
        return $array;
    }

    static public function magicKey ($scope, $method, $args = array(), $events = array(), $options = array()) {
        $_this = EventCache::getInstance();
        $dlm   = '.';
        $dls   = '@';

        $keyp = array();
        if (is_object($scope)) {
            if (!empty($scope->name)) {
                $keyp[] = $scope->name;
            } else {
                $keyp[] = get_class($scope);
            }
        } elseif (is_string($scope)) {
            $keyp[] = $scope;
        }
        $keyp[] = $method;
        // Default to 'unique' option if not specified
        if (is_string($options)) {
            $options = array(
                'unique' => $options,
            );
        }
        if (!empty($options['unique'])) {
            $keyp = array_merge($keyp, self::squashArrayTo1Dim((array)$options['unique']));
        }

        $args = self::squashArrayTo1Dim($args);

        $keyp[] = join($dls, $args);

        $keyp = $_this->sane($keyp);
        $key  = join($dlm, $keyp);
        return $key;
    }

    static protected function _execute ($callback, $args) {
        // Can we Execute Callback?
        if (!is_callable($callback)) {
            trigger_error('Can\'t call '.join('::', $callback).' is it public?', E_USER_ERROR);
            return false;
        }
        return call_user_func_array($callback, $args);
    }

    static public function magic ($scope, $method, $args = array(), $events = array(), $options = array()) {
        if (empty($args)) $args = array();
        if (empty($events)) $events = array();
        if (empty($options)) $options = array();

        $key      = self::magicKey($scope, $method, $args, $events, $options);
        $callback = array($scope, '_'.$method);
        #$debug    = $method === '_getLookupList';
        $debug    = false;

        if (!empty($options['disable'])) {
            $val = self::_execute($callback, $args);
        } else {
            if (false === ($val = self::read($key))) {
                $val = self::_execute($callback, $args);
                self::write($key, $val, $events, $options);
                $debug && pr(compact('key', 'method', 'options', 'events', 'val'));
            }
        }

        // For testing purposes
        if (!empty($options['keypair'])) {
            return array($key, $val);
        }

        return $val;
    }

    static public function write($key, $val, $events = array(), $options = array()) {
        $_this = EventCache::getInstance();
        return $_this->write($key, $val, $events, $options);
    }

    static public function trigger($event) {
        $_this = EventCache::getInstance();
        return $_this->trigger($event);
    }
    static public function flush() {
        $_this = EventCache::getInstance();
        return $_this->flush();
    }

    static public function getLogs() {
        $_this = EventCache::getInstance();
        return $_this->getLogs();
    }
}