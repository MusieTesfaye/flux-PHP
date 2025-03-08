<?php

declare(strict_types=1);

namespace App\Middleware;

use Flux\Http\Request;
use Flux\Http\Response;
use Flux\Core\Application;

class AuthMiddleware
{
    /**
     * Process the request
     */
    public function process(Request $request, callable $next)
    {
        $app = Application::getInstance();
        
        // Check if the user is authenticated
        if (!$app->getAuth()->check()) {
            // Check for API requests
            if ($request->expectsJson()) {
                return Response::json([
                    'error' => 'Unauthenticated',
                ], 401);
            }
            
            // Redirect to login page
            return Response::json([
                'redirect' => '/login',
            ], 302);
        }
        
        // User is authenticated, continue
        return $next($request);
    }
}

