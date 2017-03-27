<?php
namespace AutoLock;
use AutoLock\Exception\ManagerCompareException;

/**
 * Class Manager is using to create lock and release lock
 * using pool.
 *
 * @package AutoLock
 * @author Liu Lu <liulu.0610@gmail.com>
 * @since 0.1
 */
class Manager
{
    const MIN_DRIFT = 2;

    const UNLOCK_SCRIPT = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
    ';

    /**
     * @var int Delay millisecond after fail to get lock
     */
    private $retryDelay;
    /**
     * @var int Number of times after fail to get lock
     */
    private $retryCount;
    /**
     * @var float
     * Clock drift coefficient,
     * to prevent redis and web time to pass the speed of different,
     * resulting in lock early failure of the insurance value
     */
    private $clockDriftFactor = 0.01;
    /**
     * @var Pool
     */
    private $pool;


    function __construct(Pool $pool, $retryDelay = 200, $retryCount = 3)
    {
        $this->pool = $pool;
        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
    }

    /**
     * $ttl's unit is milliseconds, min $ttl is 2 milliseconds which means you will aways
     * get a lock which is expired
     * @param string $resource
     * @param int $ttl
     * @param bool $autoRelease
     * @return Lock|bool
     */
    public function lock($resource, $ttl, $autoRelease = false)
    {
        //@todo 验证$ttl > 0
        $ttl = (int)$ttl;
        $resource = (string)$resource;
        $token = uniqid();
        $retry = $this->retryCount;
        do {
            $n = 0;
            $startTime = microtime(true) * 1000;
            foreach ($this->pool as $server) {
                /**
                 * @var $server Server
                 */
                if ($server->set($resource, $token, array('NX', 'PX' => $ttl))) {
                    $n++;
                }
            }
            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + self::MIN_DRIFT;
            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;
            if ($this->pool->checkQuorum($n) && $validityTime > 0) {
                return new Lock($this, $validityTime, $resource, $token, $autoRelease);
            } else {
                foreach ($this->pool as $server) {
                    /**
                     * @var $server Server
                     */
                    $this->unlockServer($server, $resource, $token);
                }
            }
            // Wait a random delay before to retry
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);
            $retry--;
        } while ($retry > 0);
        return false;
    }

    /**
     * @param Lock $lock
     * @throws ManagerCompareException
     */
    public function unlock(Lock $lock)
    {
        if ($lock->getManager() !== $this) {
            throw new ManagerCompareException('You must unlock with the manager who create it');
        }
        $resource = $lock->getResource();
        $token = $lock->getToken();
        foreach ($this->pool as $server) {
            self::unlockServer($server, $resource, $token);
        }
    }

    private static function unlockServer(Server $server, $resource, $token)
    {
        $script = self::UNLOCK_SCRIPT;
        $server->evalScript($script, array($resource, $token));
    }

    public function available()
    {
        $onLineServersNumber = 0;
        foreach ($this->pool as $server) {
            /**
             * @var $server Server
             */
            if ($server->available()) {
                $onLineServersNumber++;
            }
        }
        if ($this->pool->checkQuorum($onLineServersNumber)) {
            return true;
        } else {
            return false;
        }
    }
}