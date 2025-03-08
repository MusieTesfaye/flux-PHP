<?php

declare(strict_types=1);

namespace Flux\Auth;

use Flux\Config\ConfigManager;
use Flux\Auth\Providers\NativeProvider;
use Flux\Auth\Providers\FirebaseProvider;
use Flux\Auth\Providers\WorkOSProvider;
use Flux\Auth\Providers\AuthProviderInterface;
use Flux\Http\Request;

class AuthManager
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var array Authentication providers
     */
    private array $providers = [];
    
    /**
     * @var AuthProviderInterface|null The default provider
     */
    private ?AuthProviderInterface $defaultProvider = null;
    
    /**
     * @var User|null The authenticated user
     */
    private ?User $user = null;
    
    /**
     * Initialize the authentication manager
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        
        // Register the native provider
        $this->registerProvider('native', new NativeProvider($config));
        
        // Register the Firebase provider if configured
        if ($this->config->has('auth.providers.firebase')) {
            $this->registerProvider('firebase', new FirebaseProvider($config));
        }
        
        // Register the WorkOS provider if configured
        if ($this->config->has('auth.providers.workos')) {
            $this->registerProvider('workos', new WorkOSProvider($config));
        }
        
        // Set the default provider
        $defaultProvider = $this->config->get('auth.default', 'native');
        $this->defaultProvider = $this->providers[$defaultProvider] ?? $this->providers['native'];
    }
    
    /**
     * Register an authentication provider
     */
    public function registerProvider(string $name, AuthProviderInterface $provider): void
    {
        $this->providers[$name] = $provider;
    }
    
    /**
     * Get an authentication provider
     */
    public function provider(string $name = null): AuthProviderInterface
    {
        if ($name === null) {
            return $this->defaultProvider;
        }
        
        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException("Authentication provider not found: $name");
        }
        
        return $this->providers[$name];
    }
    
    /**
     * Attempt to authenticate a user
     */
    public function attempt(array $credentials, string $provider = null): bool
    {
        $provider = $this->provider($provider);
        
        $user = $provider->attempt($credentials);
        
        if ($user) {
            $this->user = $user;
            $this->startSession($user);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log in a user
     */
    public function login(User $user): void
    {
        $this->user = $user;
        $this->startSession($user);
    }
    
    /**
     * Log out the current user
     */
    public function logout(): void
    {
        $this->user = null;
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['auth_user']);
            session_regenerate_id(true);
        }
    }
    
    /**
     * Check if a user is authenticated
     */
    public function check(): bool
    {
        return $this->user !== null;
    }
    
    /**
     * Get the authenticated user
     */
    public function user(): ?User
    {
        if ($this->user === null) {
            $this->user = $this->getUserFromSession();
        }
        
        return $this->user;
    }
    
    /**
     * Start a session for the user
     */
    private function startSession(User $user): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION['auth_user'] = [
            'id' => $user->getId(),
            'provider' => $user->getProvider(),
        ];
        
        session_regenerate_id(true);
    }
    
    /**
     * Get the user from the session
     */
    private function getUserFromSession(): ?User
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['auth_user'])) {
            return null;
        }
        
        $userData = $_SESSION['auth_user'];
        $provider = $this->provider($userData['provider']);
        
        return $provider->retrieveById($userData['id']);
    }
    
    /**
     * Validate a token
     */
    public function validateToken(string $token, string $provider = null): ?User
    {
        $provider = $this->provider($provider);
        
        return $provider->validateToken($token);
    }
    
    /**
     * Process an OAuth callback
     */
    public function handleOAuthCallback(Request $request, string $provider = null): ?User
    {
        $provider = $this->provider($provider);
        
        $user = $provider->handleOAuthCallback($request);
        
        if ($user) {
            $this->login($user);
        }
        
        return $user;
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function getOAuthUrl(string $provider = null): string
    {
        $provider = $this->provider($provider);
        
        return $provider->getOAuthUrl();
    }
}

