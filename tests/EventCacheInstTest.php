<?php
function prd ($arr) {
	echo "<xmp>";
	if (is_array($arr) && count($arr)) {
		print_r($arr);
	} else {
		var_dump($arr);
	}
	echo "\n";
	echo "</xmp>";
	die();
}
require_once dirname(dirname(__FILE__)).'/EventCache.php';
require_once dirname(dirname(__FILE__)).'/EventCacheInst.php';

class EventCacheInstTest extends PHPUnit_Framework_TestCase {
	public $EventCacheInst;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp ()
	{
		$this->EventCacheInst = new EventCacheInst(array(
			'app' => 'testapp',
			'trackEvents' => true,
			//'adapter' => 'EventCacheAdapterFile',
			'adapter' => 'EventCacheAdapterApc',
			//'adapter' => 'EventCacheAdapterMemcached',
			//'adapter' => 'EventCacheAdapterRedis',
		));

		$this->EventCacheInst->flush();
		$this->EventCacheInst->clear();
	}
	protected function tearDown ()
	{
		$this->EventCacheInst->clear();
	}

	public function testListAdd () {
		$this->EventCacheInst->delete('my_list');
		$this->EventCacheInst->listAdd('my_list', 'Kevin van Zonneveld');
		$this->EventCacheInst->listAdd('my_list', 'Kevin');
		$this->EventCacheInst->listAdd('my_list', 'Kevin Henk');

		$list = $this->EventCacheInst->getList('my_list');

		$this->assertEquals(array(
			'Kevin van Zonneveld',
			'Kevin',
			'Kevin Henk',
		), $list);
	}

	public function testUlistSet () {
		$this->EventCacheInst->delete('my_ulist');
		$this->EventCacheInst->ulistSet('my_ulist', 'a', 'Kevin van Zonneveld');
		$this->EventCacheInst->ulistSet('my_ulist', 'b', 'Kevin');
		$this->EventCacheInst->ulistSet('my_ulist', 'b', 'Kevin Henk');

		$ulist = $this->EventCacheInst->getUlist('my_ulist');

		$this->assertEquals(array(
			'a' => 'Kevin van Zonneveld',
			'b' => 'Kevin Henk',
		), $ulist);
	}

	public function testWrite () {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
		));
		$this->assertEquals('Kevin van Zonneveld', $this->EventCacheInst->read('name'));
	}

	public function testRead () {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
		));
		$this->assertEquals('Kevin van Zonneveld', $this->EventCacheInst->read('name'));
		$this->assertFalse($this->EventCacheInst->read('name_break'));
	}

	public function testTrigger () {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
		));
		$this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
			'Server::afterSave',
			'Server::afterDelete',
		));

		$this->EventCacheInst->trigger('Server::afterSave');

		$this->assertEquals(false, $this->EventCacheInst->read('hostname'));
		$this->assertEquals('Kevin van Zonneveld', $this->EventCacheInst->read('name'));
	}

	public function testRegister () {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
		));
		$this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
			'Server::afterSave',
			'Server::afterDelete',
		));

		$this->EventCacheInst->register('name', 'Server::afterSave');

		$this->EventCacheInst->trigger('Server::afterSave');

		$this->assertEquals(false, $this->EventCacheInst->read('hostname'));
		$this->assertEquals(false, $this->EventCacheInst->read('name'));
	}

	public function testUnregister () {
		$this->EventCacheInst->clear();
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
			'Server::afterDelete',
		));
		$this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
			'Server::afterSave',
			'Server::afterDelete',
		));

		$keys = $this->EventCacheInst->getEventsKeys('Server::afterDelete');

		$this->assertContains('hostname', $keys);
		$this->assertContains('name', $keys);

		$this->EventCacheInst->unregister('name', 'Server::afterDelete');
		$keys = $this->EventCacheInst->getEventsKeys('Server::afterDelete');
		$this->assertContains('hostname', $keys);
		$this->assertTrue(count($keys) === 1);
	}

	public function testGetEvents () {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
		));
		$this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
			'Server::afterSave',
			'Server::afterDelete',
		));

		$events = $this->EventCacheInst->getTracksEvents();


		$this->assertContains('Employee::afterSave', $events);
		$this->assertContains('Employee::afterDelete', $events);
		$this->assertContains('Server::afterSave', $events);
		$this->assertContains('Server::afterDelete', $events);
		$this->assertTrue(count($events) === 4);
	}


	public function testGetKeys() {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
			'Server::afterDelete',
		));
		$keys = $this->EventCacheInst->getEventsKeys('Server::afterDelete');
		$this->assertContains('name', $keys);

		$this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
			'Server::afterSave',
			'Server::afterDelete',
		));

		$keys = $this->EventCacheInst->getEventsKeys('Server::afterDelete');
		$this->assertContains('hostname', $keys);
		$this->assertContains('name', $keys);
		$this->assertTrue(count($keys) === 2);
	}

	public function testSave() {
		$this->assertEquals('asdf__235@____b______', $this->EventCacheInst->sane('asdf~!235@#$%^b&_-=*('));
	}

	public function testClear() {
		$this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
			'Employee::afterSave',
			'Employee::afterDelete',
			'Server::afterDelete',
		));

		$this->EventCacheInst->clear();

		$this->assertEquals(false, $this->EventCacheInst->read('hostname'));
		$this->assertEquals(false, $this->EventCacheInst->read('name'));
	}
}