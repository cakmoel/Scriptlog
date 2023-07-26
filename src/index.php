<?php 
/**
 * index.php file
 * 
 * @category index.php file designed as front controller
 * @author   M.Noermoehammad
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
require dirname(__FILE__) . '/lib/main.php';

route_request($dispatcher);