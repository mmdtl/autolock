<?php
namespace AutoLock\Test;

use AutoLock\Drivers\Driver;
use AutoLock\Server;
use PHPUnit\Framework\TestCase;
use AutoLock\Drivers\PHPRedis;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use RedisException;

class ServerTest extends TestCase
{

    /**
     * @var Prophet
     */
    protected $prophet;

    /**
     * @var ObjectProphecy
     */
    protected $config;
    /**
     * @var ObjectProphecy
     */
    protected $driver;

    protected function setUp()
    {
        $this->prophet = new Prophet;

        $config = $this->prophet->prophesize('AutoLock\Config');
        $config->getHost()->willReturn('127.0.0.1');
        $config->getPort()->willReturn(6379);
        $config->getTimeout()->willReturn(1);
        $config->getPrefix()->willReturn('lock:');
        $this->config = $config;

        $configObject = $config->reveal();
        $driver = $this->prophet->prophesize('\AutoLock\Drivers\PHPRedis');
        $truePHPRedis = new PHPRedis();
        $driver->connect($configObject->getHost(), $configObject->getPort(), $configObject->getTimeout())->willReturn(true);
        $driver->getPrefixOptionName()->willReturn($truePHPRedis->getPrefixOptionName());
        $driver->setOption($truePHPRedis->getPrefixOptionName(), $configObject->getPrefix())->willReturn(true);
        $this->driver = $driver;
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testConstruct()
    {
        $server = new Server($this->config->reveal(), $this->driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
    }


    public function testGet()
    {
        $config = $this->config->reveal();
        $driver = $this->driver->reveal();
        $server = new Server($config, $driver);
        $this->assertEquals($config, $server->getConfig());
        $this->assertEquals($driver, $server->getDriver());
    }


    public function testGetInstance()
    {
        $config = $this->prophet->prophesize('\AutoLock\Config');
        $config->getHost()->willReturn('127.0.0.1')->shouldBeCalled();
        $config->getPort()->willReturn(6379)->shouldBeCalled();
        $config->getTimeout()->willReturn(1)->shouldBeCalled();
        $config->getPrefix()->willReturn('lock:')->shouldBeCalled();
        $configObject = $config->reveal();

        $driver = $this->prophet->prophesize('\AutoLock\Drivers\PHPRedis');
        $truePHPRedis = new PHPRedis();
        $driver->connect($configObject->getHost(), $configObject->getPort(), $configObject->getTimeout())->willReturn(true)->shouldBeCalledTimes(1);
        $driver->getPrefixOptionName()->willReturn($truePHPRedis->getPrefixOptionName())->shouldBeCalledTimes(1);
        $driver->setOption($truePHPRedis->getPrefixOptionName(), $configObject->getPrefix())->willReturn(true)->shouldBeCalledTimes(1);

        $server = new Server($configObject, $driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
        $instance = $server->getInstance();
        $this->assertInstanceOf('\AutoLock\Drivers\Driver', $instance);
        $this->prophet->checkPredictions();

        $instance = $server->getInstance();
        $this->assertInstanceOf('\AutoLock\Drivers\Driver', $instance);
        $this->prophet->checkPredictions();
    }

    /**
     * @expectedException \AutoLock\Exception\ServerConnectException
     */
    public function testGetInstanceConnectError()
    {
        $configObject = $this->config->reveal();
        $driver = $this->prophet->prophesize('\AutoLock\Drivers\PHPRedis');
        $truePHPRedis = new PHPRedis();
        $driver->connect($configObject->getHost(), $configObject->getPort(), $configObject->getTimeout())->willReturn(false);
        $driver->getPrefixOptionName()->willReturn($truePHPRedis->getPrefixOptionName());
        $driver->setOption($truePHPRedis->getPrefixOptionName(), $configObject->getPrefix())->willReturn(true);

        $server = new Server($configObject, $driver->reveal());
        $server->getInstance();
    }

    /**
     * @expectedException \AutoLock\Exception\ServersOperateException
     */
    public function testGetInstanceOperateError()
    {
        $configObject = $this->config->reveal();
        $driver = $this->prophet->prophesize('\AutoLock\Drivers\PHPRedis');
        $truePHPRedis = new PHPRedis();
        $driver->connect($configObject->getHost(), $configObject->getPort(), $configObject->getTimeout())->willReturn(true);
        $driver->getPrefixOptionName()->willReturn($truePHPRedis->getPrefixOptionName());
        $driver->setOption($truePHPRedis->getPrefixOptionName(), $configObject->getPrefix())->willReturn(false);

        $server = new Server($configObject, $driver->reveal());
        $server->getInstance();
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($key, $value, $option, $result)
    {
        $driver = $this->driver;
        $driver->set($key, $value, $option)->willReturn($result)->shouldBeCalledTimes(1);
        $server = new Server($this->config->reveal(), $driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
        $this->assertEquals($result, $server->set($key, $value, $option));
        $this->prophet->checkPredictions();
    }

    public function setProvider()
    {
        return array(
            //@todo 设置长度上限
            array('test1', 'adfdsfdsfdsf', array(), true),
            array('test2', 'aaaaaaaaaaaa', array('NX', 'PX' => 500), true),
            array('test3', 'bbbbbbbbb', array('NX', 'PX' => 500), false),
        );
    }

    /**
     * @dataProvider evalProvider
     */
    public function testEval($script, $args, $numKeys, $result)
    {
        $driver = $this->driver;
        $driver->evalScript($script, $args, $numKeys)->willReturn($result)->shouldBeCalledTimes(1);;
        $server = new Server($this->config->reveal(), $driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
        $this->assertEquals($result, $server->evalScript($script, $args, $numKeys));
        $this->prophet->checkPredictions();
    }

    public function evalProvider()
    {
        return array(
            //@todo 设置长度上限
            array('sdfsdf  sdfsdf ', array('222'), 1, true),
            array('test2', array('adfsdf', 'zzzz'), 2, true),
            array('test2', array('adfsdf', 'zzzz'), 3, false),
        );
    }

    /**
     * @dataProvider availableProvider
     */
    public function testAvailable($response, $result)
    {
        $driver = $this->driver;
        $driver->ping()->willReturn($response);
        $server = new Server($this->config->reveal(), $driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
        $this->assertEquals($result, $server->available());
    }

    public function availableProvider()
    {
        return array(
            array(Driver::PONG_STRING, true),
            array('wrong_string', false),
        );
    }

    /**
     * @expectedException \RedisException
     */
    public function testAvailableServerFail()
    {
        $driver = $this->driver;
        $driver->ping()->willThrow(new  RedisException());
        $server = new Server($this->config->reveal(), $driver->reveal());
        $this->assertInstanceOf('\AutoLock\Server', $server);
        $this->assertEquals(false, $server->available());
    }
}
