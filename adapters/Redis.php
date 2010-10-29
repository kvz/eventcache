<?php
require_once dirname(__FILE__) . '/Adapter.php';
class EventCacheAdapterRedis extends EventCacheAdapter {
	protected $Rediska;

	protected $_flushSafe = false;
	protected $_needVersion = '1.3.10';
	protected $_config = array(
		'servers' => array(
			array(
				'host' => '127.0.0.1',
				'port' => 6379,
			),
		),
	);

	public function init () {
		// Convert memcached style config to redis style: [0]=ip => [0][host] = ip
		foreach ($this->_config['servers'] as $i => $server) {
			if (is_string($server)) {
				unset($this->_config['servers'][$i]);
				$this->_config['servers'][$i]['host'] = $server;
			}
		}

		include_once('Rediska.php');
		include_once('Rediska/Key/List.php');
		include_once('Rediska/Key.php');
		include_once('Rediska/Key/Hash.php');

		if (!class_exists('Rediska_Key_Hash')) {
			return sprintf(
				'Rediska not installed. For instructions see: http://kevin.vanzonneveld.net/techblog/article/redis_in_php/'
			);
		}

		$this->Rediska = new Rediska($this->_config + array(
			 'redisVersion' => '1.3.10',
		));

		return true;
	}

	public function get ($key) {
		$Key = new Rediska_Key($key);
		$Key->setRediska($this->Rediska);

		if (!$Key->isExists()) {
			return false;
		}

//		try {
//			return $Key->getValue();
//		} catch (Exception $exc) {
//			prd($key);
//			echo $exc->getTraceAsString();
//		}
		# @todo
		#$Key->delete();
		return $Key->getValue();
	}
	public function set ($key, $val, $ttl = 0) {
		if ($ttl === 0) $ttl = null;
		$Key = new Rediska_Key($key, $ttl);
		$Key->setRediska($this->Rediska);
		return $Key->setValue($val);
	}
	public function delete ($key) {
		$Key = new Rediska_Key($key);
		$Key->setRediska($this->Rediska);
		return $Key->delete();
	}

	public function flush () {
		trigger_error('I\'m not letting you flush the entire Redis Database through EventCache. Consider using ->clear() instead');
	}

	public function increment ($key, $val = 1) {
		$Key = new Rediska_Key($key);
		$Key->setRediska($this->Rediska);
		return $Key->increment($val);
	}
	public function decrement ($key, $val = 1) {
		$Key = new Rediska_Key($key);
		$Key->setRediska($this->Rediska);
		return $Key->decrement($val);
	}


	public function ulistDelete ($ulistKey, $safeKey) {
		$MSet = new Rediska_Key_Hash($ulistKey);
		$MSet->setRediska($this->Rediska);
		return $MSet->remove($safeKey);
	}
	public function ulistSet ($ulistKey, $safeKey = null, $val = null) {
		$MSet = new Rediska_Key_Hash($ulistKey);
		$MSet->setRediska($this->Rediska);
		return $MSet->set($safeKey, $val);
	}
	public function getUlist ($ulistKey) {
		$MSet = new Rediska_Key_Hash($ulistKey);
		$MSet->setRediska($this->Rediska);
		return $MSet->toArray();
	}

	public function listAdd ($listKey, $val = null) {
		$List = new Rediska_Key_List($listKey);
		$List->setRediska($this->Rediska);
		return $List->append($val);
	}
	public function getList ($listKey) {
		$List = new Rediska_Key_List($listKey);
		$List->setRediska($this->Rediska);
		return $List->toArray();
	}
}