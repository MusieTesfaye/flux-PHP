<?php
/**
 * Flux Autoloader
 * 
 * A simple PSR-4 compatible autoloader for the Flux framework.
 */

declare(strict_types=1);

namespace Flux\System;

class Autoloader
{
    /**
     * @var array Namespace prefixes and their base directories
     */
    private static array $prefixes = [];

    /**
     * Register the autoloader
     */
    public static function register(): void
    {
        // Register default namespaces
        self::addNamespace('Flux\\', FLUX_ROOT . '/system');
        self::addNamespace('App\\', FLUX_ROOT . '/app');
        
        // Register the autoloader
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Add a namespace prefix to the autoloader
     */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        self::$prefixes[$prefix] = $baseDir;
    }

    /**
     * Load a class based on its fully qualified name
     */
    public static function loadClass(string $class): bool
    {
        // Loop through registered prefixes
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }
        }
        
        return false;
    }
}

// Register the autoloader
Autoloader::register();

