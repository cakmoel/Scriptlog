<?php 
/**
 * DbInterface interface
 * Describe the functionality
 * that any database adapter will need.
 *
 * @package   SCRIPTLOG/LIB/CORE/DbInterface
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
 * set database connection
 * 
 * @method public setDbConnection()
 * @param array $values
 * @param array $options
 * 
 */
 public function setDbConnection($values = [], $options = []);
 
/**
 * Close database connection
 * 
 * @method public closeDbConnection()
 * 
 */
 public function closeDbConnection();
 
/**
 * Query
 * 
 * @method public dbQuery()
 * @param string $sql
 * @param string $parameters default NULL
 * 
 */
 public function dbQuery($sql, $parameters = null);
 
/**
 * Insert new record
 * 
 * @method public dbInsert()
 * @param string $tablename
 * @param array $params
 * 
 */
 public function dbInsert($tablename, array $params);
 
/**
 * Update record
 * 
 * @method public dbUpdate()
 * @param string $tablename
 * @param string $params
 * @param string $where
 * 
 */
 public function dbUpdate($tablename, $params, $where);
 
/**
 * Delete record
 * 
 * @method public dbDelete()
 * @param string $tablename
 * @param string $where
 * @param numeric|integer $limit default null
 * 
 */
 public function dbDelete($tablename, $where, $limit = null);
 
/**
 * Last insert Id
 * 
 * @method public dbLastInsertId()
 */
 public function dbLastInsertId();
    
}