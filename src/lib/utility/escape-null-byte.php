<?php
/**
 * escape_null_byte
 *
 * @category function
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @param string $input
 * @return array|string
 * 
 */
function escape_null_byte($input)
{

 $input = str_replace(chr(0), '', $input);

 return $input;

}