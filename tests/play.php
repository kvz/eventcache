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


$E = new EventCacheInst(array(
    'app' => 'testapp',
    'trackEvents' => true,
    //'adapter' => 'EventCacheAdapterFile',
    'adapter' => 'EventCacheAdapterApc',
    //'adapter' => 'EventCacheAdapterMemcached',
    //'adapter' => 'EventCacheAdapterRedis',
));

$E->clear();
$E->delete('lijst');
$E->listAdd('lijst', 'kevin');
$E->listAdd('lijst', 'jp');
prd($E->getList('lijst'));


$E->ulistSet('lijst', 'naam1', 'kevin');
$E->ulistSet('lijst', 'naam2', 'jp');
$E->ulistSet('lijst', 'num1', 123);
$E->ulistSet('lijst', 'num2', 234);

prd($E->getUlist('lijst'));
