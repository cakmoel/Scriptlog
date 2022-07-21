<?php
/**
 * form_filled_validation()
 * 
 * This function checks that the form has been filled out
 * Example:
 * if (!form_filled_validation($_POST)) { 
 *   throw new Exception('You have not filled the form out correctly - please go back and try again');
 * }
 * 
 * @category function
 * @author Luke Welling and Laura Thomson
 * @param  array $vars
 * @return bool
 * 
 */
function form_filled_validation($vars)
{

foreach ( $vars as $key => $value) {

     if ((!isset($key)) || ($value == '')) {
         return false;
     }

}

return true;

}