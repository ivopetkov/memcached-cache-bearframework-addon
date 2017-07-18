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
        $ttlToSet = $ttl !== null && $ttl > 0 ? time() + $ttl : 0;
        $valueToSet = gzcompress(serialize([$key, $value]));
        $valueLimit = 900000;
        if (strlen($valueToSet) > $valueLimit) {
            $partsToSet = str_split($valueToSet, 900000);
            $partsID = md5(uniqid());
            $partsCount = sizeof($partsToSet);
            foreach ($partsToSet as $i => $partToSet) {
                if ($i === 0) {
                    $keyToSet = md5($key);
                } else {
                    $keyToSet = md5(md5($key) . md5($partsID) . md5($i));
                }
                $result = $instance->set($keyToSet, 'multipart:' . $partsCount . ':' . $partsID . ':' . $i . ':' . $partToSet, $ttlToSet);
            }
        } else {
            $result = $instance->set(md5($key), $valueToSet, $ttlToSet);
        }
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
            $partsData = [];
            if (substr($value, 0, 10) === 'multipart:') {
                $partsData = explode(':', $value, 5);
                $partsCount = $partsData[1];
                if (!is_numeric($partsCount)) {
                    return null;
                }
                $partsID = $partsData[2];
                if (strlen($partsID) !== 32) {
                    return null;
                }
                if ($partsData[2] !== '0') {
                    return;
                }
                $partsData[0] = $partsData[4];
                for ($i = 1; $i <= $partsCount; $i++) {
                    $partKey = md5(md5($key) . md5($partsID) . md5($i));
                    $partValue = $instance->get($partKey);
                    if ($value !== false) {
                        $expectedPartPrefix = 'multipart:' . $partsCount . ':' . $partsID . ':' . $i . ':';
                        if (substr($partValue, 0, strlen($expectedPartPrefix)) === $expectedPartPrefix) {
                            $partsData[$i] = substr($partValue, strlen($expectedPartPrefix));
                            continue;
                        }
                    }
                    return false;
                }
                if (sizeof($partsData) === $partsCount) {
                    $value = implode('', $partsData);
                } else {
                    return false;
                }
            }
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
