<?php

declare(strict_types=1);

namespace Flux\CLI;

class GenerateCommand
{
    /**
     * Execute the command
     */
    public static function execute(array $args): void
    {
        $forge = new Forge();
        
        if (empty($args)) {
            $forge->error('Please specify what to generate');
            $forge->info('Usage: forge generate [type] [name]');
            $forge->info('Available types: controller, model, middleware, module, migration');
            return;
        }
        
        $type = $args[0];
        $name = $args[1] ?? null;
        
        if ($name === null) {
            $forge->error('Please provide a name');
            return;
        }
        
        switch ($type) {
            case 'controller':
                self::generateController($name);
                break;
            case 'model':
                self::generateModel($name);
                break;
            case 'middleware':
                self::generateMiddleware($name);
                break;
            case 'module':
                self::generateModule($name);
                break;
            case 'migration':
                self::generateMigration($name);
                break;
            default:
                $forge->error("Unknown type: $type");
                $forge->info('Available types: controller, model, middleware, module, migration');
                return;
        }
        
        $forge->success("Generated $type: $name");
    }
    
    /**
     * Generate a controller
     */
    private static function generateController(string $name): void
    {
        $controllerDir = FLUX_ROOT . '/app/Controllers';
        
        if (!is_dir($controllerDir)) {
            mkdir($controllerDir, 0755, true);
        }
        
        $controllerPath = $controllerDir . '/' . $name . 'Controller.php';
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Controllers;

use Flux\Http\Request;
use Flux\Http\Response;
use Flux\View\View;

class {$name}Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request \$request)
    {
        return Response::json([
            'message' => '{$name} index',
        ]);
    }
    
    /**
     * Display the specified resource
     */
    public function show(Request \$request, \$id)
    {
        return Response::json([
            'message' => '{$name} show',
            'id' => \$id,
        ]);
    }
    
    /**
     * Store a newly created resource
     */
    public function store(Request \$request)
    {
        return Response::json([
            'message' => '{$name} stored',
            'data' => \$request->all(),
        ]);
    }
    
    /**
     * Update the specified resource
     */
    public function update(Request \$request, \$id)
    {
        return Response::json([
            'message' => '{$name} updated',
            'id' => \$id,
            'data' => \$request->all(),
        ]);
    }
    
    /**
     * Remove the specified resource
     */
    public function destroy(Request \$request, \$id)
    {
        return Response::json([
            'message' => '{$name} deleted',
            'id' => \$id,
        ]);
    }
}
PHP;
        
        file_put_contents($controllerPath, $content);
    }
    
    /**
     * Generate a model
     */
    private static function generateModel(string $name): void
    {
        $modelDir = FLUX_ROOT . '/app/Models';
        
        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0755, true);
        }
        
        $modelPath = $modelDir . '/' . $name . '.php';
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Flux\Database\Model;

class {$name} extends Model
{
    /**
     * The table associated with the model
     */
    protected string \$table = ''.strtolower($name).'s';
    
    /**
     * The primary key for the model
     */
    protected string \$primaryKey = 'id';
    
    /**
     * Custom method example
     */
    public function customMethod()
    {
        // Your custom logic here
    }
}
PHP;
        
        file_put_contents($modelPath, $content);
    }
    
    /**
     * Generate middleware
     */
    private static function generateMiddleware(string $name): void
    {
        $middlewareDir = FLUX_ROOT . '/app/Middleware';
        
        if (!is_dir($middlewareDir)) {
            mkdir($middlewareDir, 0755, true);
        }
        
        $middlewarePath = $middlewareDir . '/' . $name . 'Middleware.php';
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Middleware;

use Flux\Http\Request;
use Flux\Http\Response;

class {$name}Middleware
{
    /**
     * Process the request
     */
    public function process(Request \$request, callable \$next)
    {
        // Your middleware logic here
        
        // Call the next middleware
        return \$next(\$request);
    }
}
PHP;
        
        file_put_contents($middlewarePath, $content);
    }
    
    /**
     * Generate a module
     */
    private static function generateModule(string $name): void
    {
        $moduleDir = FLUX_ROOT . '/app/Modules/' . $name;
        
        if (!is_dir($moduleDir)) {
            mkdir($moduleDir, 0755, true);
            mkdir($moduleDir . '/Controllers', 0755, true);
            mkdir($moduleDir . '/Models', 0755, true);
            mkdir($moduleDir . '/Views', 0755, true);
        }
        
        // Create module class
        $modulePath = $moduleDir . '/' . $name . 'Module.php';
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$name};

use Flux\Core\Application;
use Flux\Routing\Router;

class {$name}Module
{
    /**
     * @var Application The application instance
     */
    private Application \$app;
    
    /**
     * Create a new module instance
     */
    public function __construct(Application \$app)
    {
        \$this->app = \$app;
    }
    
    /**
     * Register module routes
     */
    public function registerRoutes(Router \$router): void
    {
        \$router->group(['prefix' => strtolower('$name')], function(\$router) {
            \$router->get('/', [Controllers\\{$name}Controller::class, 'index']);
        });
    }
}
PHP;
        
        file_put_contents($modulePath, $content);
        
        // Create module controller
        $controllerPath = $moduleDir . '/Controllers/' . $name . 'Controller.php';
        
        $controllerContent = <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$name}\Controllers;

use Flux\Http\Request;
use Flux\Http\Response;

class {$name}Controller
{
    /**
     * Display the module index
     */
    public function index(Request \$request)
    {
        return Response::json([
            'module' => '{$name}',
            'message' => 'Welcome to the {$name} module',
        ]);
    }
}
PHP;
        
        file_put_contents($controllerPath, $controllerContent);
    }
    
    /**
     * Generate a migration
     */
    private static function generateMigration(string $name): void
    {
        $migrationDir = FLUX_ROOT . '/database/migrations';
        
        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }
        
        $timestamp = date('Y_m_d_His');
        $migrationPath = $migrationDir . '/' . $timestamp . '_' . strtolower($name) . '.php';
        
        $content = <<<PHP
<?php

declare(strict_types=1);

use Flux\Database\Migration;
use Flux\Database\Schema;
use Flux\Database\Table;

class {$name} extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        Schema::create('table_name', function(Table \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
}
PHP;
        
        file_put_contents($migrationPath, $content);
    }
}

