<?php
/**
 * Function check_integer
 * 
 * @category function
 * @param integer $input
 * @see https://www.php.net/manual/en/function.is-int.php#82857
 * @return boolean
 * 
 */
function check_integer($input)
{
  return (ctype_digit(strval($input)));
}
