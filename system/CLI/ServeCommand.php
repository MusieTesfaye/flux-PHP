<?php

declare(strict_types=1);

namespace Flux\CLI;

class ServeCommand
{
    /**
     * Execute the command
     */
    public static function execute(array $args): void
    {
        $forge = new Forge();
        
        // Parse options
        $host = '127.0.0.1';
        $port = 8000;
        
        foreach ($args as $arg) {
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            } elseif (strpos($arg, '--port=') === 0) {
                $port = (int) substr($arg, 7);
            }
        }
        
        // Check if the public directory exists
        $publicDir = FLUX_ROOT . '/public';
        
        if (!is_dir($publicDir)) {
            $forge->error('Public directory not found');
            return;
        }
        
        // Start the built-in web server
        $forge->info("Starting development server at http://$host:$port");
        $forge->info("Press Ctrl+C to stop the server");
        
        // Change to the public directory
        chdir($publicDir);
        
        // Start the server
        passthru("php -S $host:$port");
    }
}

