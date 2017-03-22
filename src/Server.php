<?php
/**
 * This file is part of mmdtl/autolock.
 *
 * (c) liulu <liulu.0610@gmail.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */
namespace AutoLock;

use autolock\Drivers\Driver;
use AutoLock\Exception\ServerConnectException;
use AutoLock\Exception\ServersOperateException;

/**
 * Class Server is object to connect to redis server , create and release lock.
 *
 * @package AutoLock
 * @author Liu Lu <liulu.0610@gmail.com>
 * @since 0.1
 */
class Server
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Driver;
     */
    private $instance;

    /**
     * @var Driver
     */
    protected $driver;

    /**
     * Server constructor.
     * @param Config $config
     * @param Driver $driver
     */
    public function __construct(Config $config, Driver $driver)
    {
        $this->config = $config;
        $this->driver = $driver;
    }

    /**
     * @throws ServerConnectException
     * @throws ServersOperateException
     */
    protected function open()
    {
        if (empty($this->instance)) {
            $config = $this->config;
            $driver = $this->driver;
            $status = $driver->connect($config->getHost(), $config->getPort(), $config->getTimeout());
            if ($status !== true) {
                throw new ServerConnectException('server connect fail.');
            }
            $optionName = $driver->getPrefixOptionName();
            $prefix = $config->getPrefix();
            $status = $driver->setOption($optionName, $prefix);
            if ($status !== true) {
                throw new ServersOperateException("server set option $optionName:$prefix fail.");
            }
            $this->instance = $driver;
        }
    }

    /**
     * @param $key
     * @param $value
     * @param array $Options
     * @return Bool
     */
    public function set($key, $value, $Options = array())
    {
        return $this->getInstance()->set($key, $value, $Options);
    }

    /**
     * @param $script
     * @param array $args
     * @param int $numKeys
     * @return mixed
     */
    public function evalScript($script, $args = array(), $numKeys = 0)
    {
        return $this->getInstance()->evalScript($script, $args, $numKeys);
    }

    /**
     * @return bool
     */
    public function available()
    {
        $status = false;
        $successPingString = Driver::PONG_STRING;
        $responseString = $this->getInstance()->ping();
        if ($responseString === $successPingString) {
            $status = true;
        }
        return $status;
    }

    /**
     * @return Driver
     * @throws ServerConnectException
     * @throws ServersOperateException
     */
    public function getInstance()
    {
        $this->open();
        return $this->instance;
    }
}
