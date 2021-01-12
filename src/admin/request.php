<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed");

$load = null;
$current_request = current_request_method();
$method_allowed = ['GET', 'POST'];
$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "";

try {

   if (! in_array($protocol, ['HTTP/1.1', 'HTTP/2', 'HTTP/2.0'], true)) {

    $protocol = 'HTTP/1.0';

   }

   if ((isset($_GET['load'])) || (array_key_exists('load', $_GET))) {
     
        $load = is_array($_GET['load']) ? implode('', $_GET['load']) : $_GET['load'];
        $load = filter_var($load, FILTER_VALIDATE_URL, ['flags' => FILTER_FLAG_QUERY_REQUIRED]);
        $load = filter_input(INPUT_GET, 'load', FILTER_SANITIZE_STRING);
        $load = str_replace(chr(0), '', $load);
        $load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
        $load = htmlspecialchars(strtolower($load), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
       
        // checking if the string contains parent directory
        if (strpos($load, '..')) {

            header($protocol." 400 Bad Request", true, 400);
            throw new AppException("Directory traversal attempt!");
            
        }

        // checking remote file inclusions
        if ((strstr($load, '../') !== false) || (strstr($load, 'file://') !== false) || (strstr($load, 'http://') !== false)) {
            
            header($protocol." 400 Bad Request", true, 400);
            throw new AppException("Remote file inclusion attempt!");
            
        }
    
        if ((strstr($load, 'php://input')) || (strstr($load, 'php://filter')) || (strstr($load, 'data:')) || (strstr($load, 'zip://'))) {

            header($protocol." 400 Bad Request", true, 400);
            throw new AppException("Remote file inclusion attempt!");

        }

        if ((!is_readable(dirname(dirname(__FILE__)) .DS. APP_ADMIN .DS."{$load}.php")) 
            || (empty($load)) || (!in_array($load, array_keys(admin_query())))) {

            header($protocol." 404 Not Found", true, 400);
            throw new AppException(" 404 Page requested not found");

        } else {

            if (false === $authenticator->userAccessControl()) {

                http_response_code(403);
                throw new AppException("403 - Forbidden");
 
            } else {
                
                if (true === block_request_type($current_request, $method_allowed)) {
                  
                   http_response_code(405);
                   throw new AppException("405 - Method Not Allowed");
 
                } else {

                    if (!function_exists('realpath')) {

                        include dirname(__FILE__) . DS . basename(absolute_path($load.'.php'));

                    } else {

                        include dirname(__FILE__) . DS . basename(realpath($load.'.php'));
                        
                    }
                    
                }
             
            }
             
        }

    } else {

        if (false === $authenticator->userAccessControl()) {

            http_response_code(403);
            throw new AppException("403 - Forbidden");

        } else {

            if (true === block_request_type($current_request, $method_allowed)) {

                 http_response_code(405);
                 throw new AppException("405 - Method Not Allowed");

            } else {

                direct_page('index.php?load=dashboard', 302);

            }
            
        }
        
    }
    
} catch (Throwable $th) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($th);
    LogError::customErrorMessage('admin');
    
} catch (AppException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($e);
    LogError::customErrorMessage('admin');
    
} 

(isset($ubench) && (true == APP_DEVELOPMENT)) ? $ubench->end() : null;