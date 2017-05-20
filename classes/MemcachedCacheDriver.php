<?php

/*
 * Memcached cache addon for Bear Framework
 * https://github.com/ivopetkov/memcached-cache-bearframework-addon
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use BearFramework\App;

/**
 * Mamcached based cache driver.
 */
class MemcachedCacheDriver implements \BearFramework\App\ICacheDriver
{

    private static $instances = [];

    private function getInstance()
    {
        $serverIndex = 0;
        if (!isset(self::$instances[$serverIndex])) {
            $app = App::get();
            $addonOptions = $app->addons->get('ivopetkov/memcached-cache-bearframework-addon')->options;
            if (!isset($addonOptions['servers'])) {
                throw new \Exception('No memcached servers specified');
            }
            foreach ($addonOptions['servers'] as $server) {
                if (!isset($server['host'])) {
                    throw new \Exception('Missing memcached server host');
                }
                if (!isset($server['port'])) {
                    throw new \Exception('Missing memcached server port');
                }
                self::$instances[$serverIndex] = new \Memcached();
                self::$instances[$serverIndex]->addServer($server['host'], $server['port']);
                break;
            }
        }
        return self::$instances[$serverIndex];
    }

    /**
     * Stores a value in the cache.
     * 
     * @param string $key The key under which to store the value.
     * @param type $value The value to store.
     * @param int $ttl Number of seconds to store value in the cache.
     * @return void No value is returned.
     */
    public function set(string $key, $value, int $ttl = null): void
    {
        $instance = $this->getInstance();
        $result = $instance->set(md5($key), gzcompress(serialize([$key, $value])), $ttl !== null && $ttl > 0 ? time() + $ttl : 0);
        if ($result !== true) {
            throw new \Exception('Cannot set value in memcached (' . $key . ')');
        }
    }

    /**
     * Retrieves a value from the cache.
     * 
     * @param string $key The key under which the value is stored.
     * @return mixed|null Returns the stored value or null if not found or expired.
     */
    public function get(string $key)
    {
        $instance = $this->getInstance();
        $value = $instance->get(md5($key));
        if ($value !== false) {
            try {
                $value = unserialize(gzuncompress($value));
                if (is_array($value) && $value[0] === $key) {
                    return $value[1];
                }
            } catch (\Exception $e) {
                
            }
            return null;
        } elseif ($instance->getResultCode() === \Memcached::RES_NOTFOUND) {
            return null;
        } else {
            throw new \Exception('Cannot get value from memcached (' . $key . ')');
        }
    }

    /**
     * Deletes a value from the cache.
     * 
     * @param string $key The key under which the value is stored.
     * @return void No value is returned.
     */
    public function delete(string $key): void
    {
        $instance = $this->getInstance();
        $result = $instance->delete(md5($key));
        if ($result === true || $instance->getResultCode() === \Memcached::RES_NOTFOUND) {
            
        } else {
            throw new \Exception('Cannot delete value from memcached (' . $key . ')');
        }
    }

    /**
     * Stores multiple values in the cache.
     * 
     * @param array $items An array of key/value pairs to store in the cache.
     * @param int $ttl Number of seconds to store values in the cache.
     * @return void No value is returned.
     */
    public function setMultiple(array $items, int $ttl = null): void
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * Retrieves multiple values from the cache.
     * 
     * @param array $keys The keys under which the values are stored.
     * @return array An array (key/value) of found items.
     */
    public function getMultiple(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }
        return $results;
    }

    /**
     * Deletes multiple values from the cache.
     * 
     * @param array $keys The keys under which the values are stored.
     */
    public function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    /**
     * Deletes all values from the cache.
     */
    public function clear(): void
    {
        $instance = $this->getInstance();
        $result = $instance->flush();
        if ($result !== true) {
            throw new \Exception('Cannot clear all values from memcached');
        }
    }

}
