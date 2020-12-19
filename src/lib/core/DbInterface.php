<?php 
/**
 * DbInterface interface
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
 public function dbQuery($sql, $parameters = array());
 
/**
 * dbInsert
 * 
 * insert new record
 * 
 * @method public dbInsert()
 * @param string $tablename
 * @param array $params
 * 
 */
 public function dbInsert($tablename, array $params);
 
/**
 * dbUpdate()
 * 
 * update existing record
 * 
 * @method public dbUpdate()
 * @param string $tablename
 * @param array $params
 * @param string $where
 * 
 */
 public function dbUpdate($tablename, $params, $where);

/**
 * dbReplace()
 * 
 * replace statement to insert or update row
 * 
 * @param string $tablename
 * @param array $params
 * @param string $to
 * @return void
 * 
 */
 public function dbReplace($tablename, $params, $to);

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
 * 
 */
 public function dbLastInsertId();

/**
 * dbTransaction
 * begin transaction for multiple queries as a unified block 
 *
 * @method public dbTransaction()
 * 
 */
 public function dbTransaction();

/**
 * dbCommit
 * commit the transaction if no problems have been encountered
 *
 * @method public dbCommit()
 * 
 */
 public function dbCommit();

/**
 * dbRollBack
 * If any errors are detected, you can roll back all tables to their 
 * original state at the end of the sequence.
 *
 * @method public dbRollBack()
 * 
 */
 public function dbRollBack();

}