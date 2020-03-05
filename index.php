<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__DIR__);

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\MemcachedCacheDriver', 'classes/MemcachedCacheDriver.php');
