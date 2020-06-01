<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$load = null;
$current_request = current_request_method();
$method_allowed = ['GET', 'POST'];

try {

   if ((isset($_GET['load'])) || (array_key_exists('load', $_GET))) {
     
        $load = htmlentities(strip_tags(strtolower($_GET['load'])), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        $load = filter_var($load, FILTER_SANITIZE_URL);
       
        // checking if the string contains parent directory
        if (strstr($_GET['load'], '../') !== false) {
            
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Directory traversal attempt!");
            
        }

        if (strpos($_GET['load'], '..')) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Directory traversal attempt!");

        }

        // checking remote file inclusions
        if (strstr($_GET['load'], 'file://') !== false) {
            
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");
            
        }
    
        if (strstr($_GET['load'], 'http://') !== false) {
            
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");
            
        }

        if (strstr($_GET['load'], 'php://input')) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");

        }

        if (strstr($_GET['load'], 'php://filter')) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");

        }

        if (strstr($_GET['load'], 'data:')) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Remote file inclusion attempt!");

        }

        if ((!is_readable(dirname(dirname(__FILE__)) .DS. APP_ADMIN .DS."{$load}.php")) 
            || (empty($load)) || (!in_array($load, array_keys($allowedQuery)))) {

            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            throw new AppException(" 404 Page requested not found");

        } else {

            if (false === $authenticator -> userAccessControl()) {

                http_response_code(403);
                throw new AppException("403 - Forbidden");
 
            } else {
                
                if (true === block_request_type($current_request, $method_allowed)) {
                  
                   http_response_code(405);
                   throw new AppException("405 - Method Not Allowed");
 
                } else {
 
                    include __DIR__ . DS . basename($load).'.php';
                   
                }
             
            }
             
        }

    } else {

        if (false === $authenticator->userAccessControl()) {

            http_response_code(403);
            throw new AppException("403 Forbidden");

        } else {

            if (true === block_request_type($current_request, $method_allowed)) {

                 http_response_code(405);
                 throw new AppException("405 - Method Not Allowed");

            } else {

                direct_page('index.php?load=dashboard', 302);

            }
            
        }
        
    }
    
} catch (AppException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($e);
    LogError::customErrorMessage('admin');
    
} 

(isset($ubench) && (true == APP_DEVELOPMENT)) ? $ubench->end() : null;