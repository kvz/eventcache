<?php
require_once dirname(dirname(__FILE__)).'/EventCache.php';

class EventCacheTest extends PHPUnit_Framework_TestCase {
    public $DBCalled = false;
    public $MagicKey = '';
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $arr = array(
            'app' => 'testapp',
            'trackEvents' => true,
            'adapter' => 'EventCacheFileAdapter',
        );
        EventCache::setOption($arr);
        EventCache::flush();
    }
    
    public function testRead() {
        EventCache::write('name', 'Kevin');
        $val = EventCache::read('name');
        $this->assertEquals('Kevin', $val);
    }
    public function testSquashArrayTo1Dim() {
        $y = array(
            'a' => array(1, 2, 3, 4),
            'b' => array(5, 6, 7, 8),
            'c' => array(9, 10, 11, 12),
        );

        $x = EventCache::squashArrayTo1Dim($y);

        $this->assertEquals('1741593048', $x['a']);
        $this->assertTrue(count($x) === 3);
    }

    public function testLightning() {
        $args = array(
            5,
            'Customer',
        );
        
        $this->assertEquals('Kevin', $this->urlMappingFunction('Kevin'));
        $this->assertEquals('Kevin', EventCache::read($this->MagicKey));

        $keys = EventCache::getKeys('deploy');
        $this->assertTrue(count($keys) === 0);
    }
    
    public function testMagic() {
        $EventCacheInst = EventCache::getInstance();
        $EventCacheInst->flush();
        $this->DBCalled = false;

        $events = $EventCacheInst->getEvents();
        $this->assertTrue(empty($events));
        
        $this->assertEquals('Kevin', $this->heavyDBFunction('Kevin'));
        $this->assertTrue($this->DBCalled);
        $this->assertEquals('van Zonneveld', $this->heavyDBFunction('van Zonneveld', array(
            'url' => array(
                'controller' => 'Customers',
                'action' => 'view',
                'id' => 5,
            ),
        )));
        $this->assertTrue($this->DBCalled);
        $this->assertEquals('van Zonneveld', $this->heavyDBFunction('van Zonneveld', array(
            'url' => array(
                'controller' => 'Customers',
                'action' => 'view',
                'id' => 6,
            ),
        )));
        $this->assertTrue($this->DBCalled);
        $this->assertEquals('van Zonneveld', $this->heavyDBFunction('van Zonneveld', array(
            'url' => array(
                'controller' => 'Customers',
                'action' => 'view',
                'id' => 6,
            ),
        )));
        $this->assertFalse($this->DBCalled);

        $this->assertEquals('van Zonneveld', $this->heavyDBFunction('van Zonneveld', array(
            'url' => array(
                'controller' => 'Customers',
                'action' => 'view',
                'id' => 6,
            ),
        )));
        $this->assertFalse($this->DBCalled);


        $this->assertEquals('Kevin', $this->heavyDBFunction('Kevin'));
        $this->assertTrue(!$this->DBCalled);

        $events = $EventCacheInst->getEvents();
        $this->assertContains('deploy', $events);
        $this->assertContains('Server::afterSave', $events);
        $this->assertTrue(count($events) === 2);
        
        $this->assertEquals('Kevin', EventCache::read($this->MagicKey));
    }
    
    public function heavyDBFunction($name, $retry = 3) {
        $this->DBCalled = false;
        $args = func_get_args();

        list ($this->MagicKey, $val) = EventCache::magic($this, __FUNCTION__, $args, array(
            'deploy',
            'Server::afterSave',
        ), array(
            'unique' => array('a' => array(1, 2, 3), 'b', 'c'),
            'keypair' => true,
        ));

        return $val;
    }
    public function _heavyDBFunction($name, $retry = 3) {
        $this->DBCalled = true;
        return $name;
    }
    
    public function urlMappingFunction($name, $retry = 3) {
        $this->DBCalled = false;
        $args = func_get_args();

        list ($this->MagicKey, $val) = EventCache::magic($this, __FUNCTION__, $args, array(
            'deploy',
        ), array(
            'unique' => array('a' => array(1, 2, 3), 'b', 'c'),
            'lightning' => true,
            'keypair' => true,
        ));

        return $val;
    }
    public function _urlMappingFunction($name, $retry = 3) {
        $this->DBCalled = true;
        return $name;
    }
}