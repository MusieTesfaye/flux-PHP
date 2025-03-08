<?php

declare(strict_types=1);

namespace Flux\Auth\Providers;

use Flux\Auth\User;
use Flux\Config\ConfigManager;
use Flux\Http\Request;
use Flux\Database\Connection;

class NativeProvider implements AuthProviderInterface
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var Connection|null The database connection
     */
    private ?Connection $db = null;
    
    /**
     * Create a new native provider instance
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get the database connection
     */
    private function getDb(): Connection
    {
        if ($this->db === null) {
            $dbConfig = $this->config->get('database.connections.' . $this->config->get('database.default'));
            $this->db = new Connection($dbConfig);
        }
        
        return $this->db;
    }
    
    /**
     * Attempt to authenticate a user with the given credentials
     */
    public function attempt(array $credentials): ?User
    {
        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            return null;
        }
        
        $db = $this->getDb();
        $user = $db->table('users')
            ->where('email', $credentials['email'])
            ->first();
        
        if (!$user) {
            return null;
        }
        
        if (!password_verify($credentials['password'], $user['password'])) {
            return null;
        }
        
        return $this->mapUserFromDatabase($user);
    }
    
    /**
     * Retrieve a user by their ID
     */
    public function retrieveById(string $id): ?User
    {
        $db = $this->getDb();
        $user = $db->table('users')
            ->where('id', $id)
            ->first();
        
        if (!$user) {
            return null;
        }
        
        return $this->mapUserFromDatabase($user);
    }
    
    /**
     * Validate a token
     */
    public function validateToken(string $token): ?User
    {
        // Native provider doesn't support token authentication
        return null;
    }
    
    /**
     * Handle an OAuth callback
     */
    public function handleOAuthCallback(Request $request): ?User
    {
        // Native provider doesn't support OAuth
        return null;
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function getOAuthUrl(): string
    {
        // Native provider doesn't support OAuth
        return '';
    }
    
    /**
     * Map a user from the database
     */
    private function mapUserFromDatabase(array $user): User
    {
        // Remove the password from the attributes
        $attributes = $user;
        unset($attributes['id'], $attributes['name'], $attributes['email'], $attributes['password']);
        
        return new User(
            (string) $user['id'],
            $user['name'],
            $user['email'],
            'native',
            $attributes
        );
    }
    
    /**
     * Create a new user
     */
    public function createUser(string $name, string $email, string $password): User
    {
        $db = $this->getDb();
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $db->table('users')->insert([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $id = $db->lastInsertId();
        
        return new User(
            $id,
            $name,
            $email,
            'native',
            []
        );
    }
}

