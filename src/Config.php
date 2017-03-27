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
        if (is_array($this->dsn)) {
            $dsn = $this->dsn;
            $this->host = empty($dsn[0]) ? $this->host : (string)$dsn[0];
            $this->port = empty($dsn[1]) ? $this->port : (int)$dsn[1];
            $this->timeout = empty($dsn[2]) ? $this->timeout : (float)$dsn[2];
        } else {
            $dsnArray = explode('@', $this->dsn);
            if (count($dsnArray) >= 1) {
                if (count($dsnArray) >= 2) {
                    list($hostAndPort, $timeout) = $dsnArray;
                } else {
                    $hostAndPort = $dsnArray[0];
                }
                $hostAndPortArray = explode(':', $hostAndPort);
                if (count($hostAndPortArray) >= 2) {
                    list($host, $port) = $hostAndPortArray;
                } else {
                    $host = $hostAndPortArray[0];
                }
            }
            $this->host = empty($host) ? $this->host : (string)$host;
            $this->port = empty($port) ? $this->port : (int)$port;
            $this->timeout = empty($timeout) ? $this->timeout : (float)$timeout;
        }
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
