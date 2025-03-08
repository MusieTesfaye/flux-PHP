<?php

declare(strict_types=1);

namespace Flux\CLI;

class InitCommand
{
    /**
     * @var Forge The Forge instance
     */
    private Forge $forge;
    
    /**
     * @var array User choices
     */
    private array $choices = [
        'auth' => 'native',
        'darkMode' => true,
    ];
    
    /**
     * Execute the command
     */
    public static function execute(array $args): void
    {
        $instance = new self();
        $instance->run($args);
    }
    
    /**
     * Run the command
     */
    private function run(array $args): void
    {
        $this->forge = new Forge();
        
        // Process command line arguments
        $this->processArguments($args);
        
        // If not using command line arguments, prompt for choices
        if (!$this->hasArgument($args, '--no-interaction')) {
            $this->promptForChoices();
        }
        
        // Create the project structure
        $this->createProjectStructure(FLUX_ROOT);
        
        $this->forge->success("Project initialized successfully");
        $this->forge->info("Run 'composer serve' or 'php forge serve' to start the development server");
    }
    
    /**
     * Process command line arguments
     */
    private function processArguments(array $args): void
    {
        foreach ($args as $arg) {
            if (strpos($arg, '--auth=') === 0) {
                $this->choices['auth'] = substr($arg, 7);
            } elseif ($arg === '--no-dark-mode') {
                $this->choices['darkMode'] = false;
            }
        }
    }
    
    /**
     * Check if an argument exists
     */
    private function hasArgument(array $args, string $name): bool
    {
        return in_array($name, $args);
    }
    
    /**
     * Prompt for user choices
     */
    private function promptForChoices(): void
    {
        // Prompt for authentication
        $this->forge->info("Select authentication provider:");
        $this->forge->info("1. Native (username/password)");
        $this->forge->info("2. Firebase");
        $this->forge->info("3. WorkOS");
        $this->forge->info("4. All providers");
        
        $authChoice = $this->prompt("Enter your choice (1-4): ");
        
        switch ($authChoice) {
            case '1':
                $this->choices['auth'] = 'native';
                break;
            case '2':
                $this->choices['auth'] = 'firebase';
                break;
            case '3':
                $this->choices['auth'] = 'workos';
                break;
            case '4':
                $this->choices['auth'] = 'all';
                break;
            default:
                $this->choices['auth'] = 'native';
                break;
        }
        
        // Prompt for dark mode
        $darkModeChoice = $this->prompt("Enable dark mode support? (y/n): ");
        $this->choices['darkMode'] = strtolower($darkModeChoice) === 'y';
    }
    
    /**
     * Prompt for user input
     */
    private function prompt(string $message): string
    {
        $this->forge->info($message);
        return trim(fgets(STDIN));
    }
    
