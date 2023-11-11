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
    //@fsockopen is used to connect to a socket
    if (function_exists('fsockopen')) {

        $connected = @fsockopen($hostname, 80);
        if ($connected) {
            $is_conn = true;
            fclose($connected);
        } else {
            $is_conn = false;
        }

        return $is_conn;
    }
}

/**
 * checking_connection_with_fopen($ip)
 *
 * @param string $ip
 * 
 */
function checking_connection_with_fopen($hostname)
{
    if (function_exists('fopen')) {

        return (bool) (@fopen($hostname, "r")) ? true : false;
    }
}

/**
 * checking_internet_connection
 *
 * @param string $hostname
 * 
 */
function checking_internet_connection()
{
  $hostname = '216.239.38.120';
                
  if (true === check_disabled_functions('fsockopen')) {
     
    switch (connection_status()) {
        case CONNECTION_NORMAL:
            $msg = 'connected';
            break;
        case CONNECTION_ABORTED:
            $msg = 'Not connected';
            break;
        case CONNECTION_TIMEOUT:
            $msg = 'Time-out';
            break;
        case CONNECTION_ABORTED & CONNECTION_TIMEOUT:
            $msg = 'Time-out and aborted';
            break;
        default:
            $msg = 'Undefined state';
            break;
    }

    return ($msg == 'Not connected') ? false : true;

  } else {

    return checking_connection_with_fsockopen($hostname);
  }
}

/**
 * is_online
 *
 * @see https://stackoverflow.com/questions/1696202/check-if-host-computer-is-online-with-php
 * @return boolean
 */
function is_online()
{
    if (function_exists('checkdnsrr')) {

        return checkdnsrr('google.com', 'ANY') && checkdnsrr('yahoo.com', 'ANY') && checkdnsrr('microsoft.com', 'ANY');
    } else {

        return check_online('google.com');
    }
}

