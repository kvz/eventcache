<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterMemcache extends EventCacheAdapter {
	protected $Memcache;

	protected $_config = array(
		'servers' => array(
			'127.0.0.1',
		),
	);

	public function init () {
		if (!class_exists('Memcache')) {
			return sprintf(
				'Memcache not installed'
			);
		}

		$this->Memcache = new Memcache();
		foreach ($this->_config['servers'] as $server) {
			call_user_func(array($this->Memcache, 'addServer'), $server);
		}

		return true;
	}


	public function get ($key) {
		return @$this->Memcache->get($key);
	}
	public function set ($key, $val, $ttl = 0, $flag = 0) {
		return @$this->Memcache->set($key, $val, $flag, $ttl);
	}
	public function delete ($key) {
		return @$this->Memcache->delete($key, $ttl);
	}
	public function flush () {
		return $this->Memcache->flush();
	}


	public function increment ($key, $val = 1) {
		return @$this->Memcache->increment($key, $val);
	}
	public function decrement ($key, $val = 1) {
		return @$this->Memcache->decrement($key, $val);
	}
}