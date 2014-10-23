Eventcache is a PHP class for caching that:
 - Is very fast
 - Supports multiple backends
   - memcache
   - memcached
   - apc (has some bugs)
   - redis (needs 2.2)
   - file
 - Is easy to implement
 - Doesn't care about your framework
 - Tries to solve the invalidation problem by using events & triggers
 - Can be used as a wrapper around heavy functions

On the  I held a [presentation on Eventcache](http://www.slideshare.net/kevinvz/eventcache)
during [the 2nd CakePHP borrel](http://www.cake-toppings.com/2010/10/15/venue-of-the-dutch-cakephp-borrel-event-announced/)
in Utrecht.
It explains what Eventcache does and how you can implement it.

## Setup

    require_once APP . DS . 'vendors' . DS . 'eventcache' . DS . 'EventCache.php';
    EventCache::setOption(array(
        'disable' => false, // Disable/Enable eventcache globally
        'adapter' => 'EventCacheAdapterRedis',
        'servers' => array(
            array(
                'host' => '10.0.0.135',
                'port' => 6379,
            ),
        ),
    ));

## Most Basic Example

    EventCache::write('name', 'Kevin');
    $val = EventCache::read('name');


