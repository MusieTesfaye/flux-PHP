<?php

declare(strict_types=1);

namespace Flux\Environment;

class EnvLoader
{
    /**
     * Load environment variables from .env file
     */
    public static function load(string $rootDir): void
    {
        $envFile = $rootDir . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse the line
            list($name, $value) = self::parseLine($line);
            
            if (!empty($name)) {
                // Set the environment variable
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
    
    /**
     * Parse a line from the .env file
     */
    private static function parseLine(string $line): array
    {
        // Check if the line contains an equals sign
        if (strpos($line, '=') === false) {
            return [null, null];
        }
        
        // Split the line into name and value
        list($name, $value) = explode('=', $line, 2);
        
        // Trim the name and value
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes from the value
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }
        
        return [$name, $value];
    }
    
    /**
     * Get an environment variable
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}

