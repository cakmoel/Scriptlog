<?php
/**
 * notfound_id()
 * 
 * generate unique identifier for 404 HTTP Response Code (Not found)
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function notfound_id()
{
 return md5(app_key().get_ip_address());
}