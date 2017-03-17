<?php
use AutoLock\Lock;
use AutoLock\Server;

/**
 * Created by PhpStorm.
 * User: liulu
 * Date: 2017/3/16
 * Time: 16:30
 */
class Manager
{
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
     * @var \AutoLock\Pool
     */
    private $pool;


    function __construct(array $serversConfig, $retryDelay = 200, $retryCount = 0)
    {
        $this->pool = new Pool($serversConfig);
        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
    }

    public function lock($resource, $ttl, $autoRelease = false)
    {
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
            $drift = ($ttl * $this->clockDriftFactor) + 2;
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
     * @return bool
     */
    public function unlock(Lock $lock)
    {
        $resource = $lock->getResource();
        $token = $lock->getToken();
        foreach ($this->pool as $server) {
            self::unlockServer($server, $resource, $token);
        }
        return true;
    }

    private static function unlockServer(Server $server, $resource, $token)
    {
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        return $server->eval($script, array($resource, $token), 1);
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