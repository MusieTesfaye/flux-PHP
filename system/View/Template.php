<?php

declare(strict_types=1);

namespace Flux\View;

class Template
{
    /**
     * @var string The template content
     */
    private string $content;
    
    /**
     * @var array The template data
     */
    private array $data;
    
    /**
     * Create a new template instance
     */
    public function __construct(string $content, array $data = [])
    {
        $this->content = $content;
        $this->data = $data;
    }
    
    /**
     * Render the template
     */
    public function render(): string
    {
        $content = $this->content;
        
        // Process conditionals
        $content = $this->processConditionals($content);
        
        // Process loops
        $content = $this->processLoops($content);
        
        // Process variables
        $content = $this->processVariables($content);
        
        // Process includes
        $content = $this->processIncludes($content);
        
        return $content;
    }
    
    /**
     * Process conditional statements
     */
    private function processConditionals(string $content): string
    {
        // Match if statements
        $pattern = '/{%\s*if\s+(.*?)\s*%}(.*?)(?:{%\s*else\s*%}(.*?))?{%\s*endif\s*%}/s';
        
        return preg_replace_callback($pattern, function($matches) {
            $condition = $matches[1];
            $thenBlock = $matches[2];
            $elseBlock = $matches[3] ?? '';
            
            // Evaluate the condition
            $result = $this->evaluateExpression($condition);
            
            return $result ? $thenBlock : $elseBlock;
        }, $content);
    }
    
    /**
     * Process loops
     */
    private function processLoops(string $content): string
    {
        // Match for loops
        $pattern = '/{%\s*for\s+(.*?)\s+in\s+(.*?)\s*%}(.*?){%\s*endfor\s*%}/s';
        
        return preg_replace_callback($pattern, function($matches) {
            $itemName = $matches[1];
            $arrayName = $matches[2];
            $loopContent = $matches[3];
            
            // Get the array from the data
            $array = $this->evaluateExpression($arrayName);
            
            if (!is_array($array)) {
                return '';
            }
            
            $result = '';
            
            foreach ($array as $key => $value) {
                // Create a temporary template with the loop item
                $tempData = array_merge($this->data, [
                    $itemName => $value,
                    $itemName . '_key' => $key,
                ]);
                
                $tempTemplate = new self($loopContent, $tempData);
                $result .= $tempTemplate->render();
            }
            
            return $result;
        }, $content);
    }
    
    /**
     * Process variables
     */
    private function processVariables(string $content): string
    {
        // Match variables
        $pattern = '/{{(.*?)}}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $expression = trim($matches[1]);
            return $this->evaluateExpression($expression);
        }, $content);
    }
    
    /**
     * Process includes
     */
    private function processIncludes(string $content): string
    {
        // Match includes
        $pattern = '/{%\s*include\s+[\'"](.+?)[\'"]\s*%}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $includePath = $matches[1];
            
            // Resolve the include path
            $includePath = FLUX_ROOT . '/app/Views/' . $includePath;
            
            if (!file_exists($includePath)) {
                return "<!-- Include not found: $includePath -->";
            }
            
            // Create a new template with the included content
            $includeContent = file_get_contents($includePath);
            $includeTemplate = new self($includeContent, $this->data);
            
            return $includeTemplate->render();
        }, $content);
    }
    
    /**
     * Evaluate an expression
     */
    private function evaluateExpression(string $expression)
    {
        // Simple variable access
        if (strpos($expression, '.') !== false) {
            $parts = explode('.', $expression);
            $value = $this->data;
            
            foreach ($parts as $part) {
                $part = trim($part);
                
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return '';
                }
            }
            
            return $value;
        }
        
        // Simple variable
        $expression = trim($expression);
        return $this->data[$expression] ?? '';
    }
    
    /**
     * Create a new template from a file
     */
    public static function fromFile(string $path, array $data = []): self
    {
        if (!file_exists($path)) {
            throw new \Exception("Template file not found: $path");
        }
        
        $content = file_get_contents($path);
        
        return new self($content, $data);
    }
}

