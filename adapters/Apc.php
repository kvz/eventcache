<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterApc extends EventCacheAdapter {
	public function init () {
        if (!function_exists('apc_cache_info')) {
            return sprintf(
                'APC not installed'
            );
        }
        return true;
	}

    
	public function get ($key) {
		return @apc_fetch($key);
	}
	public function set ($key, $val, $ttl = 0) {
		return @apc_store($key, $val, $ttl);
	}
	public function add ($key, $val, $ttl = 0) {
		return @apc_store($key, $val, $ttl);
	}
	public function delete ($key) {
		return @apc_delete($key);
	}
	public function flush () {
        return @apc_clear_cache('user');
	}


	public function increment ($key, $value = 1) {
        $this->set($key, (float)$this->get($key) + $value);
	}
	public function decrement ($key, $value = 1) {
		$this->set($key, (float)$this->get($key) - $value);
	}
}