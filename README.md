AutoLock - Redis distributed locks in PHP

This library implements the RedLock algorithm introduced by [@antirez](http://antirez.com/)

This library implements the Redis-based distributed lock manager algorithm [described in this blog post](http://antirez.com/news/77).

To create a lock manager:

```php

$pool = new \AutoLock\Pool(array(
    '192.168.1.1:6379',
    '192.168.1.2:6379',
    '192.168.1.3:6379',
),new \AutoLock\Drivers\PHPRedis());

$manager = new \AutoLock\Manager($pool);

```

To acquire a lock:

```php

$lock = $manager->lock('distributed_lock',1000);;

```

Where the resource name is an unique identifier of what you are trying to lock
and 1000 is the number of milliseconds for the validity time.

The returned value is `false` if the lock was not acquired (you may try again),
otherwise an object representing the lock is returned, you should use isExpired method
to detect whether the lock is expired now or in certain time:

```php

$status = $lock->isExpired();
//$status will be true if the lock is expired or it will be false
$status = $lock->isExpired(3600 * 1000);
//$status will be true if the lock will be expired after 3600 seconds or it will be false

```




* validity, an integer representing the number of milliseconds the lock will be valid.
* resource, the name of the locked resource as specified by the user.
* token, a random token value which is used to safe reclaim the lock.

To release a lock:

```php
    $lock->unlock($lock)
```

It is possible to setup the number of retries (by default 3) and the retry
delay (by default 200 milliseconds) used to acquire the lock.

The retry delay is actually chosen at random between `$retryDelay / 2` milliseconds and
the specified `$retryDelay` value.

