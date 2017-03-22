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

/**
 * Class Pool is using to collect servers.
 *
 * @package AutoLock
 * @author Liu Lu <liulu.0610@gmail.com>
 * @since 0.1
 */

class Pool implements \Iterator
{
    /**
     * @servers array Server
     */
    private $servers;

    /**
     * @var int
     */
    private $quorum;


    /**
     * Pool constructor.
     * @param $serversConfig
     * @param Driver $driver
     */
    public function __construct($serversConfig, Driver $driver)
    {
        foreach ($serversConfig as $configDsn) {
            $this->servers[] = $this->getServer($this->getConfig($configDsn), $driver);
        }
        $this->quorum = $this->getQuorum();
    }

    public function getQuorum()
    {
        return min(count($this->servers), (floor(count($this->servers) / 2) + 1));
    }

    protected function getConfig($dsn)
    {
        return new Config($dsn);
    }

    protected function getServer(Config $config, Driver $driver)
    {
        return new Server($config, $driver);
    }

    public function checkQuorum($n)
    {
        return (int)$n >= $this->quorum;
    }

    public function rewind()
    {
        reset($this->servers);
    }

    public function current()
    {
        return current($this->servers);

    }

    public function key()
    {
        return key($this->servers);
    }

    public function next()
    {
        next($this->servers);
    }

    public function valid()
    {
        $key = key($this->servers);
        $servers = ($key !== NULL && $key !== FALSE);
        return $servers;
    }
}
