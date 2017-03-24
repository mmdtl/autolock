<?php
namespace AutoLock\tests\Drivers;


use autolock\Drivers\Driver;
use AutoLock\Lock;
use AutoLock\Manager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

class PHPRedisTest extends TestCase
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
}
