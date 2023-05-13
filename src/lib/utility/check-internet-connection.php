<?php

/**
 * check_internet_fsockopen
 *
 * @category function
 * @see https://www.codespeedy.com/check-internet-connection-in-php/
 * @see https://stackoverflow.com/questions/4860365/determine-in-php-script-if-connected-to-internet
 * @param string $hostname
 * @return bool
 * 
 */
function checking_connection_with_fsockopen($hostname)
{
    $file = @fsockopen($hostname, 80);//@fsockopen is used to connect to a socket
    
    return ($file);
}

/**
 * checking_connection_with_fopen($ip)
 *
 * @param string $ip
 * 
 */
function checking_connection_with_fopen($hostname)
{
    return (bool) (@fopen($hostname, "r")) ? true : false;
}

/**
 * checking_internet_connection
 *
 * @param string $hostname
 * @return void
 */
function checking_internet_connection()
{

    $hostname = '216.239.38.120';

    if (function_exists('fsockopen')) {

        return checking_connection_with_fsockopen($hostname);

    } else {

        return checking_connection_with_fopen($hostname);

    }
    
}
