<?php
/**
 * Created by PhpStorm.
 * User: liulu
 * Date: 2017/3/13
 * Time: 18:13
 */

namespace AutoLock\tests;


use AutoLock\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    const CONFIG_DSN = '192.168.1.2:6479@10';

    /**
     * @return Config
     */
    public function testConstruct()
    {
        $config = new Config(self::CONFIG_DSN);
        $this->assertInstanceOf('\AutoLock\Config', $config);
        return $config;
    }

    /**
     * @depends testConstruct
     * @param Config $config
     */
    public function testResolveDsn(Config $config)
    {
        $this->assertEquals('192.168.1.2', $config->getHost());
        $this->assertEquals((int)'6479', $config->getPort());
        $this->assertEquals((float)'10', $config->getTimeout());
    }

    /**
     * @depends testConstruct
     * @param Config $config
     */
    public function testSetAndGetPrefix(Config $config)
    {
        $this->assertInternalType('string', $config->getPrefix());
        $prefixString = 'custome_lock:';
        $this->assertNull($config->setPrefix($prefixString));
        $this->assertEquals($prefixString, $config->getPrefix());
    }
}
