<?php
/**
 * Distill post request function
 * filtering optionally external variable
 * 
 * @param array $refine
 * @return mixed
 * 
 */
function distill_post_request($refine)
{

 if (is_array($refine)) {

   return filter_input_array(INPUT_POST, $refine);

 } else {

    scriptlog_error("can not retrieve external variables, please make sure it is an array!");

 }

}