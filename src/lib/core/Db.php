<?php defined('SCRIPTLOG') || die("Direct access not permitted.");
/**
 * Class Db implements DbInterface
 * 
 * This class provides a database abstraction layer using PDO with MySQL functionality.
 * It implements all methods defined in DbInterface for consistent database operations.
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 * @see       DbInterface
 *
 */

class Db implements DbInterface
{
    /**
     * PDO database connection instance
     * 
     * @var PDO|null
     */
    private ?PDO $dbc = null;

    /**
     * Path to SSL CA certificate file for secure connections
     * 
     * @var string|null
     */
    private ?string $caPath = null;

    /**
     * Table name prefix
     * 
     * @var string
     */
    private string $tablePrefix = '';

    /**
     * Known table names for prefix replacement
     * 
     * @var array
     */
    private array $knownTables = [
        'tbl_users',
        'tbl_user_token',
        'tbl_posts',
        'tbl_topics',
        'tbl_post_topic',
        'tbl_comments',
        'tbl_media',
        'tbl_mediameta',
        'tbl_media_download',
        'tbl_menu',
        'tbl_plugin',
        'tbl_settings',
        'tbl_themes'
    ];

    /**
     * Constructor - Initializes the database connection if config is provided
     * 
     * @param array $config Database configuration [DSN, username, password]
     * @param array $options Additional PDO connection options
     */
    public function __construct(array $config = [], array $options = [])
    {
        if (!empty($config)) {
            $this->setDbConnection($config, $options);
        }
    }

    /**
     * Set table prefix
     * 
     * @param string $prefix
     */
    public function setTablePrefix(string $prefix): void
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * Get table prefix
     * 
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Establishes a database connection using PDO
     * 
     * @param array $config Database configuration [DSN, username, password]
     * @param array $options Additional PDO connection options
     * @return void
     * @throws InvalidArgumentException If configuration is incomplete
     * @throws RuntimeException If connection fails
     */
    public function setDbConnection(array $config = [], array $options = []): void
    {
        if (count($config) < 3) {
            throw new InvalidArgumentException("Database configuration array must contain DSN, username, and password.");
        }

        [$dsn, $dbUser, $dbPass] = $config;

        $defaultOptions = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci',
        ];

        if ($this->caPath) {
            $defaultOptions[PDO::MYSQL_ATTR_SSL_CA] = $this->caPath;
        }

        try {
            $this->dbc = new PDO($dsn, $dbUser, $dbPass, array_replace($defaultOptions, $options));
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Checks if database connection is active
     * 
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->dbc !== null;
    }

    /**
     * Closes the database connection
     * 
     * @return void
     */
    public function closeDbConnection(): void
    {
        $this->dbc = null;
    }

    /**
     * Ensures database connection is established before operations
     * 
     * @return void
     * @throws RuntimeException If connection is not established
     */
    private function ensureConnection(): void
    {
        if (!$this->dbc) {
            throw new RuntimeException("Database connection is not established.");
        }
    }

    /**
     * Executes a database query and returns the statement
     * 
     * @param string $sql SQL query to execute
     * @param array $args Parameters for prepared statement
     * @return PDOStatement Executed statement
     * @throws RuntimeException If connection is not established
     */
    public function dbQuery(string $sql, array $args = []): PDOStatement
    {
        $this->ensureConnection();
        $sql = $this->applyTablePrefix($sql);
        $stmt = $this->dbc->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }

    /**
     * Executes a SELECT query and returns results
     * 
     * @param string $sql SQL SELECT query
     * @param array $parameters Query parameters
     * @param int $fetchMode PDO fetch mode (default: PDO::FETCH_OBJ)
     * @param string $class Class name for PDO::FETCH_CLASS mode
     * @return array Fetched results
     * @throws RuntimeException If connection is not established
     */
    public function dbSelect(string $sql, array $parameters = [], int $fetchMode = PDO::FETCH_OBJ, string $class = ''): array
    {
        $this->ensureConnection();
        $sql = $this->applyTablePrefix($sql);

        $stmt = $this->dbc->prepare($sql);
        $stmt->execute($parameters);

        return $fetchMode === PDO::FETCH_CLASS ?
            $stmt->fetchAll($fetchMode, $class) :
            $stmt->fetchAll($fetchMode);
    }

    /**
     * Inserts a new record into the specified table
     * 
     * @param string $tablename Name of the table
     * @param array $params Associative array of column => value pairs
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException If parameters are empty
     * @throws RuntimeException If connection is not established
     */
    public function dbInsert(string $tablename, array $params): bool
    {
        $this->ensureConnection();

        if (empty($params)) {
            throw new InvalidArgumentException("Insert parameters cannot be empty.");
        }

        // Apply prefix to table name
        $tablename = $this->applyTablePrefix($tablename);

        $fields = array_keys($params);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $tablename,
            implode(", ", $fields),
            implode(", ", $placeholders)
        );

