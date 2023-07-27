<?php
/**
 * medoo_init
 *
 * @category function
 * @author M.Noermoehammad 
 * @license MIT
 * @version 1.0
 * 
 */
function medoo_init()
{

  $read_config = class_exists('AppConfig') ? AppConfig::readConfiguration(invoke_config()) : null;

  $configuration = [
    'type'     => 'mysql',
    'host'     => isset($read_config['db']['host']) ? $read_config['db']['host'] : "",
    'database' => isset($read_config['db']['name']) ? $read_config['db']['name'] : "",
    'username' => isset($read_config['db']['user']) ? $read_config['db']['user'] : "",
    'password' => isset($read_config['db']['pass']) ? $read_config['db']['pass'] : "",
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_general_ci',
    'port'     => isset($read_config['db']['port']) ? $read_config['db']['port'] : ""
  ];

  return (class_exists('MedooInit')) ? MedooInit::connect($configuration) : "";

}

/**
 * medoo_column()
 * 
 * select data from the table
 *
 * @category function
 * @param string $select
 * @param string|array $columns
 * @return array|mixed
 * 
 */
function medoo_column($table, $columns)
{
  $database = medoo_init();
  return $database->select($table, $columns);
}

/**
 * medoo_column_where()
 *
 * @category function
 * @param string $table
 * @param string|array $column
 * @param array|option $where
 * @return mixed|array
 * 
 */
function medoo_column_where($table, $columns, $where)
{
  $database = medoo_init();
  return $database->select($table, $columns, $where);
}

/**
 * medoo_join
 *
 * @param string $table
 * @param array $join
 * @param array|string $columns
 * @param array $where
 * 
 */
function medoo_join($table, $join, $columns, $where)
{
  $database = medoo_init();
  return $database->select($table, $join, $columns, $where);
}

/**
 * medoo_fetch_callback
 *
 * @param string $table
 * @param array $column
 * @param array $where
 * 
 */
function medoo_fetch_callback($table, $columns, $where)
{
  $database = medoo_init();

  if (method_exists($database, 'select')) {

    return $database->select($table, $columns, $where, function ($data) {
      return $data;
    });
    
  }
  
}

/**
 * medoo_get_where
 *
 * @param string $table
 * @param string|array $columns
 * @param optional|array $where
 * @see https://medoo.in/api/get
 * 
 */
function medoo_get_where($table, $columns, $where)
{
 $database = medoo_init();
 return (method_exists($database, 'get')) ? $database->get($table, $columns, $where) : "";
}

/**
 * medoo_get_join
 *
 * @param string $table
 * @param array $join
 * @param string|array $columns
 * @param array $where
 * @return void
 */
function medoo_get_join($table, $join, $columns, $where)
{
  $database = medoo_init();
  return $database->get($table, $join, $columns, $where);
}

/**
 * medoo_insert
 *
 * @param string $table
 * @param array $values
 * 
 */
function medoo_insert($table, $values)
{
  $database = medoo_init();
  return $database->insert($table, $values);
}

/**
 * medoo_update
 *
 * @param string $table
 * @param array $data
 * @param array $where
 * @return object PDOStatement
 */
function medoo_update($table, $column, $where)
{
  $database = medoo_init();
  return (method_exists($database, 'update')) ? $database->update($table, $column, $where) : "";
}

/**
 * medoo_replace
 *
 * @param string $table
 * @param array $column
 * @param array $where
 * @return object PDOStatement
 * 
 */
function medoo_replace($table, $column, $where)
{
  $database = medoo_init();
  return (method_exists($database, 'replace')) ? $database->replace($table, $column, $where) : "";
}

/**
 * medoo_delete
 *
 * @param string $table
 * @param array $where
 * @return object PDOStatement
 */
function medoo_delete($table, $where)
{
  $database = medoo_init();
  return (method_exists($database, 'delete')) ? $database->delete($table, $where) : "";
}