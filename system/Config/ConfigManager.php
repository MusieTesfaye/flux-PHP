<?php

declare(strict_types=1);

namespace Flux\Config;

use Flux\Environment\EnvLoader;

class ConfigManager
{
    /**
     * @var array Loaded configuration
     */
    private array $config = [];
    
    /**
     * Initialize the configuration manager
     */
    public function __construct()
    {
        $this->loadConfigFiles();
    }
    
    /**
     * Load all configuration files
     */
    private function loadConfigFiles(): void
    {
        $configDir = FLUX_ROOT . '/config';
        
        if (!is_dir($configDir)) {
            return;
        }
        
        $files = glob($configDir . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $this->config[$name] = require $file;
        }
    }
    
    /**
     * Get a configuration value
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $config = $this->config;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            
            $config = $config[$part];
        }
        
        return $config;
    }
    
    /**
     * Set a configuration value
     */
    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $config = &$this->config;
        
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                
                $config = &$config[$part];
            }
        }
    }
    
    /**
     * Check if a configuration value exists
     */
    public function has(string $key): bool
    {
        $parts = explode('.', $key);
        $config = $this->config;
        
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return false;
            }
            
            $config = $config[$part];
        }
        
        return true;
    }
}

