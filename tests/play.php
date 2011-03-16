<?php
error_reporting(E_ALL);
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


include_once('Rediska/Key/Set.php');
$key = 'test';
$Set = new Rediska_Key_Set($key);
$Set->add('a');
$Set->add('b');
$Set->add('b');
$Set->add('a');
prd($Set->toArray());


die();

require_once dirname(dirname(__FILE__)).'/EventCache.php';
require_once dirname(dirname(__FILE__)).'/EventCacheInst.php';


$E = new EventCacheInst(array(
    'app' => 'testapp',
    'trackEvents' => false,
    //'adapter' => 'EventCacheAdapterFile',
    'adapter' => 'EventCacheAdapterApc',
    //'adapter' => 'EventCacheAdapterMemcached',
    //'adapter' => 'EventCacheAdapterRedis',
));

#$E->flush();
//
//$E->delete('test');
//$lijst = $E->read('test');
//if (empty($lijst)) {
//    $lijst = array();
//}
//$lijst[] = 'test';
//$E->write('test', $lijst);
//prd($E->read('test'));
apc_clear_cache('user');



$E->delete('b');
$E->write('b', '1');
$E->delete('b');
$E->listAdd('b', 'kevin');
$E->listAdd('b', 'jp');


$lijst = $E->getList('lijst');
prd(compact('lijst'));


$E->ulistSet('ulijst', 'naam1', 'kevin');
$E->ulistSet('ulijst', 'naam2', 'jp');
$E->ulistSet('ulijst', 'num1', 123);
$E->ulistSet('ulijst', 'num2', 234);
$ulijst = $E->getUlist('ulijst');
prd(compact('ulijst'));
