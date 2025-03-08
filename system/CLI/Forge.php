<?php

declare(strict_types=1);

namespace Flux\CLI;

class Forge
{
    /**
     * @var array Available commands
     */
    private array $commands = [];
    
    /**
     * @var array Command arguments
     */
    private array $args = [];
    
    /**
     * Initialize the CLI tool
     */
    public function __construct(array $args = [])
    {
        // Remove the script name from arguments
        array_shift($args);
        
        $this->args = $args;
        
        // Register built-in commands
        $this->registerBuiltInCommands();
    }
    
    /**
     * Register built-in commands
     */
    private function registerBuiltInCommands(): void
    {
        $this->registerCommand('new', [NewCommand::class, 'execute'], 'Create a new Flux application');
        $this->registerCommand('init', [InitCommand::class, 'execute'], 'Initialize a new Flux application');
        $this->registerCommand('serve', [ServeCommand::class, 'execute'], 'Start the development server');
        $this->registerCommand('generate', [GenerateCommand::class, 'execute'], 'Generate boilerplate code');
        $this->registerCommand('migrate', [MigrateCommand::class, 'execute'], 'Run database migrations');
        $this->registerCommand('help', [$this, 'showHelp'], 'Show help information');
    }
    
    /**
     * Register a command
     */
    public function registerCommand(string $name, callable $handler, string $description = ''): void
    {
        $this->commands[$name] = [
            'handler' => $handler,
            'description' => $description,
        ];
    }
    
    /**
     * Run the CLI tool
     */
    public function run(): void
    {
        $command = $this->args[0] ?? 'help';
        
        if (!isset($this->commands[$command])) {
            $this->error("Command not found: $command");
            $this->showHelp();
            return;
        }
        
        // Remove the command name from arguments
        $commandArgs = $this->args;
        array_shift($commandArgs);
        
        // Execute the command
        call_user_func($this->commands[$command]['handler'], $commandArgs);
    }
    
    /**
     * Show help information
     */
    public function showHelp(): void
    {
        $this->info('Flux Framework CLI Tool');
        $this->info('Usage: forge [command] [options]');
        $this->info('');
        $this->info('Available commands:');
        
        foreach ($this->commands as $name => $command) {
            $this->info(sprintf('  %-15s %s', $name, $command['description']));
        }
    }
    
    /**
     * Output an info message
     */
    public function info(string $message): void
    {
        echo $message . PHP_EOL;
    }
    
    /**
     * Output an error message
     */
    public function error(string $message): void
    {
        echo 'Error: ' . $message . PHP_EOL;
    }
    
    /**
     * Output a success message
     */
    public function success(string $message): void
    {
        echo 'Success: ' . $message . PHP_EOL;
    }
}

