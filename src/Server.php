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

use autolock\src\Driver;
use autolock\src\Exception\InvalidConfigException;
use autolock\src\Exception\ServerConnectException;
use autolock\src\Exception\ServersOperateException;

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

    protected $driverMap = array(
        'phpredis' => '/redis'
    );

    protected $driverName;

    public function __construct(Config $config, $driverName = 'phpredis')
    {
        $this->config = $config;
        $this->driverName = $driverName;
        $this->open();
    }

    public function open()
    {
        if (empty($this->instance)) {
            $config = $this->getConfig();
            $driverClass = $this->getDriver();
            /**
             * @var $driver Driver
             */
            $driver = new $driverClass();
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
        }
    }

    protected function getDriver()
    {
        if (empty($this->driverMap[$this->driverName])) {
            throw new InvalidConfigException('server driver name can\'t be found.');
        } else {
            return $this->driverMap[$this->driverName];
        }
    }

    protected function getConfig()
    {
        if (empty($this->config)) {
            throw new InvalidConfigException('server config should not be empty.');
        } else {
            return $this->config;
        }
    }

    public function set($key, $value, $Options = array())
    {
        $this->open();
        return $this->instance->set($key, $value, $Options);
    }

    public function eval($script, $args = array(), $numKeys = 0)
    {
        $this->open();
        return $this->instance->eval($script, $args, $numKeys);
    }

    public function available()
    {
        $status = false;
        $successPingString = '+PONG';
        $responseString = $this->instance->ping();
        if ($responseString === $successPingString) {
            $status = true;
        }
        return $status;
    }
}
