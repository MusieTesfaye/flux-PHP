<?php

declare(strict_types=1);

namespace Flux\Database;

class QueryBuilder
{
    /**
     * @var \PDO The database connection
     */
    private \PDO $connection;
    
    /**
     * @var string The table name
     */
    private string $table;
    
    /**
     * @var array The select columns
     */
    private array $columns = ['*'];
    
    /**
     * @var array The where conditions
     */
    private array $wheres = [];
    
    /**
     * @var array The order by clauses
     */
    private array $orders = [];
    
    /**
     * @var int|null The limit
     */
    private ?int $limit = null;
    
    /**
     * @var int|null The offset
     */
    private ?int $offset = null;
    
    /**
     * Create a new query builder instance
     */
    public function __construct(\PDO $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }
    
    /**
     * Select columns
     */
    public function select($columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Add a where condition
     */
    public function where(string $column, $operator, $value = null): self
    {
        // If only two arguments are provided, assume equals operator
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        
        return $this;
    }
    
    /**
     * Add an order by clause
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'DESC' : 'ASC',
        ];
        
        return $this;
    }
    
    /**
     * Set the limit
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * Set the offset
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * Get all results
     */
    public function get(): array
    {
        $query = $this->buildSelectQuery();
        $statement = $this->connection->prepare($query['sql']);
        $statement->execute($query['bindings']);
        
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the first result
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        
        return $results[0] ?? null;
    }
    
    /**
     * Insert a record
     */
    public function insert(array $values): bool
    {
        $columns = array_keys($values);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $statement = $this->connection->prepare($query);
        
        foreach ($values as $column => $value) {
            $statement->bindValue(":$column", $value);
        }
        
        return $statement->execute();
    }
    
    /**
     * Update records
     */
    public function update(array $values): int
    {
        $sets = [];
        $bindings = [];
        
        foreach ($values as $column => $value) {
            $sets[] = "$column = :update_$column";
            $bindings["update_$column"] = $value;
        }
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        $whereQuery = $this->buildWhereClause();
        if ($whereQuery['sql']) {
            $query .= ' WHERE ' . $whereQuery['sql'];
            $bindings = array_merge($bindings, $whereQuery['bindings']);
        }
        
        $statement = $this->connection->prepare($query);
        
        foreach ($bindings as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : ":$key", $value);
        }
        
        $statement->execute();
        
        return $statement->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete(): int
    {
        $query = "DELETE FROM {$this->table}";
        
        $whereQuery = $this->buildWhereClause();
        if ($whereQuery['sql']) {
            $query .= ' WHERE ' . $whereQuery['sql'];
        }
        
        $statement = $this->connection->prepare($query);
        
        foreach ($whereQuery['bindings'] as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : ":$key", $value);
        }
        
        $statement->execute();
        
        return $statement->rowCount();
    }
    
    /**
     * Build the select query
     */
    private function buildSelectQuery(): array
    {
        $query = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        $bindings = [];
        
        // Add where clauses
        $whereQuery = $this->buildWhereClause();
        if ($whereQuery['sql']) {
            $query .= ' WHERE ' . $whereQuery['sql'];
            $bindings = array_merge($bindings, $whereQuery['bindings']);
        }
        
        // Add order by clauses
        if (!empty($this->orders)) {
            $orders = array_map(function($order) {
                return $order['column'] . ' ' . $order['direction'];
            }, $this->orders);
            
            $query .= ' ORDER BY ' . implode(', ', $orders);
        }
        
        // Add limit and offset
        if ($this->limit !== null) {
            $query .= ' LIMIT ' . $this->limit;
            
            if ($this->offset !== null) {
                $query .= ' OFFSET ' . $this->offset;
            }
        }
        
        return [
            'sql' => $query,
            'bindings' => $bindings,
        ];
    }
    
    /**
     * Build the where clause
     */
    private function buildWhereClause(): array
    {
        if (empty($this->wheres)) {
            return ['sql' => '', 'bindings' => []];
        }
        
        $conditions = [];
        $bindings = [];
        
        foreach ($this->wheres as $i => $where) {
            $placeholder = "where_{$i}";
            $conditions[] = "{$where['column']} {$where['operator']} :$placeholder";
            $bindings[$placeholder] = $where['value'];
        }
        
        return [
            'sql' => implode(' AND ', $conditions),
            'bindings' => $bindings,
        ];
    }
}