    /**
     * Create the project structure
     */
    private function createProjectStructure(string $projectDir): void
    {
        // Create directories if they don't exist
        $this->createDirectoryIfNotExists($projectDir . '/app');
        $this->createDirectoryIfNotExists($projectDir . '/app/Controllers');
        $this->createDirectoryIfNotExists($projectDir . '/app/Models');
        $this->createDirectoryIfNotExists($projectDir . '/app/Views');
        $this->createDirectoryIfNotExists($projectDir . '/app/Views/components');
        $this->createDirectoryIfNotExists($projectDir . '/app/Views/layouts');
        $this->createDirectoryIfNotExists($projectDir . '/app/Modules');
        $this->createDirectoryIfNotExists($projectDir . '/app/Middleware');
        $this->createDirectoryIfNotExists($projectDir . '/config');
        $this->createDirectoryIfNotExists($projectDir . '/public');
        $this->createDirectoryIfNotExists($projectDir . '/public/css');
        $this->createDirectoryIfNotExists($projectDir . '/public/js');
        $this->createDirectoryIfNotExists($projectDir . '/storage');
        $this->createDirectoryIfNotExists($projectDir . '/storage/cache');
        $this->createDirectoryIfNotExists($projectDir . '/storage/logs');
        
        // Create basic files if they don't exist
        $this->createFileIfNotExists($projectDir . '/public/index.php', $this->getIndexFileContent());
        $this->createFileIfNotExists($projectDir . '/app/routes.php', $this->getRoutesFileContent());
        $this->createFileIfNotExists($projectDir . '/app/Controllers/HomeController.php', $this->getHomeControllerContent());
        $this->createFileIfNotExists($projectDir . '/app/Views/home.flux', $this->getHomeViewContent());
        $this->createFileIfNotExists($projectDir . '/app/Views/layouts/app.flux', $this->getAppLayoutContent());
        $this->createFileIfNotExists($projectDir . '/config/app.php', $this->getAppConfigContent());
        $this->createFileIfNotExists($projectDir . '/config/database.php', $this->getDatabaseConfigContent());
        $this->createFileIfNotExists($projectDir . '/config/auth.php', $this->getAuthConfigContent());
        $this->createFileIfNotExists($projectDir . '/config/cache.php', $this->getCacheConfigContent());
        $this->createFileIfNotExists($projectDir . '/config/session.php', $this->getSessionConfigContent());
        $this->createFileIfNotExists($projectDir . '/.env.example', $this->getEnvExampleContent());
        $this->createFileIfNotExists($projectDir . '/tailwind.config.js', $this->getTailwindConfigContent());
        $this->createFileIfNotExists($projectDir . '/public/css/app.css', $this->getAppCssContent());
        
        // Create authentication files if needed
        if (in_array($this->choices['auth'], ['native', 'all'])) {
            $this->createFileIfNotExists($projectDir . '/app/Controllers/AuthController.php', $this->getAuthControllerContent());
            $this->createFileIfNotExists($projectDir . '/app/Views/auth/login.flux', $this->getLoginViewContent());
            $this->createFileIfNotExists($projectDir . '/app/Views/auth/register.flux', $this->getRegisterViewContent());
        }
        
        // Create dark mode toggle component if needed
        if ($this->choices['darkMode']) {
            $this->createFileIfNotExists($projectDir . '/app/Views/components/dark-mode-toggle.flux', $this->getDarkModeToggleContent());
        }
    }
    
    /**
     * Create a directory if it doesn't exist
     */
    private function createDirectoryIfNotExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Create a file if it doesn't exist
     */
    private function createFileIfNotExists(string $path, string $content): void
    {
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }
    
    /**
     * Get the content for the index.php file
     */
    private function getIndexFileContent(): string
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

// Check if we're running from the Composer autoloader
if (file_exists(FLUX_ROOT . '/vendor/autoload.php')) {
   require FLUX_ROOT . '/vendor/autoload.php';
} else {
   // Fall back to the system autoloader
   require_once FLUX_ROOT . '/system/Autoloader.php';
}

// Bootstrap the application
$app = new \Flux\Core\Application();

// Run the application
$app->run();
PHP;
    }
    
    /**
     * Get the content for the routes.php file
     */
    private function getRoutesFileContent(): string
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
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

// Get the router instance
$router = $app->getRouter();

// Define routes
$router->get('/', [HomeController::class, 'index']);

// Authentication routes
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegistrationForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Protected routes
$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/dashboard', function(Request $request) {
        return view('dashboard', [
            'title' => 'Dashboard',
            'user' => app()->getAuth()->user(),
        ]);
    });
});

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
    
    // Protected API routes
    $router->group(['middleware' => [AuthMiddleware::class]], function($router) {
        $router->get('/user', function(Request $request) {
            return Response::json([
                'user' => app()->getAuth()->user()->toArray(),
            ]);
        });
    });
});

// Helper functions
if (!function_exists('app')) {
    function app() {
        return \Flux\Core\Application::getInstance();
    }
}

