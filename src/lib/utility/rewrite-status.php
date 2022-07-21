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

$rewrite_status = json_decode(app_info()['permalink_setting'], true);
return $rewrite_status['rewrite'];

}