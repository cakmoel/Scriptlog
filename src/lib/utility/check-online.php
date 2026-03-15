<?php
/**
 * check_online
 *
 * getting the website status whether it is up or down
 * 
 * @category function
 * @see https://stackoverflow.com/questions/9817046/get-the-site-status-up-or-down
 * @param string $domain
 * @return bool
 * 
 */
function check_online($domain)
{

$curlInit = curl_init($domain);
    
curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
 
curl_setopt($curlInit, CURLOPT_HEADER, true);

curl_setopt($curlInit, CURLOPT_NOBODY, true);

curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
 
//get answer

$response = curl_exec($curlInit);
 
curl_close($curlInit);

return ($response) ? true : false;

}