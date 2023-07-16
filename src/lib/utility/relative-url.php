<?php
/**
 * Relative URL function
 * 
 * @category function
 * @see https://www.php.net/manual/en/reserved.variables.server.php#112693 PHP:$_SERVER Manual
 * @return mixed
 * 
 */
function relative_url()
{
  
  $dir = str_replace('\\', '/', __DIR__);

  return substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
  
}