        $stmt = $this->dbc->prepare($sql);
        return $stmt->execute(array_values($params));
    }

    /**
     * Returns the last inserted ID
     * 
     * @return string Last inserted row ID
     * @throws RuntimeException If connection is not established
     */
    public function dbLastInsertId(): string
    {
        $this->ensureConnection();
        return $this->dbc->lastInsertId();
    }

    /**
     * Updates records in the specified table
     * 
     * @param string $tablename Name of the table
     * @param array $params Associative array of column => value pairs to update
     * @param array $where Associative array of conditions for WHERE clause
     * @return int Number of affected rows
     * @throws InvalidArgumentException If parameters or conditions are empty
     * @throws RuntimeException If connection is not established
     */
    public function dbUpdate(string $tablename, array $params, array $where): int
    {
        $this->ensureConnection();

        if (empty($params) || empty($where)) {
            throw new InvalidArgumentException("Update parameters and conditions cannot be empty.");
        }

        // Apply prefix to table name
        $tablename = $this->applyTablePrefix($tablename);

        $setClause = implode(", ", array_map(fn($key) => "$key = ?", array_keys($params)));
        $whereClause = implode(" AND ", array_map(fn($key) => "$key = ?", array_keys($where)));

        $sql = "UPDATE `$tablename` SET $setClause WHERE $whereClause";

        $stmt = $this->dbc->prepare($sql);
        $stmt->execute(array_merge(array_values($params), array_values($where)));

        return $stmt->rowCount();
    }

    /**
     * Performs an INSERT or UPDATE (upsert) operation
     * 
     * Inserts a new record or updates existing one if duplicate key exists
     * 
     * @param string $tablename Name of the table
     * @param array $params Associative array of column => value pairs for insert
     * @param array $updateParams Associative array of column => value pairs for update
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException If parameters are empty
     * @throws RuntimeException If connection is not established
     */
    public function dbReplace(string $tablename, array $params, array $updateParams): bool
    {
        $this->ensureConnection();

        if (empty($params) || empty($updateParams)) {
            throw new InvalidArgumentException("Upsert parameters cannot be empty.");
        }

        // Apply prefix to table name
        $tablename = $this->applyTablePrefix($tablename);

        $fields = array_keys($params);
        $placeholders = array_fill(0, count($fields), '?');

        $updateFields = array_keys($updateParams);
        $updateAssignments = implode(", ", array_map(fn($field) => "$field = ?", $updateFields));

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
            $tablename,
            implode(", ", $fields),
            implode(", ", $placeholders),
            $updateAssignments
        );

        $stmt = $this->dbc->prepare($sql);
        return $stmt->execute(array_merge(array_values($params), array_values($updateParams)));
    }

    /**
     * Deletes records from the specified table
     * 
     * @param string $tablename Name of the table
     * @param array $where Associative array of conditions for WHERE clause
     * @param int|null $limit Optional maximum number of rows to delete
     * @return int Number of affected rows
     * @throws InvalidArgumentException If conditions are empty
     * @throws RuntimeException If connection is not established
     */
    public function dbDelete(string $tablename, array $where, ?int $limit = null): int
    {
        $this->ensureConnection();

        if (empty($where)) {
            throw new InvalidArgumentException("Delete conditions cannot be empty.");
        }

        // Apply prefix to table name
        $tablename = $this->applyTablePrefix($tablename);

        $whereClause = implode(" AND ", array_map(fn($key) => "$key = ?", array_keys($where)));

        $sql = "DELETE FROM `$tablename` WHERE $whereClause";

        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->dbc->prepare($sql);
        $stmt->execute(array_values($where));

        return $stmt->rowCount();
    }

    /**
     * Begins a transaction
     * 
     * @return bool True on success, false on failure
     * @throws RuntimeException If connection is not established
     */
    public function dbTransaction(): bool
    {
        $this->ensureConnection();
        return $this->dbc->beginTransaction();
    }

    /**
     * Commits a transaction
     * 
     * @return bool True on success, false on failure
     * @throws RuntimeException If connection is not established
     */
    public function dbCommit(): bool
    {
        $this->ensureConnection();
        return $this->dbc->commit();
    }

    /**
     * Rolls back a transaction
     * 
     * @return bool True on success, false on failure
     * @throws RuntimeException If connection is not established
     */
    public function dbRollBack(): bool
    {
        $this->ensureConnection();
        return $this->dbc->rollBack();
    }

    /**
     * Apply table prefix to SQL query
     * 
     * @param string $sql
     * @return string
     */
    private function applyTablePrefix(string $sql): string
    {
        if (empty($this->tablePrefix)) {
            return $sql;
        }

        foreach ($this->knownTables as $table) {
            // Match table name not already prefixed
            $pattern = '/(?<![a-zA-Z_])' . preg_quote($table, '/') . '(?![a-zA-Z_])/';
            $sql = preg_replace($pattern, $this->tablePrefix . $table, $sql);
        }

        return $sql;
    }
}
