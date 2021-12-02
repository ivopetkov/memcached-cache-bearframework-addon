<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class DataTest extends BearFramework\AddonTests\PHPUnitTestCase
{

    protected function initializeApp(bool $setLogger = true, bool $setDataDriver = true, bool $setCacheDriver = true, bool $addAddon = true): \BearFramework\App
    {
        $app = parent::initializeApp($setLogger, $setDataDriver, false, $addAddon);
        if ($setCacheDriver) {
            $app->cache->setDriver(new \IvoPetkov\BearFrameworkAddons\MemcachedCacheDriver([
                        'servers' => [
                            [
                                'host' => 'localhost',
                                'port' => 11211,
                                'keyPrefix' => 'prefix1'
                            ],
                            [
                                'host' => 'localhost',
                                'port' => 11211,
                                'keyPrefix' => 'prefix2'
                            ]
                        ]
            ]));
        }
        return $app;
    }

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

    /**
     * 
     */
    public function testBigValues()
    {
        $app = $this->getApp();

        $app->cache->delete('key1');

        $text = '';
        for ($i = 0; $i < 200000; $i++) {
            $text .= base64_encode(md5(uniqid()));
        }

        $cacheItem = $app->cache->make('key1', $text);
        $app->cache->set($cacheItem);
        $result = $app->cache->getValue('key1');
        $this->assertTrue($result === $text);
    }

}
