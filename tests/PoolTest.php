<?php
namespace AutoLock\tests;


use AutoLock\Config;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /**
     * @var Prophet
     */
    protected $prophet;

    protected function setUp()
    {
        $this->prophet = new Prophet;
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }
}
