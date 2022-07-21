<?php
/**
 * check_request_generated
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function check_request_generated()
{

$method = ['GET', 'POST'];

if(true === block_request_type(current_request_method(), $method)) {

   http_response_code(405);
   scriptlog_error("405 - Method Not Allowed");

} else {

    unset($method);

}

return false;

}