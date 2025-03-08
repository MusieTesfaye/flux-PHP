<?php

declare(strict_types=1);

namespace Flux\Cache\Drivers;

use Flux\Config\ConfigManager;

class FileDriver implements CacheDriverInterface
{
    /**
     * @var string The cache directory
     */
    private string $directory;
    
    /**
     * @var string The cache prefix
     */
    private string $prefix;
    
    /**
     * Create a new file driver instance
     */
    public function __construct(ConfigManager $config)
    {
        // Get the cache directory
        $this->directory = $config->get('cache.stores.file.path', FLUX_ROOT . '/storage/cache');
        
        // Create the directory if it doesn't exist
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
        
        // Set the prefix
        $this->prefix = $config->get('cache.prefix', 'flux_');
    }
    
    /**
     * Get a value from the cache
     */
    public function get(string $key, $default = null)
    {
        $path = $this->getPath($key);
        
        if (!file_exists($path)) {
            return $default;
        }
        
        $data = $this->readFile($path);
        
        if ($data === null) {
            return $default;
        }
        
        // Check if the cache has expired
        if ($data['expiration'] !== null && $data['expiration'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Set a value in the cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $path = $this->getPath($key);
        
        $data = [
            'value' => $value,
            'expiration' => $ttl !== null ? time() + $ttl : null,
        ];
        
        return $this->writeFile($path, $data);
    }
    
    /**
     * Check if a key exists in the cache
     */
    public function has(string $key): bool
    {
        $path = $this->getPath($key);
        
        if (!file_exists($path)) {
            return false;
        }
        
        $data = $this->readFile($path);
        
        if ($data === null) {
            return false;
        }
        
        // Check if the cache has expired
        if ($data['expiration'] !== null && $data['expiration'] < time()) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Remove a value from the cache
     */
    public function delete(string $key): bool
    {
        $path = $this->getPath($key);
        
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return true;
    }
    
    /**
     * Clear the cache
     */
    public function clear(): bool
    {
        $files = glob($this->directory . '/' . $this->prefix . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Get multiple values from the cache
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
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
        $result = true;
        
        foreach ($keys as $key) {
            $result = $result && $this->delete($key);
        }
        
        return $result;
    }
    
    /**
     * Get the file path for a key
     */
    private function getPath(string $key): string
    {
        return $this->directory . '/' . $this->prefix . md5($key);
    }
    
    /**
     * Read a file
     */
    private function readFile(string $path)
    {
        $content = file_get_contents($path);
        
        if ($content === false) {
            return null;
        }
        
        return unserialize($content);
    }
    
    /**
     * Write a file
     */
    private function writeFile(string $path, $data): bool
    {
        $content = serialize($data);
        
        return file_put_contents($path, $content, LOCK_EX) !== false;
    }
}

