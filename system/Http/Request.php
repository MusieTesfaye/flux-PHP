<?php

declare(strict_types=1);

namespace Flux\Http;

class Request
{
    /**
     * @var array Request headers
     */
    private array $headers = [];
    
    /**
     * @var array Request parameters ($_GET, $_POST)
     */
    private array $parameters = [];
    
    /**
     * @var array Request cookies
     */
    private array $cookies = [];
    
    /**
     * @var array Uploaded files
     */
    private array $files = [];
    
    /**
     * @var string Request method
     */
    private string $method;
    
    /**
     * @var string Request URI
     */
    private string $uri;
    
    /**
     * @var array Parsed JSON body
     */
    private ?array $jsonBody = null;
    
    /**
     * Create a new request instance
     */
    public function __construct(
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $headers = [],
        string $method = 'GET',
        string $uri = '/'
    ) {
        $this->parameters = $parameters;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->headers = $headers;
        $this->method = strtoupper($method);
        $this->uri = $uri;
    }
    
    /**
     * Create a request from global variables
     */
    public static function createFromGlobals(): self
    {
        // Parse headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = $value;
            }
        }
        
        // Determine the request URI
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if ($pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Merge GET and POST parameters
        $parameters = array_merge($_GET, $_POST);
        
        return new self(
            $parameters,
            $_COOKIE,
            $_FILES,
            $headers,
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri
        );
    }
    
    /**
     * Get a request parameter
     */
    public function get(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }
    
    /**
     * Get all request parameters
     */
    public function all(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get a request header
     */
    public function header(string $key, $default = null)
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * Get the request method
     */
    public function method(): string
    {
        return $this->method;
    }
    
    /**
     * Get the request URI
     */
    public function uri(): string
    {
        return $this->uri;
    }
    
    /**
     * Check if the request is an AJAX request
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * Check if the request expects a JSON response
     */
    public function expectsJson(): bool
    {
        return $this->isAjax() || 
               str_contains($this->header('Accept', ''), 'application/json');
    }
    
    /**
     * Get the request body as JSON
     */
    public function json(): ?array
    {
        if ($this->jsonBody === null) {
            $content = file_get_contents('php://input');
            $this->jsonBody = json_decode($content, true) ?? [];
        }
        
        return $this->jsonBody;
    }
    
    /**
     * Get a value from the JSON body
     */
    public function input(string $key, $default = null)
    {
        $json = $this->json();
        return $json[$key] ?? $default;
    }
}

