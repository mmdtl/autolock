<?php
namespace AutoLock\tests\Drivers;


use AutoLock\Drivers\Driver;
use AutoLock\Drivers\PHPRedis;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Redis;
use RedisException;

class PHPRedisTest extends TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    /**
     * @var ObjectProphecy
     */
    protected $redis;

    /**
     * @var Driver
     */
    protected $driverObject;

    protected function setUp()
    {
        $this->prophet = new Prophet;
        $this->redis = $this->prophet->prophesize(Redis::class);
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testConstruct()
    {
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertInstanceOf(PHPRedis::class, $driver);
        $this->assertInstanceOf(Redis::class, $driver->getInstance());
        $driver = new PHPRedis();
        $this->assertInstanceOf(PHPRedis::class, $driver);
    }

    public function testConnect()
    {
        $this->redis->connect(Argument::type('string'), Argument::type('int'), Argument::type('int'))->willReturn(true)->shouldBeCalledTimes(1);
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals(true, $driver->connect('127.0.0.1', 6379, 5));
    }

    public function testSet()
    {
        $this->redis->set(Argument::type('string'), Argument::type('string'), Argument::type('array'))->willReturn(true)->shouldBeCalledTimes(1);
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals(true, $driver->set('test_key', 'test_value', Array('xx', 'px' => 1000)));
    }

//    public function testEvalScript(){
//        $this->redis->eval(Argument::type('string'),Argument::type('array'),Argument::type('int'))->willReturn(true)->shouldBeCalledTimes(1);
//        $driver = new PHPRedis($this->redis->reveal());
//        $this->assertEquals(true,$driver->evalScript('test_script',array('arg0'=>'value0','arg1'=>'value1'),2));
//    }

    public function testPing()
    {
        $this->redis->ping()->willReturn('+PONG')->shouldBeCalledTimes(1);
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals('+PONG', $driver->ping());
    }

    public function testPingError()
    {
        $this->redis->ping()->willThrow(new RedisException())->shouldBeCalledTimes(1);
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals(false, $driver->ping());
    }

    public function testSetOption()
    {
        $this->redis->setOption(Argument::type('string'), Argument::type('string'))->willReturn(true)->shouldBeCalledTimes(1);
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals(true, $driver->setOption('test_option', 'test_option'));
    }

    public function testGetPrefixOptionName()
    {
        $driver = new PHPRedis($this->redis->reveal());
        $this->assertEquals(Redis::OPT_PREFIX, $driver->getPrefixOptionName());
    }

}
