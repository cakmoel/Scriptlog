<?php
/**
 * simple salt function
 * generating simple salt
 * 
 * @return string
 * 
 */
function simple_salt($num_chars)
{
  
 if((is_numeric($num_chars)) && ($num_chars > 0) && (!is_null($num_chars))) {

     $salt = '';
     
     $accepted_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890/+';

     srand(((int)((double)microtime()*1000003)));

     for($i=0; $i<=$num_chars; $i++) {

         $random_number = rand(0, (strlen($accepted_chars)-1));

         $salt .= $accepted_chars[$random_number];

     }

     return $salt;

 }
  
}