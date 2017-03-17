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

class Config
{
    /**
     * @var string | null
     */
    private $dsn;

    /**
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * @var int
     */
    private $port = 6379;

    /**
     * @var float
     */
    private $timeout = 5;

    /**
     * @var string
     */
    private $prefix = 'lock:';

    public function __construct($dsn)
    {
        $this->dsn = $dsn;
        $this->resolveDsn();
    }

    protected function resolveDsn()
    {
        $dsn = $this->dsn;
        list($hostAndPort, $timeout) = explode('@', $dsn);
        list($host, $port) = explode(':', $hostAndPort);
        $this->host = (string)$host;
        $this->port = (int)$port;
        $this->timeout = (float)$timeout;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return float
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = (string)$prefix;
    }

}
