<?php
/**
 * Sanitize_sql_orderby function
 * Ensures a string is a valid SQL ‘order by’ clause.
 * 
 * @param string $orderby
 * @see https://developer.wordpress.org/reference/functions/sanitize_sql_orderby/
 * @return bool | false if doesn't match preg_match
 * 
 */
function sanitize_sql_orderby($orderby)
{
  
 if (preg_match('/^\s*(([a-z0-9_]+|`[a-z0-9_]+`)(\s+(ASC|DESC))?\s*(,\s*(?=[a-z0-9_`])|$))+$/i', $orderby ) || preg_match( '/^\s*RAND\(\s*\)\s*$/i', $orderby )) {
 
      return $orderby;
      
 }

 return false;

}