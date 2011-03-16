<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterFile extends EventCacheAdapter {
	public function init () {
		if (!isset($this->_config['dir'])) $this->_config['dir'] = '/tmp/EventCache';
		if (!is_dir($this->_config['dir']) && !mkdir($this->_config['dir'], 0777, true)) {
			return sprintf(
				'Unable to find and create EventCache File directory: %s',
				$this->_config['dir']
			);
		}
		return true;
	}


	public function get ($key) {
		return $this->_read($key);
	}
	public function set ($key, $val, $ttl = 0, $flag = 0) {
		return $this->_write($key, $val);
	}
	public function delete ($key) {
		return $this->_delete($key);
	}
	public function flush () {
		return $this->_delete('*');
	}


	protected function _read ($key) {
		$path = $this->_keypath($key);

		if (false === ($value = @file_get_contents($path))) {
			return false;
		}

		$value = unserialize($value);
		return $value;
	}
	protected function _write ($key, $val) {
		$val = serialize($val);
		$path  = $this->_keypath($key);

		if (!@file_put_contents($path, $val)) {
			trigger_error('Unable to write to ' . $path, E_USER_WARNING);
			return false;
		}

		return true;
	}
	protected function _delete ($key) {
		$path = $this->_keypath($key);
		foreach (glob($path) as $file) {
			if (!unlink($file)) {
				return false;
			}
		}

		return true;
	}

	protected function _safekey ($key) {
		if ($key === '*') {
			return $key;
		}
		#return sha1($key);
		return $key;
	}
	protected function _keypath ($key) {
		return $this->_config['dir'] . '/' . $this->_safekey($key) . '.cache';
	}
}