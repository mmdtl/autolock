<?php
namespace AutoLock\Test;


use AutoLock\Drivers\Driver;
use AutoLock\Lock;
use AutoLock\Manager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class LockTest extends TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    /**
     * @var ObjectProphecy
     */
    protected $manager;

    /**
     * @var Driver
     */
    protected $driverObject;

    protected function setUp()
    {
        $this->prophet = new Prophet;
        $this->manager = $this->prophet->prophesize(Manager::class);
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    public function testConstruct()
    {
        $lock = new Lock($this->manager->reveal(), '', '', '');
        $this->assertInstanceOf(Lock::class, $lock);
    }

    public function testRelease()
    {
        $lock = new Lock($this->manager->reveal(), '', '', '');
        $this->manager->unlock($lock)->willReturn(true)->shouldBeCalledTimes(1);
        $this->assertInstanceOf(Lock::class, $lock);
        $this->assertEquals(true, $lock->release());

        $this->manager->unlock($lock)->willReturn(false)->shouldBeCalledTimes(2);
        $this->assertEquals(false, $lock->release());
        $this->prophet->checkPredictions();
    }

    /**
     * @dataProvider getServersProvider
     */
    public function testGetValidityResourceAndToken($validity, $resource, $token, $validityResult, $resourceResult, $tokenResult, $autoRelease)
    {
        $lock = new Lock($this->manager->reveal(), $validity, $resource, $token, $autoRelease);
        $this->assertInstanceOf(Lock::class, $lock);
        $this->assertEquals($validityResult, $lock->getValidity());
        $this->assertEquals($resourceResult, $lock->getResource());
        $this->assertEquals($tokenResult, $lock->getToken());
        $this->assertEquals($autoRelease, $lock->getAutoRelease());
    }

    public function getServersProvider()
    {
        return array(
            array(3000, 'a', 'b', 3000, 'a', 'b', true),
            array('30003', 'aa', 'bb', 30003, 'aa', 'bb', true),
            array('0', '', '', 0, '', '', false),
            array('0', 123, 123, 0, '123', '123', false),
        );
    }

    public function testGetManager()
    {
        $lock = new Lock($this->manager->reveal(), '', '', '');
        $this->assertInstanceOf(Lock::class, $lock);
        $this->assertEquals($this->manager->reveal(), $lock->getManager());
    }

    /**
     * @dataProvider IsExpiredProvider
     */
    public function testIsExpired($validity, $afterTime, $result)
    {
        $lock = new Lock($this->manager->reveal(), $validity, '', '');
        $this->assertInstanceOf(Lock::class, $lock);
        $this->assertEquals($result, $lock->isExpired($afterTime));
    }

    public function IsExpiredProvider()
    {
        return array(
            array(1000, false, true),
            array(1000, 0, true),
            array(1000, 999, true),
            array(1000, 1001, false),
            array(1000, 2000, false),
        );
    }

    public function testDeconstruct()
    {
        $lock = new Lock($this->manager->reveal(), '', '', '', true);
        $this->manager->unlock($lock)->willReturn(true)->shouldBeCalledTimes(1);
        $this->assertInstanceOf(Lock::class, $lock);
        $lock->__destruct();
    }
}
