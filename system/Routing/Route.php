<?php

declare(strict_types=1);

namespace Flux\Routing;

use Flux\Http\Request;
use Flux\Http\Response;

class Route
{
    /**
     * @var string HTTP method
     */
    private string $method;
    
    /**
     * @var string Route path
     */
    private string $path;
    
    /**
     * @var mixed Route handler
     */
    private $handler;
    
    /**
     * @var array Route middleware
     */
    private array $middleware;
    
    /**
     * @var array Route parameters
     */
    private array $parameters = [];
    
    /**
     * @var string Route name
     */
    private ?string $name = null;
    
    /**
     * Create a new route instance
     */
    public function __construct(string $method, string $path, $handler, array $middleware = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middleware = $middleware;
    }
    
    /**
     * Check if the route matches a method and URI
     */
    public function matches(string $method, string $uri): bool
    {
        // Check method
        if ($this->method !== '*' && $this->method !== $method) {
            return false;
        }
        
        // Convert route pattern to regex
        $pattern = $this->path;
        
        // Replace named parameters
        $pattern = preg_replace('/{([a-zA-Z0-9_]+)}/', '(?P<$1>[^/]+)', $pattern);
        
        // Replace optional parameters
        $pattern = preg_replace('/{([a-zA-Z0-9_]+)\?}/', '(?P<$1>[^/]*)', $pattern);
        
        // Add start and end anchors
        $pattern = '#^' . $pattern . '$#';
        
        // Match the URI against the pattern
        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->parameters[$key] = $value;
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Execute the route handler
     */
    public function execute(Request $request)
    {
        // Add route parameters to the request
        foreach ($this->parameters as $key => $value) {
            $request->all()[$key] = $value;
        }
        
        // Handle different types of handlers
        if (is_callable($this->handler)) {
            // Callable handler
            return call_user_func($this->handler, $request, ...$this->parameters);
        } elseif (is_string($this->handler) && strpos($this->handler, '@') !== false) {
            // Controller@method handler
            list($controller, $method) = explode('@', $this->handler);
            
            // Create controller instance
            $instance = new $controller();
            
            // Call the method
            return $instance->$method($request, ...$this->parameters);
        } elseif (is_array($this->handler) && count($this->handler) === 2) {
            // [Controller::class, 'method'] handler
            list($controller, $method) = $this->handler;
            
            // Create controller instance
            $instance = new $controller();
            
            // Call the method
            return $instance->$method($request, ...$this->parameters);
        }
        
        throw new \Exception('Invalid route handler');
    }
    
    /**
     * Get the route middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
    
    /**
     * Set the route name
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Get the route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

