<?php

declare(strict_types=1);

namespace Flux\Http;

class Response
{
    /**
     * @var int HTTP status code
     */
    private int $statusCode = 200;
    
    /**
     * @var array Response headers
     */
    private array $headers = [];
    
    /**
     * @var mixed Response content
     */
    private $content = '';
    
    /**
     * Create a new response instance
     */
    public function __construct($content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    /**
     * Set the response content
     */
    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Set the response status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Set a response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Create a JSON response
     */
    public static function json($data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        return new self(json_encode($data), $statusCode, $headers);
    }
    
    /**
     * Send the response
     */
    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);
        
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        // Send content
        if (is_string($this->content)) {
            echo $this->content;
        } elseif (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content);
        }
    }
}

