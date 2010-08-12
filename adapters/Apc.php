<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterApc extends EventCacheAdapter {
	public function init () {
        if (!function_exists('apc_cache_info')) {
            return sprintf(
                'APC not installed'
            );
        }

        if (!ini_get('apc.enabled')) {
            return sprintf(
                'APC not enabled'
            );
        }

        return true;
	}
    
	public function get ($key) {
		return apc_fetch($key);
	}
	public function set ($key, $val, $ttl = 999) {
        return apc_store($key, $val, $ttl);

//        if (($got = apc_fetch($key)) !== $val) {
//            trigger_error(sprintf(
//                'Unable to store %s with value: %s and TTL: %s in APC. Resulted in "%s"',
//                $key,
//                json_encode($val),
//                $ttl,
//                var_export($got, true)
//            ), E_USER_WARNING);
//        }
//
        return true;
	}
	public function delete ($key) {
		return apc_delete($key);
	}
	public function flush () {
        return apc_clear_cache('user');
	}


	public function increment ($key, $val = 1) {
		return apc_inc($key, $val);
	}
	public function decrement ($key, $val = 1) {
		return apc_dec($key, $val);
	}
}