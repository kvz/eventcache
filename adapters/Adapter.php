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
	public function add ($key, $val, $ttl = 0) {
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
}