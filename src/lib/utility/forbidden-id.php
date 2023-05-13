<?php
/**
 * Forbidden Id function
 * generate unique identifier for 403 HTTP Response Code (Forbidden)
 * 
 * @category function
 * @return string
 * 
 */
function forbidden_id()
{
 return md5(APP_HOSTNAME.get_ip_address());
}