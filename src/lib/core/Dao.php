<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Dao Class
 *
 * Data Access Object
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Dao
{
    /**
     * Database connection
     * @var object
     *
     */
    protected $dbc;

    /**
     * SQL
     * @var string
     */
    protected $sql;

    /**
     * Error
     * @var string
     */
    protected $error;

    /**
     * Sanitize
     * @var object
     *
     */
    protected $sanitizing;

    /**
     * Table prefix
     * @var string
     */
    protected $prefix;

    public function __construct()
    {
        if (!Registry::isKeySet('dbc')) {
            throw new DbException("Database connection (dbc) is not set in the Registry ");
        }

        $this->dbc = Registry::get('dbc');

        if ($this->dbc === null) {
            throw new DbException("Database connection (dbc) is null in the Registry");
        }

        $this->prefix = get_table_prefix();
    }

    /**
     * Get prefixed table name
     *
     * @param string $table
     * @return string
     */
    protected function table($table)
    {
        return $this->prefix . $table;
    }

    /**
     * setSQL
     *
     * @param string $sql
     *
     */
    protected function setSQL($sql)
    {
        $this->sql = $sql;
    }

    /**
     * Find All records
     * getting array of rows
     *
     * @param array $data
     * @param PDO::FETCH_MODE static $fetchMode
     * @throws DbException
     * @return array|object
     */
    protected function findAll(array $data = array(), $fetchMode = null)
    {

        if (!$this->sql) {
            throw new DbException("No SQL Query");
        }

        if (!is_null($fetchMode)) {
            return $this->dbc->dbQuery($this->sql, $data)->fetchAll($fetchMode);
        }

        return $this->dbc->dbQuery($this->sql, $data)->fetchAll();
    }

    /**
     * Find Single row record
     * getting one row
     *
     * @param array $data
     * @param PDO::FETCH_MODE static $fetchMode
     * @throws DbException
     * @return mixed
     *
     */
    protected function findRow(array $data = array(), $fetchMode = null)
    {

        if (!$this->sql) {
            throw new DbException("No SQL Query!");
        }

        if (!is_null($fetchMode)) {
            return $this->dbc->dbQuery($this->sql, $data)->fetch($fetchMode);
        }

        return $this->dbc->dbQuery($this->sql, $data)->fetch();
    }

    /**
     * Find Column
     * return a single column from the next row of results set
     * getting single field value
     *
     * @param array $data
     * @param PDO::FETCH_MODE static $fetchMode
     * @throws DbException
     * @return boolean false if no more rows
     */
    protected function findColumn(array $data = array(), $fetchMode = null)
    {

        if (!$this->sql) {
            throw new DbException("No SQL Query!");
        }

        if (!is_null($fetchMode)) {
            return $this->dbc->dbQuery($this->sql, $data)->fetchColumn($fetchMode);
        }

        return $this->dbc->dbQuery($this->sql, $data)->fetchColumn();
    }

    /**
     * CheckCountValue function
     *
     * @param array $data
     * @throws DbException
     * @return integer|numeric|null
     *
     */
    public function checkCountValue(array $data = []): ?int
    {

        if (!$this->sql) {
            throw new DbException("No SQL Query!");
        }

        $stmt = $this->dbc->dbQuery($this->sql, $data);

        $rowCount = $stmt->rowCount();

        return $rowCount > 0 ? $rowCount : 0;
    }

    /**
     * Create records
     *
     * @param string $table
     * @param array $params
     *
     */
    protected function create($table, $params)
    {
        $this->dbc->dbInsert($table, $params);
    }

    /**
     * Modify record
     *
     * @param string $table
     * @param array $params
     * @param array $where
     *
     */
    protected function modify($table, $params, $where)
    {
        $this->dbc->dbUpdate($table, $params, $where);
    }

    /**
     * deleteRecord()
     *
     * @param string $table
     * @param array $where
     * @param integer $limit
     */
    protected function deleteRecord($table, $where, $limit = 1)
    {
        (is_numeric($limit)) ? $this->dbc->dbDelete($table, $where, $limit) : $this->dbc->dbDelete($table, $where);
    }

    /**
     * replaceRecord()
     *
     * @param string $table
     * @param array $params
     * @param string $to
     *
     */
    protected function replaceRecord($table, $params, $to)
    {
        $this->dbc->dbReplace($table, $params, $to);
    }

    /**
     * callTransaction
     * begin transaction for multiple queries as a unified block
     *
     */
    protected function callTransaction()
    {
        $this->dbc->dbTransaction();
    }

    /**
     * callCommit
     * commit the transaction if no problems have been encountered
     *
     */
    protected function callCommit()
    {
        $this->dbc->dbCommit();
    }

    /**
     * callRollBack
     * to roll back the tables to their original state.
     *
     */
    protected function callRollBack()
    {
        $this->dbc->dbRollBack();
    }

    /**
     * closeConnection
     *
     */
    protected function closeConnection()
    {
        $this->dbc->closeDbConnection();
    }

    /**
     * lastId
     *
     * @return integer
     *
     */
    protected function lastId()
    {
        return $this->dbc->dbLastInsertId();
    }

    /**
     * Filter and sanitize input values
     *
     * @param Sanitize $sanitize Sanitizer instance
     * @param mixed $str Input value to filter
     * @param string $type Sanitization type ('sql' or 'xss')
     * @return int|string Sanitized value as integer (sql) or string (xss)
     * @throws InvalidArgumentException When input is invalid
     */
    protected function filteringId(Sanitize $sanitize, $str, $type)
    {
        $this->sanitizing = $sanitize;

        // If $str is null or empty, throw exception immediately
        if ($str === null || $str === '') {
            throw new InvalidArgumentException("Input value cannot be empty");
        }

        // Convert to appropriate type based on $type
        switch ($type) {
            case 'sql':
                // First validate it's an integer
                $intVal = filter_var($str, FILTER_VALIDATE_INT);

                if ($intVal === false || $intVal <= 0) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "Invalid ID: '%s' - must be a positive integer",
                            is_scalar($str) ? (string)$str : gettype($str)
                        )
                    );
                }

                // Now sanitize the validated integer
                $sanitized = $this->sanitizing->sanitasi((string)$intVal, 'sql');

                // Return as integer for database safety
                return (int)$sanitized;

            case 'xss':
                // Validate is a string
                if (!is_string($str) || empty(trim($str))) {
                    throw new InvalidArgumentException("Input value for XSS sanitization must be a non-empty string");
                }

                // Sanitize the string
                $sanitized = $this->sanitizing->sanitasi(prevent_injection($str), 'xss');

                // Return sanitized string
                return $sanitized;

            default:
                throw new InvalidArgumentException(
                    sprintf("Invalid sanitization type: '%s'. Allowed types: 'sql', 'xss'", $type)
                );
        }
    }
}
