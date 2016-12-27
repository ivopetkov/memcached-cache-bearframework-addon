<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
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
    public function test1()
    {
        $m = new Memcached();
        $m->addServer('localhost', 11211);

        $m->set('key1', 'value1');

        $this->assertTrue($m->get('key1') === 'value1');
    }

}
