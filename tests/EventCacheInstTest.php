<?php
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
            // 'adapter' => 'EventCacheAdapterFile',
            // 'adapter' => 'EventCacheAdapterApc',
            'adapter' => 'EventCacheAdapterMemcached',
        ));

        $this->EventCacheInst->clear();
    }
    protected function tearDown ()
    {
        $this->EventCacheInst->clear();
    }

    public function testUlistSet () {
        $this->EventCacheInst->flush();
        
        $this->EventCacheInst->ulistSet('EventCacheLogEntries', null, 'Kevin van Zonneveld');
        $this->EventCacheInst->ulistSet('EventCacheLogEntries', null, 'Kevin');

        $ulist = $this->EventCacheInst->read('EventCacheLogEntries');

        $this->assertEquals(array(
            'Kevin van Zonneveld',
            'Kevin',
        ), $ulist);

        
        $this->EventCacheInst->flush();

        $this->EventCacheInst->ulistSet('EventCacheLogEntries', 'a', 'Kevin van Zonneveld');
        $this->EventCacheInst->ulistSet('EventCacheLogEntries', 'b', 'Kevin');

        $ulist = $this->EventCacheInst->read('EventCacheLogEntries');

        $this->assertEquals(array(
            'a' => 'Kevin van Zonneveld',
            'b' => 'Kevin',
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
        $this->assertFalse($this->EventCacheInst->read('name1'));
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
        $this->EventCacheInst->flush();
        $this->EventCacheInst->write('name', 'Kevin van Zonneveld', array(
            'Employee::afterSave',
            'Employee::afterDelete',
            'Server::afterDelete',
        ));
        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));
        
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');

        $this->assertContains('hostname', $keys);
        $this->assertContains('name', $keys);
        
        $this->EventCacheInst->unregister('name', 'Server::afterDelete');
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
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
        
        $events = $this->EventCacheInst->getEvents();


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
        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
        $this->assertContains('name', $keys);

        $this->EventCacheInst->write('hostname', 'kevin.vanzonneveld.net', array(
            'Server::afterSave',
            'Server::afterDelete',
        ));

        $keys = $this->EventCacheInst->getKeys('Server::afterDelete');
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