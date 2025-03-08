<?php

declare(strict_types=1);

namespace Flux\View;

class View
{
    /**
     * @var string The view path
     */
    private string $path;
    
    /**
     * @var array The view data
     */
    private array $data;
    
    /**
     * Create a new view instance
     */
    public function __construct(string $path, array $data = [])
    {
        $this->path = $path;
        $this->data = $data;
    }
    
    /**
     * Render the view
     */
    public function render(): string
    {
        // Check if the view file exists
        $viewPath = $this->resolvePath($this->path);
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$this->path}");
        }
        
        // Check if it's a Flux template
        if (pathinfo($viewPath, PATHINFO_EXTENSION) === 'flux') {
            $content = file_get_contents($viewPath);
            $template = new FluxTemplate($content, $this->data);
            return $template->render();
        }
        
        // Otherwise, treat it as a PHP template
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $viewPath;
        
        // Get the buffered content
        return ob_get_clean();
    }
    
    /**
     * Resolve the view path
     */
    private function resolvePath(string $path): string
    {
        // Replace dots with directory separators
        $path = str_replace('.', '/', $path);
        
        // Check if the path already has an extension
        $hasExtension = pathinfo($path, PATHINFO_EXTENSION) !== '';
        
        // Try with the provided path
        $fullPath = FLUX_ROOT . '/app/Views/' . $path;
        if (file_exists($fullPath)) {
            return $fullPath;
        }
        
        // Try with .flux extension
        if (!$hasExtension) {
            $fluxPath = $fullPath . '.flux';
            if (file_exists($fluxPath)) {
                return $fluxPath;
            }
        }
        
        // Try with .php extension
        if (!$hasExtension) {
            $phpPath = $fullPath . '.php';
            if (file_exists($phpPath)) {
                return $phpPath;
            }
        }
        
        // Return the original path for error reporting
        return $fullPath;
    }
    
    /**
     * Create a new view
     */
    public static function make(string $path, array $data = []): self
    {
        return new self($path, $data);
    }
    
    /**
     * Convert the view to a string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}

