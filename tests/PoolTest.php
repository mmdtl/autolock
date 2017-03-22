<?php
namespace AutoLock\tests;


use AutoLock\Config;
use autolock\Drivers\Driver;
use AutoLock\Drivers\PHPRedis;
use AutoLock\Pool;
use AutoLock\Server;
use Iterator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

class PoolTest extends TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    /**
     * @var array
     */
    protected $serversConfig;

    protected $serverConfig;

    /**
     * @var Driver
     */
    protected $driverObject;

    protected function setUp()
    {
        $this->prophet = new Prophet;

        $this->serversConfig = array(
            '127.0.0.1:6379@1',
            '127.0.0.1:6479@1',
            '127.0.0.1:6579@1',
        );
        $this->serverConfig = array(
            '127.0.0.1:6379@1',
        );

        $driver = $this->prophet->prophesize(PHPRedis::class);
        $truePHPRedis = new PHPRedis();
        foreach ($this->serversConfig as $configString) {
            $configObject = new Config($configString);
            $driver->connect($configObject->getHost(), $configObject->getPort(), $configObject->getTimeout())->willReturn(true);
            $driver->getPrefixOptionName()->willReturn($truePHPRedis->getPrefixOptionName());
            $driver->setOption($truePHPRedis->getPrefixOptionName(), $configObject->getPrefix())->willReturn(true);
        }
        $this->driverObject = $driver->reveal();
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testConstruct()
    {
        $pool = new Pool($this->serversConfig, $this->driverObject);
        $this->assertInstanceOf(Pool::class, $pool);
        $pool = new Pool($this->serverConfig, $this->driverObject);
        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testGetQuorum()
    {
        $pool = new Pool($this->serversConfig, $this->driverObject);
        $this->assertEquals(2, $pool->getQuorum());
        $pool = new Pool($this->serverConfig, $this->driverObject);
        $this->assertEquals(1, $pool->getQuorum());
    }


    /**
     * @dataProvider quorumMultiServersProvider
     */
    public function testCheckQuorumWithMultiServers($num, $result)
    {
        $pool = new Pool($this->serversConfig, $this->driverObject);
        $this->assertEquals($result, $pool->checkQuorum($num));
    }

    public function quorumMultiServersProvider()
    {
        return array(
            array(0, false),
            array(1, false),
            array(2, true),
            array(3, true),
            array(4, true),
        );
    }

    /**
     * @dataProvider quorumSingleServerProvider
     */
    public function testCheckQuorumWithSingleServer($num, $result)
    {
        $pool = new Pool($this->serverConfig, $this->driverObject);
        $this->assertEquals($result, $pool->checkQuorum($num));
    }

    public function quorumSingleServerProvider()
    {
        return array(
            array(0, false),
            array(1, true),
            array(2, true),
        );
    }

    public function testIterator()
    {
        $pool = new Pool($this->serversConfig, $this->driverObject);
        foreach ($pool as $key => $server) {
            $config = $this->serversConfig[$key];
            $configObject = new Config($config);
            /**
             * @var $server Server
             */
            $this->assertEquals($configObject, $server->getConfig());
        }

//        $iteratorMock = $this->getMockBuilder(Pool::class)->disableOriginalConstructor()->setMethods(array('rewind', 'valid', 'current', 'key', 'next'))->getMockForAbstractClass();
//        $this->mockIteratorItems($iteratorMock, array('foo', 'bar', 'bar'));
    }

    /**
     * Adds expected items to a mocked Iterator.
     */
    function mockIteratorItems(Iterator $iterator, array $items, $includeCallsToKey = false)
    {
        $iterator->expects($this->at(0))->method('rewind');
        $counter = 1;
        foreach ($items as $k => $v) {
            $iterator->expects($this->at($counter++))->method('valid')->will($this->returnValue(true));
            $iterator->expects($this->at($counter++))->method('current')->will($this->returnValue($v));
            if ($includeCallsToKey) {
                $iterator->expects($this->at($counter++))->method('key')->will($this->returnValue($k));
            }
            $iterator->expects($this->at($counter++))->method('next');
        }
        $iterator->expects($this->at($counter))->method('valid')->will($this->returnValue(false));
    }

}
