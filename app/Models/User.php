<?php

declare(strict_types=1);

namespace App\Models;

use Flux\Database\Model;

class User extends Model
{
    /**
     * The table associated with the model
     */
    protected string $table = 'users';
    
    /**
     * The primary key for the model
     */
    protected string $primaryKey = 'id';
    
    /**
     * Find a user by their email
     */
    public static function findByEmail(string $email): ?self
    {
        $result = static::query()->where('email', $email)->first();
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }
    
    /**
     * Find a user by their provider ID
     */
    public static function findByProvider(string $provider, string $providerId): ?self
    {
        $result = static::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }
}

