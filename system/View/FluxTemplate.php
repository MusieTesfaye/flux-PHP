<?php

declare(strict_types=1);

namespace Flux\View;

use Flux\Core\Application;

class FluxTemplate
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
     * @var array Compiled templates cache
     */
    private static array $compiledCache = [];
    
    /**
     * @var array Component registry
     */
    private static array $components = [];
    
    /**
     * @var string Current layout
     */
    private ?string $layout = null;
    
    /**
     * @var array Sections content
     */
    private array $sections = [];
    
    /**
     * @var string|null Currently active section
     */
    private ?string $currentSection = null;
    
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
        // Compile the template
        $compiled = $this->compile($this->content);
        
        // Extract data to make it available in the template
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the compiled template
        eval('?>' . $compiled);
        
        // Get the rendered content
        $rendered = ob_get_clean();
        
        // Apply layout if set
        if ($this->layout) {
            $layoutPath = $this->resolvePath($this->layout);
            $layoutContent = file_get_contents($layoutPath);
            
            $layoutTemplate = new self($layoutContent, $this->data);
            
            // Add sections to the layout data
            foreach ($this->sections as $name => $content) {
                $layoutTemplate->data['__sections'][$name] = $content;
            }
            
            $rendered = $layoutTemplate->render();
        }
        
        return $rendered;
    }
    
    /**
     * Compile the template
     */
    private function compile(string $content): string
    {
        // Check if we have a cached version
        $hash = md5($content);
        
        if (isset(self::$compiledCache[$hash])) {
            return self::$compiledCache[$hash];
        }
        
        // Compile directives
        $content = $this->compileDirectives($content);
        
        // Compile components
        $content = $this->compileComponents($content);
        
        // Compile expressions
        $content = $this->compileExpressions($content);
        
        // Cache the compiled template
        self::$compiledCache[$hash] = $content;
        
        return $content;
    }
    
    /**
     * Compile directives
     */
    private function compileDirectives(string $content): string
    {
        // Compile @if directives
        $content = preg_replace('/@if\s*$$(.*?)$$/', '<?php if($1): ?>', $content);
        $content = preg_replace('/@elseif\s*$$(.*?)$$/', '<?php elseif($1): ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);
        
        // Compile @foreach directives
        $content = preg_replace('/@foreach\s*$$(.*?)$$/', '<?php foreach($1): ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);
        
        // Compile @for directives
        $content = preg_replace('/@for\s*$$(.*?)$$/', '<?php for($1): ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);
        
        // Compile @while directives
        $content = preg_replace('/@while\s*$$(.*?)$$/', '<?php while($1): ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);
        
        // Compile @php directives
        $content = preg_replace('/@php/', '<?php', $content);
        $content = preg_replace('/@endphp/', '?>', $content);
        
        // Compile @extends directive
        $content = preg_replace_callback('/@extends\s*$$[\'"](.+?)[\'"]$$/', function($matches) {
            $this->layout = $matches[1];
            return '';
        }, $content);
        
        // Compile @section directive
        $content = preg_replace_callback('/@section\s*$$[\'"](.+?)[\'"]$$/', function($matches) {
            $this->currentSection = $matches[1];
            return '<?php ob_start(); ?>';
        }, $content);
        
        // Compile @endsection directive
        $content = preg_replace_callback('/@endsection/', function($matches) {
            $section = $this->currentSection;
            $this->currentSection = null;
            return '<?php $this->sections["' . $section . '"] = ob_get_clean(); ?>';
        }, $content);
        
        // Compile @yield directive
        $content = preg_replace_callback('/@yield\s*$$[\'"](.+?)[\'"]$$/', function($matches) {
            return '<?php echo $__sections["' . $matches[1] . '"] ?? ""; ?>';
        }, $content);
        
        // Compile @include directive
        $content = preg_replace_callback('/@include\s*$$[\'"](.+?)[\'"](,\s*(.*?))?$$/', function($matches) {
            $includePath = $matches[1];
            $includeData = isset($matches[3]) ? $matches[3] : '[]';
            
            return '<?php echo $this->includeTemplate("' . $includePath . '", ' . $includeData . '); ?>';
        }, $content);
        
        // Compile @auth directive
        $content = preg_replace('/@auth/', '<?php if(app()->getAuth()->check()): ?>', $content);
        $content = preg_replace('/@endauth/', '<?php endif; ?>', $content);
        
        // Compile @guest directive
        $content = preg_replace('/@guest/', '<?php if(!app()->getAuth()->check()): ?>', $content);
        $content = preg_replace('/@endguest/', '<?php endif; ?>', $content);
        
        // Compile @json directive
        $content = preg_replace_callback('/@json\s*$$(.*?)$$/', function($matches) {
            return '<?php echo json_encode(' . $matches[1] . '); ?>';
        }, $content);
        
        // Compile @method directive
        $content = preg_replace_callback('/@method\s*$$[\'"](.+?)[\'"]$$/', function($matches) {
            return '<input type="hidden" name="_method" value="' . $matches[1] . '">';
        }, $content);
        
        // Compile @csrf directive
        $content = preg_replace('/@csrf/', '<?php echo csrf_field(); ?>', $content);
        
        return $content;
    }
    
    /**
     * Compile components
     */
    private function compileComponents(string $content): string
    {
        // Compile <x-component> tags
        $content = preg_replace_callback('/<x-([a-zA-Z0-9\-\.]+)(\s+[^>]*)?(?:\/>|>(.*?)<\/x-\1>)/s', function($matches) {
            $component = $matches[1];
            $attributes = $matches[2] ?? '';
            $slot = $matches[3] ?? '';
            
            // Parse attributes
            $parsedAttributes = [];
            preg_match_all('/\s+([a-zA-Z0-9\-_]+)=(["\'])(.*?)\2/', $attributes, $attrMatches, PREG_SET_ORDER);
            
            foreach ($attrMatches as $match) {
                $parsedAttributes[$match[1]] = $match[3];
            }
            
            return '<?php echo $this->renderComponent("' . $component . '", ' . var_export($parsedAttributes, true) . ', "' . addslashes($slot) . '"); ?>';
        }, $content);
        
        return $content;
    }
    
    /**
     * Compile expressions
     */
    private function compileExpressions(string $content): string
    {
        // Compile {{ }} expressions
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $content);
        
        // Compile {!! !!} expressions (unescaped)
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
    
    /**
     * Include a template
     */
    public function includeTemplate(string $path, array $data = []): string
    {
        $path = $this->resolvePath($path);
        
        if (!file_exists($path)) {
            return "<!-- Template not found: $path -->";
        }
        
        $content = file_get_contents($path);
        $template = new self($content, array_merge($this->data, $data));
        
        return $template->render();
    }
    
    /**
     * Render a component
     */
    public function renderComponent(string $name, array $attributes = [], string $slot = ''): string
    {
        // Check if the component is registered
        if (isset(self::$components[$name])) {
            $component = self::$components[$name];
            
            if (is_callable($component)) {
                return call_user_func($component, $attributes, $slot);
            }
        }
        
        // Try to find the component file
        $componentPath = $this->resolveComponentPath($name);
        
        if (!file_exists($componentPath)) {
            return "<!-- Component not found: $name -->";
        }
        
        $content = file_get_contents($componentPath);
        
        // Add slot to the data
        $data = array_merge($this->data, $attributes, ['slot' => $slot]);
        
        $template = new self($content, $data);
        
        return $template->render();
    }
    
    /**
     * Resolve the template path
     */
    private function resolvePath(string $path): string
    {
        // Replace dots with directory separators
        $path = str_replace('.', '/', $path);
        
        // Add .flux extension if not present
        if (!str_ends_with($path, '.flux') && !str_ends_with($path, '.php')) {
            $path .= '.flux';
        }
        
        // Check if the path is absolute
        if (file_exists($path)) {
            return $path;
        }
        
        // Check in the views directory
        $viewsPath = FLUX_ROOT . '/app/Views/' . $path;
        
        if (file_exists($viewsPath)) {
            return $viewsPath;
        }
        
        // Try with .php extension
        if (!str_ends_with($path, '.php')) {
            $phpPath = substr($path, 0, -5) . '.php';
            $viewsPhpPath = FLUX_ROOT . '/app/Views/' . $phpPath;
            
            if (file_exists($viewsPhpPath)) {
                return $viewsPhpPath;
            }
        }
        
        return $path;
    }
    
    /**
     * Resolve the component path
     */
    private function resolveComponentPath(string $name): string
    {
        // Replace dots with directory separators
        $path = str_replace('.', '/', $name);
        
        // Add .flux extension if not present
        if (!str_ends_with($path, '.flux') && !str_ends_with($path, '.php')) {
            $path .= '.flux';
        }
        
        // Check in the components directory
        $componentsPath = FLUX_ROOT . '/app/Views/components/' . $path;
        
        if (file_exists($componentsPath)) {
            return $componentsPath;
        }
        
        // Try with .php extension
        if (!str_ends_with($path, '.php')) {
            $phpPath = substr($path, 0, -5) . '.php';
            $componentsPhpPath = FLUX_ROOT . '/app/Views/components/' . $phpPath;
            
            if (file_exists($componentsPhpPath)) {
                return $componentsPhpPath;
            }
        }
        
        return $componentsPath;
    }
    
    /**
     * Register a component
     */
    public static function component(string $name, callable $callback): void
    {
        self::$components[$name] = $callback;
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
    
    /**
     * Create a new template from a string
     */
    public static function fromString(string $content, array $data = []): self
    {
        return new self($content, $data);
    }
}

