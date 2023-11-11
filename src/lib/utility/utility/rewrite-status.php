<?php
/**
 * rewrite_status()
 *
 * retrieve rewrite status wheter enabled (Yes) or disabled(No)
 * 
 * @category Function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function rewrite_status()
{

$rewrite_status = isset(app_info()['permalink_setting']) ? json_decode(app_info()['permalink_setting'], true) : "";
return (is_array($rewrite_status) && isset($rewrite_status['rewrite'])) ? $rewrite_status['rewrite'] : [];

}