<?php

declare(strict_types=1);

namespace Flux\Routing;

use Flux\Http\Request;
use Flux\Http\Response;

class Router
{
    /**
     * @var array Registered routes
     */
    private array $routes = [];
    
    /**
     * @var array Route groups
     */
    private array $groups = [];
    
    /**
     * @var string Current group prefix
     */
    private string $currentGroupPrefix = '';
    
    /**
     * @var array Current group middleware
     */
    private array $currentGroupMiddleware = [];
    
    /**
     * Register a GET route
     */
    public function get(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Register a POST route
     */
    public function post(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Register a PUT route
     */
    public function put(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Register a DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Register a PATCH route
     */
    public function patch(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }
    
    /**
     * Register a route that responds to any HTTP method
     */
    public function any(string $path, $handler, array $middleware = []): Route
    {
        return $this->addRoute('*', $path, $handler, $middleware);
    }
    
    /**
     * Create a route group
     */
    public function group(array $attributes, callable $callback): void
    {
        // Save current group state
        $previousGroupPrefix = $this->currentGroupPrefix;
        $previousGroupMiddleware = $this->currentGroupMiddleware;
        
        // Update group state
        $prefix = $attributes['prefix'] ?? '';
        $this->currentGroupPrefix .= '/' . trim($prefix, '/');
        $this->currentGroupPrefix = rtrim($this->currentGroupPrefix, '/');
        
        if (isset($attributes['middleware'])) {
            $middleware = is_array($attributes['middleware']) 
                ? $attributes['middleware'] 
                : [$attributes['middleware']];
            $this->currentGroupMiddleware = array_merge(
                $this->currentGroupMiddleware,
                $middleware
            );
        }
        
        // Execute the group callback
        $callback($this);
        
        // Restore previous group state
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->currentGroupMiddleware = $previousGroupMiddleware;
    }
    
    /**
     * Add a route to the router
     */
    private function addRoute(string $method, string $path, $handler, array $middleware = []): Route
    {
        // Apply group prefix
        $path = $this->currentGroupPrefix . '/' . ltrim($path, '/');
        $path = '/' . trim($path, '/');
        
        // Apply group middleware
        $middleware = array_merge($this->currentGroupMiddleware, $middleware);
        
        // Create the route
        $route = new Route($method, $path, $handler, $middleware);
        
        // Store the route
        $this->routes[] = $route;
        
        return $route;
    }
    
    /**
     * Match a request to a route
     */
    public function match(Request $request): ?Route
    {
        $method = $request->method();
        $uri = $request->uri();
        
        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                return $route;
            }
        }
        
        throw new \Exception("Route not found: $uri", 404);
    }
}

