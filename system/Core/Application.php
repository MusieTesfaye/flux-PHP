<?php

declare(strict_types=1);

namespace Flux\Core;

use Flux\Http\Request;
use Flux\Http\Response;
use Flux\Routing\Router;
use Flux\Config\ConfigManager;
use Flux\Error\ErrorHandler;
use Flux\Environment\EnvLoader;
use Flux\Auth\AuthManager;
use Flux\Cache\CacheManager;
use Flux\Session\SessionManager;

class Application
{
    /**
     * @var Router The router instance
     */
    private Router $router;
    
    /**
     * @var Request The current request
     */
    private Request $request;
    
    /**
     * @var Response The response to be sent
     */
    private Response $response;
    
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var AuthManager The authentication manager
     */
    private AuthManager $auth;
    
    /**
     * @var CacheManager The cache manager
     */
    private CacheManager $cache;
    
    /**
     * @var SessionManager The session manager
     */
    private SessionManager $session;
    
    /**
     * @var array Loaded modules
     */
    private array $modules = [];
    
    /**
     * @var Application The application instance
     */
    private static ?Application $instance = null;
    
    /**
     * Initialize the application
     */
    public function __construct()
    {
        // Set the instance
        self::$instance = $this;
        
        // Initialize error handling
        ErrorHandler::register();
        
        // Load environment variables
        EnvLoader::load(FLUX_ROOT);
        
        // Load configuration
        $this->config = new ConfigManager();
        
        // Create request from globals
        $this->request = Request::createFromGlobals();
        
        // Initialize response
        $this->response = new Response();
        
        // Initialize router
        $this->router = new Router();
        
        // Initialize session manager
        $this->session = new SessionManager($this->config);
        $this->session->start();
        
        // Initialize cache manager
        $this->cache = new CacheManager($this->config);
        
        // Initialize auth manager
        $this->auth = new AuthManager($this->config);
        
        // Load application modules
        $this->loadModules();
        
        // Load routes
        $this->loadRoutes();
    }
    
    /**
     * Get the application instance
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            // Match the current route
            $route = $this->router->match($this->request);
            
            // Execute middleware stack
            $middlewareResponse = $this->executeMiddleware($route, $this->request);
            if ($middlewareResponse) {
                $this->response = $middlewareResponse;
            } else {
                // Execute the route handler
                $response = $route->execute($this->request);
                
                // If the handler returned a Response, use it
                if ($response instanceof Response) {
                    $this->response = $response;
                } else {
                    // Otherwise, set the response content
                    $this->response->setContent($response);
                }
            }
        } catch (\Throwable $e) {
            // Handle exceptions
            $this->response = ErrorHandler::handleException($e, $this->request);
        }
        
        // Send the response
        $this->response->send();
    }
    
    /**
     * Load application modules
     */
    private function loadModules(): void
    {
        $modulesDir = FLUX_ROOT . '/app/Modules';
        
        if (!is_dir($modulesDir)) {
            return;
        }
        
        $modules = array_filter(scandir($modulesDir), function($item) use ($modulesDir) {
            return is_dir($modulesDir . '/' . $item) && !in_array($item, ['.', '..']);
        });
        
        foreach ($modules as $module) {
            $moduleClass = "\\App\\Modules\\{$module}\\{$module}Module";
            
            if (class_exists($moduleClass)) {
                $this->modules[$module] = new $moduleClass($this);
            }
        }
    }
    
    /**
     * Load application routes
     */
    private function loadRoutes(): void
    {
        $routesFile = FLUX_ROOT . '/app/routes.php';
        
        if (file_exists($routesFile)) {
            // Pass $app to the routes file
            $app = $this;
            require $routesFile;
        }
        
        // Load module routes
        foreach ($this->modules as $module) {
            $module->registerRoutes($this->router);
        }
    }
    
    /**
     * Execute middleware for a route
     */
    private function executeMiddleware($route, Request $request): ?Response
    {
        $middleware = $route->getMiddleware();
        
        foreach ($middleware as $middlewareClass) {
            $instance = new $middlewareClass();
            $response = $instance->process($request, function($request) {
                return null;
            });
            
            if ($response instanceof Response) {
                return $response;
            }
        }
        
        return null;
    }
    
    /**
     * Get the router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Get the configuration manager
     */
    public function getConfig(): ConfigManager
    {
        return $this->config;
    }
    
    /**
     * Get the authentication manager
     */
    public function getAuth(): AuthManager
    {
        return $this->auth;
    }
    
    /**
     * Get the cache manager
     */
    public function getCache(): CacheManager
    {
        return $this->cache;
    }
    
    /**
     * Get the session manager
     */
    public function getSession(): SessionManager
    {
        return $this->session;
    }
}

