<?php
/**
 * Flux - A modern, lightweight PHP framework
 * 
 * This is the entry point for the Flux framework.
 * It bootstraps the application and handles the request lifecycle.
 */

declare(strict_types=1);

// Define the application root directory
define('FLUX_ROOT', __DIR__);

// Load the autoloader
require_once __DIR__ . '/system/Autoloader.php';

// Bootstrap the application
$app = new \Flux\Core\Application();

// Run the application
$app->run();

