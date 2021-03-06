<?php
namespace AutoLock\Drivers;

/**
 * This file is part of mmdtl/autolock.
 *
 * (c) liulu <liulu.0610@gmail.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */
use \Redis;
use \RedisException;

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
    }

    public function connect($host, $port, $timeout)
    {
        return $this->redis->connect($host, $port, $timeout);
    }

    public function set($key, $value, $options = array())
    {
        return $this->redis->set($key, $value, $options);
    }

    /**
     * phpunit can't mock function whose name is keyword,so this
     * function have no unit test.You should modify carefully.
     * @param $script
     * @param array $args
     * @param int $numKeys
     * @return mixed
     */
    public function evalScript($script, $args = array(), $numKeys = 0)
    {
        return $this->redis->eval($script, $args, $numKeys);
    }

    /**
     * This method will never throw exception, only return false when can't connect with server
     * this function will return false or +PONG
     * @return mixed
     */
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

    public function getInstance()
    {
        return $this->redis;
    }
}
