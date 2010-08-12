<?php
class EventCacheMemcachedAdapter {
	protected $_config = array(
		'servers' => null,
	);

	public $Memcache;

	public function __construct ($options) {
		$this->_config = array_merge($this->_config, $options);
	}
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
	public function add ($key, $val, $ttl = 0) {
		return @$this->Memcache->add($key, $val, 0, $ttl);
	}
	public function delete ($key, $ttl = 0) {
		return @$this->Memcache->delete($key, $ttl);
	}
	public function flush () {
		return $this->Memcache->flush();
	}

	public function increment ($key, $value = 1) {
		return @$this->Memcache->increment($key, $value);
	}
	public function decrement ($key, $value = 1) {
		return @$this->Memcache->decrement($key, $value);
	}
}