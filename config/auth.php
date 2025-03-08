<?php

/**
 * Authentication Configuration
 */

return [
    // Default authentication provider
    'default' => 'native',
    
    // Authentication providers
    'providers' => [
        'native' => [
            'model' => \App\Models\User::class,
        ],
        
        'firebase' => [
            'enabled' => true,
        ],
        
        'workos' => [
            'enabled' => true,
            'redirect_uri' => env('APP_URL') . '/auth/workos/callback',
        ],
    ],
];

