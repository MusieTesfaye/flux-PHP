<?php

declare(strict_types=1);

namespace Flux\Cache\Drivers;

interface CacheDriverInterface
{
    /**
     * Get a value from the cache
     */
    public function get(string $key, $default = null);
    
    /**
     * Set a value in the cache
     */
    public function set(string $key, $value, int $ttl = null): bool;
    
    /**
     * Check if a key exists in the cache
     */
    public function has(string $key): bool;
    
    /**
     * Remove a value from the cache
     */
    public function delete(string $key): bool;
    
    /**
     * Clear the cache
     */
    public function clear(): bool;
    
    /**
     * Get multiple values from the cache
     */
    public function getMultiple(array $keys, $default = null): array;
    
    /**
     * Set multiple values in the cache
     */
    public function setMultiple(array $values, int $ttl = null): bool;
    
    /**
     * Remove multiple values from the cache
     */
    public function deleteMultiple(array $keys): bool;
}

