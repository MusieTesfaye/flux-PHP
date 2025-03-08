<?php

declare(strict_types=1);

namespace Flux\Session;

use Flux\Config\ConfigManager;

class SessionManager
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var bool Whether the session has been started
     */
    private bool $started = false;
    
    /**
     * Initialize the session manager
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }
    
    /**
     * Start the session
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }
        
        // Set session options
        $lifetime = $this->config->get('session.lifetime', 120);
        $path = $this->config->get('session.path', '/');
        $domain = $this->config->get('session.domain', null);
        $secure = $this->config->get('session.secure', false);
        $httpOnly = $this->config->get('session.http_only', true);
        $sameSite = $this->config->get('session.same_site', 'lax');
        
        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
        
        // Set the session name
        $name = $this->config->get('session.name', 'flux_session');
        session_name($name);
        
        // Start the session
        $this->started = session_start();
        
        return $this->started;
    }
    
    /**
     * Get a session value
     */
    public function get(string $key, $default = null)
    {
        $this->ensureStarted();
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set a session value
     */
    public function set(string $key, $value): void
    {
        $this->ensureStarted();
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if a session value exists
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a session value
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        
        unset($_SESSION[$key]);
    }
    
    /**
     * Get all session values
     */
    public function all(): array
    {
        $this->ensureStarted();
        
        return $_SESSION;
    }
    
    /**
     * Clear all session values
     */
    public function clear(): void
    {
        $this->ensureStarted();
        
        $_SESSION = [];
    }
    
    /**
     * Destroy the session
     */
    public function destroy(): bool
    {
        if (!$this->started) {
            return true;
        }
        
        // Clear the session data
        $this->clear();
        
        // Destroy the session
        $result = session_destroy();
        
        // Clear the session cookie
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        
        $this->started = false;
        
        return $result;
    }
    
    /**
     * Regenerate the session ID
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        
        return session_regenerate_id($deleteOldSession);
    }
    
    /**
     * Get the session ID
     */
    public function getId(): string
    {
        $this->ensureStarted();
        
        return session_id();
    }
    
    /**
     * Set the session ID
     */
    public function setId(string $id): void
    {
        if ($this->started) {
            throw new \RuntimeException('Cannot change the session ID after the session has been started');
        }
        
        session_id($id);
    }
    
    /**
     * Flash a value to the session
     */
    public function flash(string $key, $value): void
    {
        $this->ensureStarted();
        
        $this->set('_flash.' . $key, $value);
    }
    
    /**
     * Get a flashed value from the session
     */
    public function getFlash(string $key, $default = null)
    {
        $this->ensureStarted();
        
        $value = $this->get('_flash.' . $key, $default);
        
        $this->remove('_flash.' . $key);
        
        return $value;
    }
    
    /**
     * Check if a flashed value exists in the session
     */
    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();
        
        return $this->has('_flash.' . $key);
    }
    
    /**
     * Ensure the session has been started
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }
}

