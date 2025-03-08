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

// Public routes
$router->get('/', [HomeController::class, 'index']);

// Authentication routes
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegistrationForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// OAuth routes
$router->get('/auth/{provider}', [AuthController::class, 'redirectToProvider']);
$router->get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

// Protected routes
$router->group(['middleware' => [AuthMiddleware::class]], function($router) {
    $router->get('/dashboard', function(Request $request) {
        return Response::json([
            'message' => 'Welcome to the dashboard',
            'user' => app()->getAuth()->user()->toArray(),
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

// Helper function to get the application instance
if (!function_exists('app')) {
    function app() {
        return \Flux\Core\Application::getInstance();
    }
}

