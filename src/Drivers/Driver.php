<?php
/**
 * This file is part of mmdtl/autolock.
 *
 * (c) liulu <liulu.0610@gmail.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace AutoLock\Drivers;


interface Driver
{
    const PONG_STRING = '+PONG';
    /**
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @return bool
     */
    public function connect($host, $port, $timeout);

    /**
     * @param $key
     * @param $value
     * @param array $Options
     * @return Bool
     */
    public function set($key, $value, $Options = array());

    /**
     * @param $script
     * @param array $args
     * @param int $numKeys
     * @return mixed
     */
    public function evalScript($script, $args = array(), $numKeys = 0);

    /**
     * this function will return false or +PONG
     * @return mixed
     * @throws \RedisException
     */
    public function ping();

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setOption($key, $value);

    /**
     * @return string
     */
    public function getPrefixOptionName();

}