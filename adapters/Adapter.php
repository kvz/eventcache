<?php
class EventCacheAdapter {
	protected $_config = array(
	);


	public function __construct ($options) {
		$this->_config = array_merge($this->_config, $options);
	}
    public function init () {
        return true;
	}


	public function get ($key) {
        trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}
	public function set ($key, $val, $ttl = 0) {
		trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}
	public function delete ($key) {
		trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}
	public function flush () {
        trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}

    
	public function increment ($key, $value = 1) {
        trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}
	public function decrement ($key, $value = 1) {
		trigger_error(sprintf('%s::%s needs to be implemented', __CLASS__, __FUNCTION__), E_USER_ERROR);
	}


    /**
     * Add remove element from an array in cache
     *
     * @param <type> $ulistKey
     * @param <type> $safeKey
     * @param <type> $ttl
     *
     * @return <type>
     */
    public function ulistDelete ($ulistKey, $safeKey, $ttl = 0) {
        $ulist = $this->get($ulistKey);
        if (is_array($ulist) && array_key_exists($safeKey, $ulist)) {
            unset($ulist[$safeKey]);
            return $this->set($ulistKey, $ulist, $ttl);
        }
        // Didn't have to remove non-existing key
        return null;
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
    public function ulistSet ($ulistKey, $safeKey = null, $val = null, $ttl = 0) {
        $ulist = $this->get($ulistKey);
        if (empty($ulist)) {
            $ulist = array();
        }
        if ($safeKey === null) {
            $ulist[] = $val;
        } else {
            $ulist[$safeKey] = $val;
        }
        return $this->set($ulistKey, $ulist, $ttl);
    }
}