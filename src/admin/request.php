<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$load = null;

try {

    if (isset($_GET['load']) && $_GET['load'] != null) {
     
        $load = htmlentities(strip_tags(strtolower(basename($_GET['load']))), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        $load = filter_var($load, FILTER_SANITIZE_URL);
        
        // checking if the string contains parent directory
        if (strstr($_GET['load'], '../') !== false) {
            
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Directory traversal attempt!");
            
        }
        
        // checking remote file inclusions
        if (strstr($_GET['load'], 'file://') !== false) {
            
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");
            
        }
    
        if ((!is_readable(dirname(dirname(__FILE__)) .DS. APP_ADMIN .DS."{$load}.php")) 
           || (empty($load)) || (!in_array($load, $allowedQuery, true)) ) {
        
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            throw new AppException("404 - Page requested not found");
        
        } else {
        
           include __DIR__ . DS . $load .'.php';
        
        }
    
    } else {

        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
        throw new AppException("404 - Page requested not found");

    }
    
} catch (AppException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($e);
    LogError::customErrorMessage('admin');
    
} 