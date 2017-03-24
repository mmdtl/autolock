<?php
namespace AutoLock\tests;


use AutoLock\Config;
use autolock\Drivers\Driver;
use AutoLock\Drivers\PHPRedis;
use AutoLock\Manager;
use AutoLock\Pool;
use AutoLock\Server;
use Iterator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Call\Call;
use Prophecy\Promise\ReturnPromise;
use Prophecy\Prophet;
use AutoLock\Lock;

class ManagerTest extends TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    protected $pool;

    protected function setUp()
    {
        $this->prophet = new Prophet;
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    protected function getPoolForAvailable($totalServer, $onLineServersNumber)
    {
        $pool = $this->prophet->prophesize(Pool::class);
        $servers = array();
        for ($i = 0; $i < $totalServer; $i++) {
            $server = $this->prophet->prophesize(Server::class);
            if ($i < $onLineServersNumber) {
                $server->available()->willReturn(true);
            } else {
                $server->available()->willReturn(false);
            }
            $servers[] = $server->reveal();
        }
        $this->mockIteratorItemsForProphet($pool, $servers);
        return $pool;
    }

    /**
     * @dataProvider availableProvider
     */
    public function testAvailable($totalServer, $onLineServersNumber, $available)
    {
        $pool = $this->getPoolForAvailable($totalServer, $onLineServersNumber);
        $pool->checkQuorum($onLineServersNumber)->willReturn($available);
        $manager = new Manager($pool->reveal());
        $this->assertEquals($available, $manager->available());
    }

    public function availableProvider()
    {
        return array(
            array(0, 0, false,),
            array(1, 0, false,),
            array(1, 0, true,),
            array(2, 0, false,),
            array(2, 1, false,),
            array(2, 2, true,),
            array(3, 0, false,),
            array(3, 1, false,),
            array(3, 2, true,),
            array(3, 3, true),
        );
    }

    protected function getServerForUnlock($resource, $ttl)
    {
        $server = $this->prophet->prophesize(Server::class);
        $this->mockServerEvalScriptFunc($server, $resource, $ttl);
        return $server->reveal();
    }

    protected function getServerForLock($resource, $ttl)
    {
        $server = $this->prophet->prophesize(Server::class);
        $this->mockServerSetFunc($server, $resource, $ttl);
        if ($ttl <= Manager::MIN_DRIFT) {
            $this->mockServerEvalScriptFunc($server, $resource, $ttl);
        }
        return $server->reveal();
    }

    protected function mockServerSetFunc($server, $resource, $ttl)
    {
        $unit = $this;
        $server->set($resource, Argument::type('string'), Argument::type('array'))->willReturn(true)->should(function ($call) use ($unit, $resource, $ttl) {
            $args = $call[0];
            /**
             * @var $call $arg
             */
            $values = $args->getArguments();
            $unit->assertArrayHasKey(0, $values[2]);
            $unit->assertEquals('NX', $values[2][0]);
            $unit->assertArrayHasKey('PX', $values[2]);
            $unit->assertGreaterThanOrEqual($ttl, $values[2]['PX']);
        });
        return $server;
    }

    protected function mockServerEvalScriptFunc($server, $resource, $ttl)
    {
        $unit = $this;
        $server->evalScript(Argument::type('string'), Argument::type('array'))->should(function ($call) use ($unit, $resource, $ttl) {
            $args = $call[0];
            /**
             * @var $call $arg
             */
            $values = $args->getArguments();
            $unit->assertEquals(Manager::UNLOCK_SCRIPT, $values[0]);
            $unit->assertEquals(2, count($values[1]));
            $unit->assertEquals($resource, $values[1][0]);
            $unit->assertInternalType('string', $values[1][1]);
        });
    }

    /**
     * @dataProvider lockProvider
     */
    public function testLock($resource, $ttl, $autoRelease)
    {
        $pool = $this->prophet->prophesize(Pool::class);
        $totalServer = 3;
        for ($i = 0; $i < $totalServer; $i++) {
            $servers[] = $this->getServerForLock($resource, $ttl);
        }

        $this->mockIteratorItemsForProphet($pool, $servers);
        $pool->checkQuorum($totalServer)->willReturn(true);

        $manager = new Manager($pool->reveal());
        $lock = $manager->lock($resource, $ttl, $autoRelease);
        if ($ttl > Manager::MIN_DRIFT) {
            $this->assertInstanceOf(Lock::class, $lock);
            $this->assertEquals($resource, $lock->getResource());
            $this->assertEquals($manager, $lock->getManager());
            $this->assertInternalType('string', $lock->getToken());
            $validity = $lock->getValidity();
            $this->assertInternalType('int', $validity);
            $this->assertGreaterThan(0, $validity);
            $this->assertGreaterThan(-$ttl, -$validity);
            $this->assertEquals($autoRelease, $lock->getAutoRelease());
        } else {
            $this->assertEquals(false, $lock);
        }

    }

    public function lockProvider()
    {
        return array(
            array('test0', 0, false),
            array('test1', Manager::MIN_DRIFT, false),
            array('test2', Manager::MIN_DRIFT * 5, false),
            array('test3', 1000, false),
            array('test4', 3600 * 1000, false),
        );
    }


    /**
     * @dataProvider unlockProvider
     */
    public function testUnlock($resource, $ttl, $autoRelease)
    {
        $lock = $this->prophet->prophesize(Lock::class);
        $lock->getResource()->willReturn($resource)->shouldBeCalledTimes(1);
        $lock->getToken()->willReturn('wwefdsff')->shouldBeCalledTimes(1);

        $pool = $this->prophet->prophesize(Pool::class);
        $totalServer = 3;
        for ($i = 0; $i < $totalServer; $i++) {
            $servers[] = $this->getServerForUnlock($resource, $ttl);
        }

        $this->mockIteratorItemsForProphet($pool, $servers);
        $pool->checkQuorum($totalServer)->willReturn(true);

        $manager = new Manager($pool->reveal());
        $lock->getManager()->willReturn($manager)->shouldBeCalledTimes(1);
        $manager->unlock($lock->reveal());
    }

    public function unlockProvider()
    {
        return array(
            array('test0', 0, false),
            array('test1', Manager::MIN_DRIFT, false),
            array('test2', Manager::MIN_DRIFT * 5, false),
            array('test3', 1000, false),
            array('test4', 3600 * 1000, false),
        );
    }

    /**
     * Adds expected items to a mocked Iterator.
     */
    public function mockIteratorItems($iterator, array $items, $includeCallsToKey = false)
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

    /**
     * Adds expected items to a mocked Iterator.
     */
    public function mockIteratorItemsForProphet($iterator, array $items)
    {
        //Fist call before looping on iterator
        $iterator->rewind()->will(function () use (&$items) {
            return reset($items);
        });
        //Will fetch values one by one
        $iterator->current()->will(function () use (&$items) {
            return current($items);
        });
        //Will be called at end of loop to get the move the cursor on next value
        $iterator->next()->will(function () use (&$items) {
            return next($items);
        });
        $iterator->key()->will(function () use (&$items) {
            return key($items);
        });

        $iterator->valid()->will(function () use (&$items) {
            $key = key($items);
            $servers = ($key !== NULL && $key !== FALSE);
            return $servers;
        });
    }
}
