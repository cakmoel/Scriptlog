<?php
/**
 * Notfound Id function
 * generate unique identifier for 404 HTTP Response Code (Not found)
 * 
 * @category function
 * @return string
 * 
 */
function notfound_id()
{
 return md5(app_key().get_ip_address());
}