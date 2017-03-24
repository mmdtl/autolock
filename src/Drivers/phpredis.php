<?php
namespace AutoLock\Drivers;

/**
 * Created by PhpStorm.
 * User: liulu
 * Date: 2017/3/20
 * Time: 19:40
 */
use \Redis;
use RedisException;

class PHPRedis implements Driver
{
    /**
     * @var Redis
     */
    private $redis;

    public function __construct(Redis $redis = null)
    {
        if (is_null($redis)) {
            $this->redis = new Redis();
        } else {
            $this->redis = $redis;
        }
        return $this->redis;
    }

    public function connect($host, $port, $timeout)
    {
        return $this->redis->connect($host, $port, $timeout);
    }

    public function set($key, $value, $options = array())
    {
        return $this->redis->set($key, $value, $options);
    }

    public function evalScript($script, $args = array(), $numKeys = 0)
    {
        return $this->redis->eval($script, $args, $numKeys);
    }

    public function ping()
    {
        try {
            $response = $this->redis->ping();
        } catch (RedisException $e) {
            $response = false;
        }
        return $response;
    }

    public function setOption($key, $value)
    {
        return $this->redis->setOption($key, $value);
    }

    public function getPrefixOptionName()
    {
        return Redis::OPT_PREFIX;
    }
}