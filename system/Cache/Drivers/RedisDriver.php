<?php

declare(strict_types=1);

namespace Flux\Cache\Drivers;

use Flux\Config\ConfigManager;

class RedisDriver implements CacheDriverInterface
{
    /**
     * @var \Redis The Redis instance
     */
    private \Redis $redis;
    
    /**
     * @var string The cache prefix
     */
    private string $prefix;
    
    /**
     * Create a new Redis driver instance
     */
    public function __construct(ConfigManager $config)
    {
        $this->redis = new \Redis();
        
        // Get the Redis configuration
        $host = $config->get('cache.stores.redis.host', '127.0.0.1');
        $port = $config->get('cache.stores.redis.port', 6379);
        $password = $config->get('cache.stores.redis.password', null);
        
        // Connect to Redis
        $this->redis->connect($host, $port);
        
        // Authenticate if a password is provided
        if ($password !== null) {
            $this->redis->auth($password);
        }
        
        // Set the prefix
        $this->prefix = $config->get('cache.prefix', 'flux_');
    }
    
    /**
     * Get a value from the cache
     */
    public function get(string $key, $default = null)
    {
        $value = $this->redis->get($this->prefix . $key);
        
        if ($value === false) {
            return $default;
        }
        
        return unserialize($value);
    }
    
    /**
     * Set a value in the cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $serialized = serialize($value);
        
        if ($ttl === null) {
            return $this->redis->set($this->prefix . $key, $serialized);
        }
        
        return $this->redis->setex($this->prefix . $key, $ttl, $serialized);
    }
    
    /**
     * Check if a key exists in the cache
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($this->prefix . $key) > 0;
    }
    
    /**
     * Remove a value from the cache
     */
    public function delete(string $key): bool
    {
        return $this->redis->del($this->prefix . $key) > 0;
    }
    
    /**
     * Clear the cache
     */
    public function clear(): bool
    {
        $keys = $this->redis->keys($this->prefix . '*');
        
        if (empty($keys)) {
            return true;
        }
        
        return $this->redis->del($keys) > 0;
    }
    
    /**
     * Get multiple values from the cache
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        $values = $this->redis->mGet($prefixedKeys);
        
        $result = [];
        
        foreach ($keys as $i => $key) {
            $value = $values[$i];
            $result[$key] = $value !== false ? unserialize($value) : $default;
        }
        
        return $result;
    }
    
    /**
     * Set multiple values in the cache
     */
    public function setMultiple(array $values, int $ttl = null): bool
    {
        $result = true;
        
        foreach ($values as $key => $value) {
            $result = $result && $this->set($key, $value, $ttl);
        }
        
        return $result;
    }
    
    /**
     * Remove multiple values from the cache
     */
    public function deleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        return $this->redis->del($prefixedKeys) > 0;
    }
}

