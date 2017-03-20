<?php

/**
 * This file is part of mmdtl/autolock.
 *
 * (c) liulu <liulu.0610@gmail.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */
namespace AutoLock;

/**
 * Class Lock contain info of lock. It has attribute like max lifetime of the lock,
 * lock's manager , key  of the lock and value of the lock
 *
 * @package AutoLock
 * @author Liu Lu <liulu.0610@gmail.com>
 * @since 0.1
 */
class Lock
{
    /**
     * @var float
     */
    private $validity;

    /**
     * @var string
     */
    private $resource;

    /**
     * @var string
     */
    private $token;

    /**
     * @var \Manager
     */
    private $manager;

    /**
     * @var bool
     */
    private $autoRelease;

    public function __construct($manager, $validity, $resource, $token, $autoRelease = false)
    {
        $this->validity = $validity;
        $this->resource = $resource;
        $this->token = $token;
        $this->manager = $manager;
        $this->autoRelease = $autoRelease;
    }

    /**
     * @return bool
     */
    public function release()
    {
        return $this->manager->unlock($this);
    }

    public function __destruct()
    {
        return $this->release();
    }

    /**
     * @return float
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return \Manager
     */
    public function getManager()
    {
        return $this->manager;
    }


}

