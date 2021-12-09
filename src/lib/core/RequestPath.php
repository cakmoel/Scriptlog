<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class RequestPath
 * 
 * Example:
 * $request = new RequestPath();
 * echo "Request matched: {$request->matched} <br>";
 * echo "Request param1: {$request->param1} <br>";
 * echo "Request param2: {$request->param2} <br>";
 * 
 * @category Core Class
 * @author Davey Shafik, Matthew Weier O’Phinney, Ligaya Turmelle, Harry Fuecks, and Ben Balbo
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
class RequestPath
{

private $parts = [];

public function __construct()
{

 if (isset($_SERVER['PATH_INFO'])) {

    $path = (substr($_SERVER['PATH_INFO'], -1) == "/") ? substr($_SERVER['PATH_INFO'], 0, -1) : $_SERVER['PATH_INFO'];

 } else {

    $path = (substr($_SERVER['REQUEST_URI'], -1) == "/") ? substr($_SERVER['REQUEST_URI'], 0, -1) : $_SERVER['REQUEST_URI'];

 }

 $bits = explode("/", substr($path, 1));

 $parsed['matched'] = array_shift($bits);
 $parsed[] = $parsed['matched'];

 $parsed['param1'] = array_shift($bits);
 $parsed[] = $parsed['param1'];

 $parsed['param2'] = array_shift($bits);
 $parsed[] = $parsed['param2'];

 $parsed['param3'] = array_shift($bits);
 $parsed[] = $parsed['param3'];

 $parts_size = sizeof($bits);

 if ($parts_size % 2 != 0) {

     $parts_size -= 1;

 }

 for ($i = 0; $i < $parts_size; $i+=2) {

    $parsed[$bits[$i]] = $bits[$i + 1];
    $parsed[] = $bits[$i + 1];

 }

 if (sizeof($bits) % 2 != 0) {

     $parsed[] = array_pop($bits);

 }

 $this->parts = $parsed;

}

public function __get($key)
{
  return $this->parts[$key];
}

public function __set($key, $value)
{
   $this->parts[$key] = $value;
}

public function __isset($key)
{
   return isset($this->parts[$key]);
}

}