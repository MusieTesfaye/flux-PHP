<?php

declare(strict_types=1);

namespace Flux\Database;

class Connection
{
    /**
     * @var \PDO The PDO connection
     */
    private \PDO $pdo;
    
    /**
     * Create a new database connection
     */
    public function __construct(array $config)
    {
        $dsn = $this->buildDsn($config);
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $options = $config['options'] ?? [];
        
        // Set default options
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        $options[\PDO::ATTR_EMULATE_PREPARES] = false;
        
        $this->pdo = new \PDO($dsn, $username, $password, $options);
    }
    
    /**
     * Build the DSN string
     */
    private function buildDsn(array $config): string
    {
        $driver = $config['driver'] ?? 'mysql';
        
        switch ($driver) {
            case 'mysql':
                return $this->buildMysqlDsn($config);
            case 'pgsql':
                return $this->buildPgsqlDsn($config);
            case 'sqlite':
                return $this->buildSqliteDsn($config);
            default:
                throw new \InvalidArgumentException("Unsupported database driver: $driver");
        }
    }
    
    /**
     * Build a MySQL DSN
     */
    private function buildMysqlDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        
        return "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
    }
    
    /**
     * Build a PostgreSQL DSN
     */
    private function buildPgsqlDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';
        
        return "pgsql:host=$host;port=$port;dbname=$database";
    }
    
    /**
     * Build a SQLite DSN
     */
    private function buildSqliteDsn(array $config): string
    {
        $path = $config['database'] ?? ':memory:';
        return "sqlite:$path";
    }
    
    /**
     * Get the PDO instance
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
    
    /**
     * Create a new query builder
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this->pdo, $table);
    }
    
    /**
     * Execute a raw query
     */
    public function query(string $query, array $bindings = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);
        
        return $statement;
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Get the last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}

