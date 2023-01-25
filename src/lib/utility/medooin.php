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

  $read_config = AppConfig::readConfiguration(invoke_config());

  $configuration = [
    'type' => 'mysql',
    'host' => $read_config['db']['host'],
    'database' => $read_config['db']['name'],
    'username' => $read_config['db']['user'],
    'password' => $read_config['db']['pass']
  ];

  return MedooInit::connect($configuration);

}

/**
 * medoo_columns
 *
 * @param string $select
 * @param string|array $columns
 * 
 */
function medoo_column($table, $columns)
{
  $database = medoo_init();
  return $database->select($table, $columns);
}

/**
 * medoo_column_where
 *
 * @param string $table
 * @param string|array $column
 * @param array|option $where
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
  return $database->select($table, $columns, $where, function ($data) {
    return $data;
  });
}

/**
 * medoo_get_where
 *
 * @param string $table
 * @param string|array $columns
 * @param optional|array $where
 * 
 */
function medoo_get_where($table, $columns, $where)
{
 $database = medoo_init();
 return $database->get($table, $columns, $where);
}

/**
 * medoo_get_join
 *
 * @param string $table
 * @param array $join
 * @param string|array $columns
 * @param [type] $where
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
