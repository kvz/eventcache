<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterMemcached extends EventCacheAdapter {
	protected $Memcached;

	protected $_config = array(
		'servers' => array(
			array('127.0.0.1', 11211),
		),
	);

	public function init () {
		if (!class_exists('Memcached')) {
			return sprintf(
				'Memcached not installed'
			);
		}

		$this->Memcached = new Memcached();
		$this->Memcached->addServers($this->_config['servers']);

		return true;
	}


	public function get ($key) {
		return @$this->Memcached->get($key);
	}
	public function set ($key, $val, $ttl = 0) {
		return @$this->Memcached->set($key, $val, $ttl);
	}
	public function delete ($key) {
		return @$this->Memcached->delete($key, $ttl);
	}
	public function flush () {
		return $this->Memcached->flush();
	}


	public function increment ($key, $val = 1) {
		return @$this->Memcached->increment($key, $val);
	}
	public function decrement ($key, $val = 1) {
		return @$this->Memcached->decrement($key, $val);
	}
}
