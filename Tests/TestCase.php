<?php

namespace Tests;
class TestCase extends IlluminateTestCase
{
	//....
    /**
     * 初始化mock物件
     *
     * @param string $class
     * @return Mockery
     */
    protected function initMock($class)
    {
        $mock = \Mockery::mock($class);
        $this->app->instance($class, $mock);

        return $mock;
    }
}