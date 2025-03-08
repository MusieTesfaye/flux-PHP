<?php

declare(strict_types=1);

namespace Flux\Database;

abstract class Model
{
    /**
     * @var string The table name
     */
    protected string $table;
    
    /**
     * @var string The primary key
     */
    protected string $primaryKey = 'id';
    
    /**
     * @var array The model attributes
     */
    protected array $attributes = [];
    
    /**
     * @var array The original attributes
     */
    protected array $original = [];
    
    /**
     * @var Connection The database connection
     */
    protected static Connection $connection;
    
    /**
     * Create a new model instance
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    /**
     * Set the database connection
     */
    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }
    
    /**
     * Get the database connection
     */
    public static function getConnection(): Connection
    {
        return static::$connection;
    }
    
    /**
     * Get the table name
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }
        
        // Derive table name from class name
        $class = get_class($this);
        $parts = explode('\\', $class);
        $className = end($parts);
        
        // Convert camel case to snake case and pluralize
        $table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className)) . 's';
        
        return $table;
    }
    
    /**
     * Fill the model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Set an attribute
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    /**
     * Get an attribute
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
    
    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     */
    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Create a new query builder for the model
     */
    public static function query(): QueryBuilder
    {
        $model = new static();
        return static::$connection->table($model->getTable());
    }
    
    /**
     * Find a model by its primary key
     */
    public static function find($id)
    {
        $model = new static();
        $result = static::query()->where($model->primaryKey, $id)->first();
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }
    
    /**
     * Get all models
     */
    public static function all(): array
    {
        $results = static::query()->get();
        
        return array_map(function($attributes) {
            return new static($attributes);
        }, $results);
    }
    
    /**
     * Create a new model
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();
        
        return $model;
    }
    
    /**
     * Save the model
     */
    public function save(): bool
    {
        if (isset($this->attributes[$this->primaryKey])) {
            // Update existing model
            $updated = static::query()
                ->where($this->primaryKey, $this->attributes[$this->primaryKey])
                ->update($this->attributes);
            
            return $updated > 0;
        } else {
            // Create new model
            $result = static::query()->insert($this->attributes);
            
            if ($result) {
                $this->attributes[$this->primaryKey] = static::$connection->lastInsertId();
            }
            
            return $result;
        }
    }
    
    /**
     * Delete the model
     */
    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $deleted = static::query()
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();
        
        return $deleted > 0;
    }
}

