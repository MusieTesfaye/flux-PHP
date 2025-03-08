<?php

declare(strict_types=1);

namespace Flux\Cache;

use Flux\Config\ConfigManager;
use Flux\Cache\Drivers\MemcachedDriver;
use Flux\Cache\Drivers\RedisDriver;
use Flux\Cache\Drivers\FileDriver;
use Flux\Cache\Drivers\CacheDriverInterface;

class CacheManager
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var array Cache drivers
     */
    private array $drivers = [];
    
    /**
     * @var CacheDriverInterface|null The default driver
     */
    private ?CacheDriverInterface $defaultDriver = null;
    
    /**
     * Initialize the cache manager
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        
        // Register the file driver
        $this->registerDriver('file', new FileDriver($config));
        
        // Register the Memcached driver if available
        if (class_exists('Memcached')) {
            $this->registerDriver('memcached', new MemcachedDriver($config));
        }
        
        // Register the Redis driver if available
        if (class_exists('Redis')) {
            $this->registerDriver('redis', new RedisDriver($config));
        }
        
        // Set the default driver
        $defaultDriver = $this->config->get('cache.default', 'memcached');
        
        // Fall back to file driver if the default is not available
        if (!isset($this->drivers[$defaultDriver])) {
            $defaultDriver = 'file';
        }
        
        $this->defaultDriver = $this->drivers[$defaultDriver];
    }
    
    /**
     * Register a cache driver
     */
    public function registerDriver(string $name, CacheDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }
    
    /**
     * Get a cache driver
     */
    public function driver(string $name = null): CacheDriverInterface
    {
        if ($name === null) {
            return $this->defaultDriver;
        }
        
        if (!isset($this->drivers[$name])) {
            throw new \InvalidArgumentException("Cache driver not found: $name");
        }
        
        return $this->drivers[$name];
    }
    
    /**
     * Get a value from the cache
     */
    public function get(string $key, $default = null)
    {
        return $this->defaultDriver->get($key, $default);
    }
    
    /**
     * Set a value in the cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        return $this->defaultDriver->set($key, $value, $ttl);
    }
    
    /**
     * Check if a key exists in the cache
     */
    public function has(string $key): bool
    {
        return $this->defaultDriver->has($key);
    }
    
    /**
     * Remove a value from the cache
     */
    public function delete(string $key): bool
    {
        return $this->defaultDriver->delete($key);
    }
    
    /**
     * Clear the cache
     */
    public function clear(): bool
    {
        return $this->defaultDriver->clear();
    }
    
    /**
     * Get multiple values from the cache
     */
    public function getMultiple(array $keys, $default = null): array
    {
        return $this->defaultDriver->getMultiple($keys, $default);
    }
    
    /**
     * Set multiple values in the cache
     */
    public function setMultiple(array $values, int $ttl = null): bool
    {
        return $this->defaultDriver->setMultiple($values, $ttl);
    }
    
    /**
     * Remove multiple values from the cache
     */
    public function deleteMultiple(array $keys): bool
    {
        return $this->defaultDriver->deleteMultiple($keys);
    }
    
    /**
     * Remember a value in the cache
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}

