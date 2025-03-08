<?php

declare(strict_types=1);

namespace Flux\Error;

use Flux\Http\Request;
use Flux\Http\Response;

class ErrorHandler
{
    /**
     * Register the error handler
     */
    public static function register(): void
    {
        // Set error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set exception handler
        set_exception_handler([self::class, 'handleUncaughtException']);
        
        // Register shutdown function
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        throw new \ErrorException($message, 0, $level, $file, $line);
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleUncaughtException(\Throwable $e): void
    {
        $response = self::createExceptionResponse($e);
        $response->send();
    }
    
    /**
     * Handle exceptions during request processing
     */
    public static function handleException(\Throwable $e, Request $request): Response
    {
        return self::createExceptionResponse($e, $request);
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            self::handleUncaughtException($exception);
        }
    }
    
    /**
     * Create a response for an exception
     */
    private static function createExceptionResponse(\Throwable $e, ?Request $request = null): Response
    {
        $statusCode = $e instanceof \Exception ? $e->getCode() : 500;
        
        // Ensure status code is valid
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 500;
        }
        
        // Check if we should return JSON
        $isJson = $request && $request->expectsJson();
        
        if ($isJson) {
            return Response::json([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $statusCode,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => self::shouldDisplayDetails() ? $e->getTrace() : [],
                ]
            ], $statusCode);
        }
        
        // Create HTML response
        $html = self::renderExceptionAsHtml($e);
        
        return new Response($html, $statusCode, ['Content-Type' => 'text/html']);
    }
    
    /**
     * Render an exception as HTML
     */
    private static function renderExceptionAsHtml(\Throwable $e): string
    {
        $title = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        
        // Get the code snippet
        $snippet = self::getCodeSnippet($file, $line);
        
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error: $title</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
            <script>
                function toggleDarkMode() {
                    document.documentElement.classList.toggle('dark');
                    localStorage.setItem('darkMode', document.documentElement.classList.contains('dark') ? 'true' : 'false');
                }
                
                // Check for saved dark mode preference
                if (localStorage.getItem('darkMode') === 'true' || 
                    (localStorage.getItem('darkMode') === null && 
                     window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            </script>
            <style>
                .dark { background-color: #1a202c; color: #e2e8f0; }
                .dark .bg-white { background-color: #2d3748 !important; }
                .dark .bg-gray-100 { background-color: #1a202c !important; }
                .dark .bg-red-100 { background-color: #742a2a !important; }
                .dark .text-gray-800 { color: #e2e8f0 !important; }
                .dark .text-gray-700 { color: #e2e8f0 !important; }
                .dark .text-red-800 { color: #feb2b2 !important; }
                .dark .border-gray-200 { border-color: #4a5568 !important; }
                .dark .border-red-200 { border-color: #742a2a !important; }
                pre { overflow-x: auto; }
            </style>
        </head>
        <body class="bg-gray-100 min-h-screen">
            <div class="container mx-auto px-4 py-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Flux Framework</h1>
                    <button onclick="toggleDarkMode()" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 focus:outline-none">
                        Toggle Dark Mode
                    </button>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                    <div class="bg-red-100 border-b border-red-200 px-6 py-4">
                        <h2 class="text-xl font-semibold text-red-800">$title</h2>
                        <p class="text-lg text-red-800 mt-1">$message</p>
                    </div>
                    
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center text-gray-700">
                            <span class="font-semibold mr-2">Location:</span>
                            <span class="font-mono">$file:$line</span>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Code</h3>
                        <div class="bg-gray-100 rounded p-4 font-mono text-sm overflow-x-auto">
                            $snippet
                        </div>
                    </div>
        HTML;
        
        if (self::shouldDisplayDetails()) {
            $html .= <<<HTML
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Stack Trace</h3>
                        <pre class="bg-gray-100 rounded p-4 font-mono text-sm whitespace-pre-wrap">$trace</pre>
                    </div>
            HTML;
        }
        
        $html .= <<<HTML
                </div>
                
                <div class="text-center text-gray-600 text-sm">
                    <p>Flux Framework</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
        
        return $html;
    }
    
    /**
     * Get a code snippet around the error line
     */
    private static function getCodeSnippet(string $file, int $line, int $linesAround = 5): string
    {
        if (!file_exists($file)) {
            return 'File not found';
        }
        
        $lines = file($file);
        
        if (!$lines) {
            return 'Could not read file';
        }
        
        $start = max(0, $line - $linesAround - 1);
        $end = min(count($lines) - 1, $line + $linesAround - 1);
        
        $snippet = '';
        
        for ($i = $start; $i <= $end; $i++) {
            $currentLine = $i + 1;
            $lineContent = htmlspecialchars($lines[$i]);
            
            if ($currentLine === $line) {
                $snippet .= "<span class=\"bg-red-200 text-red-800 block\">$currentLine: $lineContent</span>";
            } else {
                $snippet .= "<span class=\"block\">$currentLine: $lineContent</span>";
            }
        }
        
        return $snippet;
    }
    
    /**
     * Check if we should display detailed error information
     */
    private static function shouldDisplayDetails(): bool
    {
        // In a real application, this would check the environment
        // For now, always return true for development purposes
        return true;
    }
}

