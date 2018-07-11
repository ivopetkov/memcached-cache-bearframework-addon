<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->context->get(__FILE__);

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\MemcachedCacheDriver', 'classes/MemcachedCacheDriver.php');

$app->container
        ->set('CacheDriver', 'IvoPetkov\BearFrameworkAddons\MemcachedCacheDriver');
