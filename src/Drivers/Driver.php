<?php
/**
 * Created by PhpStorm.
 * User: liulu
 * Date: 2017/3/14
 * Time: 11:17
 */

namespace autolock\src;


interface Driver
{
    /**
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @return $this
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
    public function eval($script, $args = array(), $numKeys = 0);

    /**
     * @return bool
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