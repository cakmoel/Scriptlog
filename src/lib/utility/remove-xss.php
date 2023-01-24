<?php
/**
 * remove_xss
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string|array $dirty_string
 * @return void
 * 
 */

use voku\helper\AntiXSS;

function remove_xss($dirty_string)
{
 $antiXss = new AntiXSS();
 return $antiXss->xss_clean($dirty_string); 
}

/**
 * simple_remove_xss
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $dirty_string
 * @return void
 * 
 */
function simple_remove_xss($dirty_string)
{
 
if (is_array($dirty_string)) {

    $filter = Clean::cleanArray($dirty_string, true);

} else {

    $filter = Clean::cleanInput($dirty_string, true);

}

return $filter;

}