<?php
/**
 * check_internet
 *
 * @category function
 * @see https://www.codespeedy.com/check-internet-connection-in-php/
 * @see https://stackoverflow.com/questions/20348706/checking-internet-status-using-php
 * @return bool
 * 
 */
function check_internet()
{

 $connect_status = false;

 switch (connection_status()) {

    case CONNECTION_NORMAL:
         
        $connect_status = true;
    
        break;
     
    case CONNECTION_ABORTED :
    case (CONNECTION_ABORTED & CONNECTION_TIMEOUT) :
    default:
        
        $connect_status = false;

        break;

 }

 return $connect_status;
 
}

/**
 * check_internet_fsockopen
 *
 * @category function
 * @see https://www.codespeedy.com/check-internet-connection-in-php/
 * @see https://stackoverflow.com/questions/4860365/determine-in-php-script-if-connected-to-internet
 * @param string $domain
 * @return bool
 * 
 */
function check_internet_fsockopen($domain = 'www.google.com')
{
    if (function_exists('fsockopen')) {
    
        return (bool) @fsockopen($domain, 80, $errorNum,$errorMessage, 5);

    }
    
}

