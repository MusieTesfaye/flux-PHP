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

