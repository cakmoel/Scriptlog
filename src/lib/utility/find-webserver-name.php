<?php
/**
 * find_webserver_name()
 *
 * finding webserver name
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function find_webserver_name()
{

$server = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : $_SERVER['SERVER_NAME'];

$slice = explode("/", $server);

$server_name = isset($slice[0]) ? $slice[0] : '';

return $server_name;

}