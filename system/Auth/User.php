<?php

declare(strict_types=1);

namespace Flux\Auth;

class User
{
    /**
     * @var string The user ID
     */
    private string $id;
    
    /**
     * @var string The user's name
     */
    private string $name;
    
    /**
     * @var string The user's email
     */
    private string $email;
    
    /**
     * @var array Additional user attributes
     */
    private array $attributes = [];
    
    /**
     * @var string The authentication provider
     */
    private string $provider;
    
    /**
     * Create a new user instance
     */
    public function __construct(string $id, string $name, string $email, string $provider, array $attributes = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->provider = $provider;
        $this->attributes = $attributes;
    }
    
    /**
     * Get the user ID
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the user's name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get the user's email
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    
    /**
     * Get the authentication provider
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
    
    /**
     * Get a user attribute
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
    
    /**
     * Get all user attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Check if the user has a specific attribute
     */
    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Convert the user to an array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'provider' => $this->provider,
            'attributes' => $this->attributes,
        ];
    }
}

