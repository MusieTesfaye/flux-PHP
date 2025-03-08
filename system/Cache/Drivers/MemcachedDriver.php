<?php

declare(strict_types=1);

namespace Flux\Cache\Drivers;

use Flux\Config\ConfigManager;

class MemcachedDriver implements CacheDriverInterface
{
    /**
     * @var \Memcached The Memcached instance
     */
    private \Memcached $memcached;
    
    /**
     * @var string The cache prefix
     */
    private string $prefix;
    
    /**
     * Create a new Memcached driver instance
     */
    public function __construct(ConfigManager $config)
    {
        $this->memcached = new \Memcached();
        
        // Get the Memcached configuration
        $servers = $config->get('cache.stores.memcached.servers', [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
        ]);
        
        // Add the servers
        foreach ($servers as $server) {
            $this->memcached->addServer(
                $server['host'],
                $server['port'],
                $server['weight'] ?? 100
            );
        }
        
        // Set the prefix
        $this->prefix = $config->get('cache.prefix', 'flux_');
    }
    
    /**
     * Get a value from the cache
     */
    public function get(string $key, $default = null)
    {
        $value = $this->memcached->get($this->prefix . $key);
        
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return $default;
        }
        
        return $value;
    }
    
    /**
     * Set a value in the cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        return $this->memcached->set(
            $this->prefix . $key,
            $value,
            $ttl ?? 0
        );
    }
    
    /**
     * Check if a key exists in the cache
     */
    public function has(string $key): bool
    {
        $this->memcached->get($this->prefix . $key);
        
        return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
    }
    
    /**
     * Remove a value from the cache
     */
    public function delete(string $key): bool
    {
        return $this->memcached->delete($this->prefix . $key);
    }
    
    /**
     * Clear the cache
     */
    public function clear(): bool
    {
        return $this->memcached->flush();
    }
    
    /**
     * Get multiple values from the cache
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        $values = $this->memcached->getMulti($prefixedKeys);
        
        if ($values === false) {
            return array_fill_keys($keys, $default);
        }
        
        $result = [];
        
        foreach ($keys as $key) {
            $prefixedKey = $this->prefix . $key;
            $result[$key] = $values[$prefixedKey] ?? $default;
        }
        
        return $result;
    }
    
    /**
     * Set multiple values in the cache
     */
    public function setMultiple(array $values, int $ttl = null): bool
    {
        $prefixedValues = [];
        
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }
        
        return $this->memcached->setMulti($prefixedValues, $ttl ?? 0);
    }
    
    /**
     * Remove multiple values from the cache
     */
    public function deleteMultiple(array $keys): bool
    {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        $result = $this->memcached->deleteMulti($prefixedKeys);
        
        return !in_array(false, $result, true);
    }
}

