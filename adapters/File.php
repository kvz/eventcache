<?php
/**
 * File adapter to EventCache
 *
 */
class EventCacheFileAdapter {
	public $cache;

    protected $_config = array();

	public function __construct($options) {
		$this->_config =  $options + $this->_config;

        if (!isset($this->_config['dir'])) $this->_config['dir'] = '/tmp/EventCache';
        if (!is_dir($this->_config['dir']) && !mkdir($this->_config['dir'], 0777, true)) {
            trigger_error('Unable to find and create directory: '.$this->_config['dir'], E_USER_ERROR);
        }
	}

	public function get($key) {
		return $this->_read($key);
	}
	public function flush() {
        return $this->_delete('*');
	}
	public function set($key, $val, $ttl = 0, $flag = 0) {
		return $this->_write($key, $val);
	}

	public function add($key, $val, $ttl = 0) {
        return $this->_write($key, $val);
	}
	public function delete($key, $ttl = 0) {
		return $this->_delete($key);
	}
	public function increment($key, $value = 1) {
        if (is_numeric(($val = $this->_read($value)))) {
            $value = $val++;
        }
        return $this->_write($key, $value);
	}
	public function decrement($key, $value = 1) {
        if (is_numeric(($val = $this->_read($value)))) {
            $value = $val--;
        }
        return $this->_write($key, $value);
	}

    protected function _read($key) {
        $path = $this->_keypath($key);

        if (false === ($value = @file_get_contents($path))) {
            return false;
        }

        $value = unserialize($value);
        return $value;
    }
    protected function _write($key, $value) {
        $value = serialize($value);
        $path  = $this->_keypath($key);

        if (!file_put_contents($path, $value)) {
            trigger_error('Unable to write to '.$path, E_USER_WARNING);
            return false;
        }

        return true;
    }
    protected function _delete($key) {
        if ($key !== '*') {
            $key = $this->_safekey($key);
        }

        foreach (glob($this->_config['dir'].'/'.$key.'.cache') as $file) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    protected function _safekey($key) {
        #return sha1($key);
        return $key;
    }
    protected function _keypath($key) {
        return $this->_config['dir'].'/'.$this->_safekey($key).'.cache';
    }
}
?>