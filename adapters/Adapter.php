<?php
class EventCacheAdapter {
	protected $_config = array(
	);

    protected $_flushSafe = true;

	public function __construct ($options) {
		$this->_config = array_merge($this->_config, $options);
	}
    public function init () {
        return true;
	}
    public function isFlushSafe () {
        return $this->_flushSafe;
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
        if ($this->set($key, ($new = (float)$this->get($key) + $value))) {
            return $new;
        }
        return false;
	}
	public function decrement ($key, $value = 1) {
		if ($this->set($key, ($new = (float)$this->get($key) - $value))) {
            return $new;
        }
        return false;
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
    public function ulistDelete ($ulistKey, $safeKey) {
        $ulist = $this->get($ulistKey);
        if (is_array($ulist) && array_key_exists($safeKey, $ulist)) {
            unset($ulist[$safeKey]);
            return $this->set($ulistKey, $ulist);
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
    public function ulistSet ($ulistKey, $safeKey, $val) {
        $ulist = $this->get($ulistKey);
        if (empty($ulist)) {
            $ulist = array();
        }

        $ulist[$safeKey] = $val;
        
        return $this->set($ulistKey, $ulist);
    }
    
    public function getUlist ($ulistKey) {
        return $this->get($ulistKey);
    }

    public function listAdd ($listKey, $val = null) {
        $list = $this->get($listKey);
        if (empty($list)) {
            $list = array();
        }
        $list[] = $val;
        $success = $this->set($listKey, $list);
        prd(compact('val', 'listKey', 'list', 'success'));
        return $y;
    }
    public function getList ($listKey) {
        return $this->get($listKey);
    }
}