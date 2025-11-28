<?php defined('SCRIPTLOG') || die("Direct access not permitted.");
/**
 * interface DbInterface
 * Describe the functionality
 * that any database adapter will need.
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT 
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
interface DbInterface
{
    /**
     * Set database connection.
     * 
     * @param array $config
     * @param array $options 
     * @return void
     */
    public function setDbConnection(array $config, array $options = []): void;

    /**
     * Close database connection.
     */
    public function closeDbConnection(): void;

    /**
     * Execute an SQL query.
     * 
     * @param string $sql 
     * @param array $parameters
     * @return mixed
     */
    public function dbQuery(string $sql, array $parameters = []): mixed;

    /**
     * Insert a new record.
     * 
     * @param string $tablename
     * @param array $params 
     * @return boolean
     * 
     */
    public function dbInsert(string $tablename, array $params): bool;

    /**
     * Insert ... On Duplicate Key Update.
     * 
     * Replace statement to Insert or Update row. The correct way to do REPLACE INTO 
     * 
     * @param string $tablename
     * @param array $params
     * @param array $updateParams
     * @return boolean
     */
    public function dbReplace(string $tablename, array $params, array $updateParams): bool;

    /**
     * Update an existing record.
     * 
     * @param string $tablename 
     * @param array $params 
     * @param array $where
     * @return int 
     */
    public function dbUpdate(string $tablename, array $params, array $where): int;

    /**
     * Delete a record.
     * 
     * @param string $tablename
     * @param array $where 
     * @param int $limit default null 
     * @return int
     */
    public function dbDelete(string $tablename, array $where, ?int $limit = null): int;

    /**
     * Get the last inserted ID.
     */
    public function dbLastInsertId(): string;

    /**
     * Start a transaction.
     */
    public function dbTransaction(): bool;

    /**
     * Commit the current transaction.
     */
    public function dbCommit(): bool;

    /**
     * Rollback the current transaction.
     */
    public function dbRollBack(): bool;

    /**
     * Check if a database connection is active.
     */
    public function isConnected(): bool;
}