if (!function_exists('view')) {
    function view(string $path, array $data = []) {
        return \Flux\View\View::make($path, $data);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        $token = app()->getSession()->get('_token');
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            app()->getSession()->set('_token', $token);
        }
        
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}
PHP;
    }
    
    /**
     * Get the content for the HomeController.php file
     */
    private function getHomeControllerContent(): string
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
     * Get the content for the home.flux view file
     */
    private function getHomeViewContent(): string
    {
        return <<<'HTML'
@extends('layouts.app')

@section('title', 'Welcome to Flux')

@section('content')
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl sm:tracking-tight lg:text-6xl">
                Welcome to Flux
            </h1>
            <p class="mt-3 max-w-md mx-auto text-base text-gray-500 dark:text-gray-400 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                A modern, lightweight PHP framework
            </p>
        </div>
        
        <div class="mt-10">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Getting Started</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Welcome to your new Flux application! Here are a few links to help you get started:
                    </p>
                    <ul class="mt-3 list-disc list-inside text-sm text-gray-500 dark:text-gray-400">
                        <li>Edit this page in <code class="font-mono text-sm">app/Views/home.flux</code></li>
                        <li>Manage routes in <code class="font-mono text-sm">app/routes.php</code></li>
                        <li>Configure your application in the <code class="font-mono text-sm">config</code> directory</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
HTML;
    }
    
    /**
     * Get the content for the app.flux layout file
     */
    private function getAppLayoutContent(): string
    {
        $darkModeToggle = $this->choices['darkMode'] ? '<x-dark-mode-toggle />' : '';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Flux Framework')</title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true' || 
            (localStorage.getItem('darkMode') === null && 
             window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-gray-900 dark:text-white">Flux</a>
                    </div>
                    <nav class="ml-6 flex space-x-4">
                        <a href="/" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 dark:text-white">Home</a>
                        @auth
                            <a href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 dark:text-white">Dashboard</a>
                        @endauth
                    </nav>
                </div>
                <div class="flex items-center">
                    $darkModeToggle
                    
                    @auth
                        <div class="ml-4 flex items-center">
                            <span class="text-gray-900 dark:text-white mr-2">{{ auth()->user()->getName() }}</span>
                            <form action="/logout" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 dark:text-white">Logout</button>
                            </form>
                        </div>
                    @else
                        <div class="ml-4 flex items-center space-x-2">
                            <a href="/login" class="px-3 py-2 rounded-md text-sm font-medium text-gray-900 dark:text-white">Login</a>
                            <a href="/register" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Register</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>
    
    <main>
        @yield('content')
    </main>
    
    <footer class="bg-white dark:bg-gray-800 shadow mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
                &copy; {{ date('Y') }} Flux Framework
            </p>
        </div>
    </footer>
</body>
</html>
HTML;
    }
    
    /**
     * Get the content for the app.php config file
     */
    private function getAppConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
* Application Configuration
*/

return [
    // Application name
    'name' => env('APP_NAME', 'Flux Application'),
    
    // Application environment
    'environment' => env('APP_ENV', 'development'),
    
    // Debug mode
    'debug' => env('APP_DEBUG', true),
    
    // URL
    'url' => env('APP_URL', 'http://localhost:8000'),
    
    // Timezone
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    
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

/**
 * Get an environment variable
 */
function env(string $key, $default = null) {
    return \Flux\Environment\EnvLoader::get($key, $default);
}
PHP;
    }
    
    /**
     * Get the content for the database.php config file
     */
    private function getDatabaseConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
* Database Configuration
*/

return [
    // Default database connection
    'default' => env('DB_CONNECTION', 'mysql'),
    
    // Database connections
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'flux'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
        ],
        
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'flux'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', FLUX_ROOT . '/database/database.sqlite'),
        ],
    ],
];
PHP;
    }
    
    /**
     * Get the content for the auth.php config file
     */
    private function getAuthConfigContent(): string
    {
        $enableFirebase = $this->choices['auth'] === 'firebase' || $this->choices['auth'] === 'all';
        $enableWorkOS = $this->choices['auth'] === 'workos' || $this->choices['auth'] === 'all';
        
        $firebaseConfig = $enableFirebase ? "'firebase' => [
            'enabled' => true,
        ]," : "";
        
        $workosConfig = $enableWorkOS ? "'workos' => [
            'enabled' => true,
            'redirect_uri' => env('APP_URL') . '/auth/workos/callback',
        ]," : "";
        
        return <<<PHP
<?php

/**
 * Authentication Configuration
 */

return [
    // Default authentication provider
    'default' => '{$this->choices['auth']}',
    
    // Authentication providers
    'providers' => [
        'native' => [
            'model' => \App\Models\User::class,
        ],
        
        $firebaseConfig
        
        $workosConfig
    ],
];
PHP;
    }
    
    /**
     * Get the content for the cache.php config file
     */
    private function getCacheConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Cache Configuration
 */

