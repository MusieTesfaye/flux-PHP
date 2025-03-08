<?php

declare(strict_types=1);

namespace Flux\CLI;

class NewCommand
{
    /**
     * Execute the command
     */
    public static function execute(array $args): void
    {
        $forge = new Forge();
        
        if (empty($args)) {
            $forge->error('Please provide a project name');
            return;
        }
        
        $projectName = $args[0];
        $projectDir = getcwd() . '/' . $projectName;
        
        // Check if the directory already exists
        if (is_dir($projectDir)) {
            $forge->error("Directory already exists: $projectName");
            return;
        }
        
        // Create the project directory
        if (!mkdir($projectDir, 0755, true)) {
            $forge->error("Failed to create directory: $projectName");
            return;
        }
        
        // Create the project using Composer
        $composerCommand = "composer create-project flux/framework $projectName --prefer-dist";
        
        $forge->info("Creating project via Composer: $projectName");
        $forge->info("Running: $composerCommand");
        
        // Execute the Composer command
        passthru($composerCommand);
        
        $forge->success("Project created successfully: $projectName");
        $forge->info("Run 'cd $projectName && composer serve' to start the development server");
    }
    
    /**
     * Create the project structure
     */
    private static function createProjectStructure(string $projectDir): void
    {
        // Create directories
        mkdir($projectDir . '/app', 0755, true);
        mkdir($projectDir . '/app/Controllers', 0755, true);
        mkdir($projectDir . '/app/Models', 0755, true);
        mkdir($projectDir . '/app/Views', 0755, true);
        mkdir($projectDir . '/app/Modules', 0755, true);
        mkdir($projectDir . '/app/Middleware', 0755, true);
        mkdir($projectDir . '/config', 0755, true);
        mkdir($projectDir . '/public', 0755, true);
        mkdir($projectDir . '/system', 0755, true);
        
        // Create basic files
        self::createFile($projectDir . '/public/index.php', self::getIndexFileContent());
        self::createFile($projectDir . '/app/routes.php', self::getRoutesFileContent());
        self::createFile($projectDir . '/app/Controllers/HomeController.php', self::getHomeControllerContent());
        self::createFile($projectDir . '/app/Views/home.php', self::getHomeViewContent());
        self::createFile($projectDir . '/config/app.php', self::getAppConfigContent());
        self::createFile($projectDir . '/config/database.php', self::getDatabaseConfigContent());
        self::createFile($projectDir . '/forge', self::getForgeFileContent());
        
        // Make the forge file executable
        chmod($projectDir . '/forge', 0755);
    }
    
    /**
     * Create a file with content
     */
    private static function createFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }
    
    /**
     * Get the content for the index.php file
     */
    private static function getIndexFileContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Flux - A modern, lightweight PHP framework
 * 
 * This is the entry point for the Flux framework.
 * It bootstraps the application and handles the request lifecycle.
 */

declare(strict_types=1);

// Define the application root directory
define('FLUX_ROOT', dirname(__DIR__));

// Load the autoloader
require_once FLUX_ROOT . '/system/Autoloader.php';

// Bootstrap the application
$app = new \Flux\Core\Application();

// Run the application
$app->run();
PHP;
    }
    
    /**
     * Get the content for the routes.php file
     */
    private static function getRoutesFileContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Application Routes
 * 
 * Define your application routes here.
 */

declare(strict_types=1);

use Flux\Http\Request;
use Flux\Http\Response;
use App\Controllers\HomeController;

// Get the router instance
$router = $app->getRouter();

// Define routes
$router->get('/', [HomeController::class, 'index']);

// API routes
$router->group(['prefix' => 'api'], function($router) {
    $router->get('/users', function(Request $request) {
        return Response::json([
            'users' => [
                ['id' => 1, 'name' => 'John Doe'],
                ['id' => 2, 'name' => 'Jane Smith'],
            ]
        ]);
    });
});
PHP;
    }
    
    /**
     * Get the content for the HomeController.php file
     */
    private static function getHomeControllerContent(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Controllers;

use Flux\Http\Request;
use Flux\Http\Response;
use Flux\View\View;

class HomeController
{
    /**
     * Display the home page
     */
    public function index(Request $request)
    {
        return View::make('home', [
            'title' => 'Welcome to Flux',
            'message' => 'A modern, lightweight PHP framework',
        ]);
    }
}
PHP;
    }
    
    /**
     * Get the content for the home.php view file
     */
    private static function getHomeViewContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .content {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 0.5rem;
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $title ?></h1>
        <p><?= $message ?></p>
    </div>
    
    <div class="content">
        <h2>Getting Started</h2>
        <p>Welcome to your new Flux application! Here are a few links to help you get started:</p>
        <ul>
            <li><a href="https://github.com/flux-framework/docs">Documentation</a></li>
            <li><a href="https://github.com/flux-framework/flux">GitHub Repository</a></li>
        </ul>
    </div>
    
    <div class="footer">
        <p>Powered by Flux Framework</p>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get the content for the app.php config file
     */
    private static function getAppConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Application Configuration
 */

return [
    // Application name
    'name' => 'Flux Application',
    
    // Application environment
    'environment' => 'development',
    
    // Debug mode
    'debug' => true,
    
    // URL
    'url' => 'http://localhost:8000',
    
    // Timezone
    'timezone' => 'UTC',
    
    // Middleware
    'middleware' => [
        // Global middleware
        'global' => [
            // Add global middleware classes here
        ],
        
        // Route middleware
        'route' => [
            'auth' => \App\Middleware\AuthMiddleware::class,
        ],
    ],
];
PHP;
    }
    
    /**
     * Get the content for the database.php config file
     */
    private static function getDatabaseConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Database Configuration
 */

return [
    // Default database connection
    'default' => 'mysql',
    
    // Database connections
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'flux',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'flux',
            'username' => 'postgres',
            'password' => '',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => FLUX_ROOT . '/database/database.sqlite',
        ],
    ],
];
PHP;
    }
    
    /**
     * Get the content for the forge file
     */
    private static function getForgeFileContent(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

/**
 * Flux Framework CLI Tool
 */

declare(strict_types=1);

// Define the application root directory
define('FLUX_ROOT', __DIR__);

// Load the autoloader
require_once FLUX_ROOT . '/system/Autoloader.php';

// Create and run the CLI tool
$forge = new \Flux\CLI\Forge($argv);
$forge->run();
PHP;
    }
}

