<?php
class EventCacheMemcachedAdapter {
	protected $_config = array(
		'servers' => null,
	);

	public $Memcache;

	public function __construct($options) {
		$this->_config =  $options + $this->_config;

		$this->Memcache = new Memcache();
		foreach ($this->_config['servers'] as $server) {
			call_user_func_array(array($this->Memcache, 'addServer'), $server);
		}
	}

	public function get($key) {
		return @$this->Memcache->get($key);
	}

	public function flush() {
		return @$this->Memcache->flush();
	}

	public function set($key, $val, $ttl = 0, $flag = 0) {
		return @$this->Memcache->set($key, $val, $flag, $ttl);
	}

	public function add($key, $val, $ttl = 0) {
		return @$this->Memcache->add($key, $val, 0, $ttl);
	}

	public function delete($key, $ttl = 0) {
		return @$this->Memcache->delete($key, $ttl);
	}

	public function increment($key, $value = 1) {
		return @$this->Memcache->increment($key, $value);
	}

	public function decrement($key, $value = 1) {
		return @$this->Memcache->decrement($key, $value);
	}
}
?>