return [
    // Default cache store
    'default' => env('CACHE_DRIVER', 'memcached'),
    
    // Cache prefix
    'prefix' => env('CACHE_PREFIX', 'flux_'),
    
    // Cache stores
    'stores' => [
        'memcached' => [
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
        
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
        ],
        
        'file' => [
            'path' => FLUX_ROOT . '/storage/cache',
        ],
    ],
];
PHP;
    }
    
    /**
     * Get the content for the session.php config file
     */
    private function getSessionConfigContent(): string
    {
        return <<<'PHP'
<?php

/**
 * Session Configuration
 */

return [
    // Session name
    'name' => env('SESSION_NAME', 'flux_session'),
    
    // Session lifetime (in minutes)
    'lifetime' => env('SESSION_LIFETIME', 120),
    
    // Session path
    'path' => '/',
    
    // Session domain
    'domain' => env('SESSION_DOMAIN', null),
    
    // Secure cookies
    'secure' => env('SESSION_SECURE', false),
    
    // HTTP only cookies
    'http_only' => true,
    
    // Same site cookies
    'same_site' => 'lax',
];
PHP;
    }
    
    /**
     * Get the content for the .env.example file
     */
    private function getEnvExampleContent(): string
    {
        $firebaseEnv = $this->choices['auth'] === 'firebase' || $this->choices['auth'] === 'all' ? 
            "# Firebase Authentication
FIREBASE_API_KEY=
FIREBASE_PROJECT_ID=
FIREBASE_AUTH_DOMAIN=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_APP_ID=" : "";
        
        $workosEnv = $this->choices['auth'] === 'workos' || $this->choices['auth'] === 'all' ? 
            "# WorkOS Authentication
WORKOS_API_KEY=
WORKOS_CLIENT_ID=
WORKOS_REDIRECT_URI=http://localhost:8000/auth/workos/callback" : "";
        
        return <<<ENV
# Application
APP_NAME="Flux Application"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=flux
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

# Cache
CACHE_DRIVER=memcached
CACHE_PREFIX=flux_
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211

# Session
SESSION_NAME=flux_session
SESSION_LIFETIME=120
SESSION_DOMAIN=
SESSION_SECURE=false

$firebaseEnv

$workosEnv
ENV;
    }
    
    /**
     * Get the content for the tailwind.config.js file
     */
    private function getTailwindConfigContent(): string
    {
        return <<<'JS'
module.exports = {
  content: [
    "./app/Views/**/*.{php,flux}",
    "./public/**/*.{html,js}",
  ],
  darkMode: 'class',
  theme: {
    extend: {},
  },
  plugins: [],
}
JS;
    }
    
    /**
     * Get the content for the app.css file
     */
    private function getAppCssContent(): string
    {
        return <<<'CSS'
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom styles */
.dark {
  @apply bg-gray-900 text-white;
}
CSS;
    }
    
    /**
     * Get the content for the AuthController.php file
     */
    private function getAuthControllerContent(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Controllers;

use Flux\Http\Request;
use Flux\Http\Response;
use Flux\Core\Application;
use Flux\View\View;

class AuthController
{
    /**
     * Display the login form
     */
    public function showLoginForm(Request $request)
    {
        return View::make('auth.login');
    }
    
    /**
     * Handle a login request
     */
    public function login(Request $request)
    {
        $email = $request->get('email');
        $password = $request->get('password');
        
        $app = Application::getInstance();
        
        $success = $app->getAuth()->attempt([
            'email' => $email,
            'password' => $password,
        ]);
        
        if (!$success) {
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'Invalid credentials',
                ], 401);
            }
            
            return View::make('auth.login', [
                'error' => 'Invalid credentials',
            ]);
        }
        
        if ($request->expectsJson()) {
            return Response::json([
                'message' => 'Logged in successfully',
                'user' => $app->getAuth()->user()->toArray(),
            ]);
        }
        
        return Response::json([
            'redirect' => '/',
        ], 302);
    }
    
    /**
     * Log the user out
     */
    public function logout(Request $request)
    {
        $app = Application::getInstance();
        $app->getAuth()->logout();
        
        if ($request->expectsJson()) {
            return Response::json([
                'message' => 'Logged out successfully',
            ]);
        }
        
        return Response::json([
            'redirect' => '/login',
        ], 302);
    }
    
    /**
     * Display the registration form
     */
    public function showRegistrationForm(Request $request)
    {
        return View::make('auth.register');
    }
    
    /**
     * Handle a registration request
     */
    public function register(Request $request)
    {
        $name = $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');
        
        $app = Application::getInstance();
        
        // Create the user
        $provider = $app->getAuth()->provider('native');
        $user = $provider->createUser($name, $email, $password);
        
        // Log the user in
        $app->getAuth()->login($user);
        
        if ($request->expectsJson()) {
            return Response::json([
                'message' => 'Registered successfully',
                'user' => $user->toArray(),
            ]);
        }
        
        return Response::json([
            'redirect' => '/',
        ], 302);
    }
    
    /**
     * Redirect to the OAuth provider
     */
    public function redirectToProvider(Request $request, string $provider)
    {
        $app = Application::getInstance();
        
        $url = $app->getAuth()->getOAuthUrl($provider);
        
        return Response::json([
            'redirect' => $url,
        ], 302);
    }
    
    /**
     * Handle the OAuth callback
     */
    public function handleProviderCallback(Request $request, string $provider)
    {
        $app = Application::getInstance();
        
        $user = $app->getAuth()->handleOAuthCallback($request, $provider);
        
        if (!$user) {
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'Authentication failed',
                ], 401);
            }
            
            return Response::json([
                'redirect' => '/login',
            ], 302);
        }
        
        if ($request->expectsJson()) {
            return Response::json([
                'message' => 'Logged in successfully',
                'user' => $user->toArray(),
            ]);
        }
        
        return Response::json([
            'redirect' => '/',
        ], 302);
    }
}
PHP;
    }
    
    /**
     * Get the content for the login.flux view file
     */
    private function getLoginViewContent(): string
    {
        $firebaseButton = $this->choices['auth'] === 'firebase' || $this->choices['auth'] === 'all' ? 
            '<a href="/auth/firebase" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 mb-2">Login with Firebase</a>' : '';
        
        $workosButton = $this->choices['auth'] === 'workos' || $this->choices['auth'] === 'all' ? 
            '<a href="/auth/workos" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Login with WorkOS</a>' : '';
        
        return <<<HTML
@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Sign in to your account
                </h2>
            </div>
            
            @if(isset(\$error))
                <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ \$error }}</h3>
                        </div>
                    </div>
                </div>
            @endif
            
            <form class="mt-8 space-y-6" action="/login" method="POST">
                @csrf
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                </div>
            </form>
            
            @if('$firebaseButton' !== '' || '$workosButton' !== '')
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">Or continue with</span>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        $firebaseButton
                        $workosButton
                    </div>
                </div>
            @endif
            
            <div class="text-center mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account? <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Register</a>
                </p>
            </div>
        </div>
    </div>
