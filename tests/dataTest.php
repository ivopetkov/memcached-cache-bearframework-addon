<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class DataTest extends BearFrameworkAddonTestCase
{

    /**
     * 
     */
    public function testAll()
    {
        $app = $this->getApp();

        $app->cache->delete('key1');

        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === null);
        $this->assertFalse($app->cache->exists('key1'));

        $app->cache->set($app->cache->make('key1', 'data1'));
        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === 'data1');
        $this->assertTrue($app->cache->exists('key1'));
        $app->cache->delete('key1');

        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === null);
        $this->assertFalse($app->cache->exists('key1'));
    }

    /**
     * 
     */
    public function testTTL()
    {
        $app = $this->getApp();

        $app->cache->delete('key1');

        $cacheItem = $app->cache->make('key1', 'data1');
        $cacheItem->ttl = 2;
        $app->cache->set($cacheItem);
        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === 'data1');
        $result = $app->cache->exists('key1');
        $this->assertTrue($result);
        sleep(3);
        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === null);
        $result = $app->cache->exists('key1');
        $this->assertFalse($result);
        $app->cache->delete('key1');
    }

}
