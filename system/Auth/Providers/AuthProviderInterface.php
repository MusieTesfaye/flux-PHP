<?php

declare(strict_types=1);

namespace Flux\Auth\Providers;

use Flux\Auth\User;
use Flux\Http\Request;

interface AuthProviderInterface
{
    /**
     * Attempt to authenticate a user with the given credentials
     */
    public function attempt(array $credentials): ?User;
    
    /**
     * Retrieve a user by their ID
     */
    public function retrieveById(string $id): ?User;
    
    /**
     * Validate a token
     */
    public function validateToken(string $token): ?User;
    
    /**
     * Handle an OAuth callback
     */
    public function handleOAuthCallback(Request $request): ?User;
    
    /**
     * Get the OAuth authorization URL
     */
    public function getOAuthUrl(): string;
}