@endsection
HTML;
    }
    
    /**
     * Get the content for the register.flux view file
     */
    private function getRegisterViewContent(): string
    {
        $firebaseButton = $this->choices['auth'] === 'firebase' || $this->choices['auth'] === 'all' ? 
            '<a href="/auth/firebase" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-900 bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 mb-2">Register with Firebase</a>' : '';
        
        $workosButton = $this->choices['auth'] === 'workos' || $this->choices['auth'] === 'all' ? 
            '<a href="/auth/workos" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Register with WorkOS</a>' : '';
        
        return <<<HTML
@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    Create a new account
                </h2>
            </div>
            
            @if(isset(\$error))
                <div class="rounded-md bg-red-50 dark:bg-red-900 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ \$error }}</h3>
                        </div>
                    </div>
                </div>
            @endif
            
            <form class="mt-8 space-y-6" action="/register" method="POST">
                @csrf
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="name" class="sr-only">Name</label>
                        <input id="name" name="name" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Name">
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Register
                    </button>
                </div>
            </form>
            
            @if('$firebaseButton' !== '' || '$workosButton' !== '')
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">Or continue with</span>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        $firebaseButton
                        $workosButton
                    </div>
                </div>
            @endif
            
            <div class="text-center mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Already have an account? <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Login</a>
                </p>
            </div>
        </div>
    </div>
@endsection
HTML;
    }
    
    /**
     * Get the content for the dark-mode-toggle.flux component
     */
    private function getDarkModeToggleContent(): string
    {
        return <<<'HTML'
<button 
    type="button" 
    class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
    onclick="toggleDarkMode()"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
    </svg>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" viewBox="0 0 20 20" fill="currentColor">
        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
    </svg>
</button>

<script>
    function toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', document.documentElement.classList.contains('dark') ? 'true' : 'false');
    }
</script>
HTML;
    }
}

