<?php
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
     * @dataProvider resolveDsnProvider
     * @param Config $config
     */
    public function testResolveDsn($config, $ip, $port, $timeout)
    {
        $configObject = new Config($config);
        $this->assertEquals($ip, $configObject->getHost());
        $this->assertEquals($port, $configObject->getPort());
        $this->assertEquals($timeout, $configObject->getTimeout());
    }

    public function resolveDsnProvider()
    {
        return array(
            array('192.168.1.2:6479@10', '192.168.1.2', 6479, 10),
            array('192.168.1.2:6479', '192.168.1.2', 6479, 5),
            array('192.168.1.2', '192.168.1.2', 6379, 5),
            array(array('192.168.1.2', 6479, 10), '192.168.1.2', 6479, 10),
            array(array('192.168.1.2', 6479), '192.168.1.2', 6479, 5),
            array(array('192.168.1.2'), '192.168.1.2', 6379, 5),
            array(array(), '127.0.0.1', 6379, 5),
            array('', '127.0.0.1', 6379, 5),
            array(null, '127.0.0.1', 6379, 5),
        );
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
