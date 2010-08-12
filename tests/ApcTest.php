<?php
require_once dirname(dirname(__FILE__)).'/adapters/Apc.php';

class EventCacheAdapterApcTest extends PHPUnit_Framework_TestCase {
    public $Apc;
    protected function setUp ()
    {
        $this->Apc = new EventCacheAdapterApc();
        print_r(apc_cache_info());
    }
    
    public function testListAdd () {
        $this->Apc->listAdd('my_list', 'Kevin van Zonneveld');
        sleep(1);
        $this->Apc->listAdd('my_list', 'Kevin');
        sleep(1);
        $this->Apc->listAdd('my_list', 'Kevin Henk');
        sleep(1);
        $list = $this->Apc->getList('my_list');
        sleep(1);
        $this->assertEquals(array(
            'Kevin van Zonneveld',
            'Kevin',
            'Kevin Henk',
        ), $list);
    }
}