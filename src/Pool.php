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

use Iterator;

class Pool implements Iterator
{
    /**
     * @servers array Server
     */
    private $servers;

    private $configObjects;


    public function __construct($serversConfig)
    {
        foreach ($serversConfig as $configDsn) {
            $this->servers[] = $this->initServer($configDsn);
        }
    }

    protected function initServer($configDsn)
    {
        $config = new Config($configDsn);
        $this->configObjects[] = $config;
        return new Server($config);

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
        $servers = current($this->servers);
        return $servers;
    }

    public function key()
    {
        $servers = key($this->servers);
        return $servers;
    }

    public function next()
    {
        $servers = next($this->servers);
        return $servers;
    }

    public function valid()
    {
        $key = key($this->servers);
        $servers = ($key !== NULL && $key !== FALSE);
        return $servers;
    }
}
