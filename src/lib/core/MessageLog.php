<?php
/**
 * MessageLog
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
final class MessageLog
{

private static $display_errors;

public static function contentMessage($responseCode, $contentMessage = null, $privilege = null)
{
 
 $msg = null;

 if ($privilege !== 'admin') {

    $msg = <<<MSG

        <div class="content-wrapper">
        <div class="alert alert-danger" role="alert">
        Please check your error log and send it to - email: 
        scriptlog@yandex.com   
        </div></div>
        
MSG;

 } else {

    $msg = <<<MSG
     <div class="content-wrapper">
     <section class="content-header">
     <h1>$responseCode - $contentMessage</h1>
     <ol class="breadcrumb">
     <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
     <li><a href="#">Error</a></li>
     <li class="active">$responseCode</li>
     </ol>
     </section>
     <section class="content">
     <div class="error-page">
     <h2 class="headline text-yellow">$responseCode</h2>
     <div class="error-content">
     <h3><i class="fa fa-warning text-yellow"></i> Application not response to $contentMessage.</h3>
     <p>Please check your log error and send it to - email: scriptlog@yandex.com
        <a href="index.php?load=dashboard">return to dashboard</a>.
     </p>
     </div>
     </div>
     </section>
     </div>
MSG;

 }

 return $msg;
  
}

public static function messageException($exception, $logPath)
{

    if ( ! $exception instanceof Exception) {

        $exception = new CoreException($exception);

    }

    $class_except = get_class($exception);
    $messages = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $responseCode = http_response_code();

    if (APP_DEVELOPMENT == true) {

    $msg = <<<MSG
        <div class="content-wrapper">
        <section class="content-header">
        <h1>$responseCode</h1>
        <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Error</a></li>
        <li class="active">$responseCode</li>
        </ol>
        </section>
        <section class="content">
        <div class="error-page">
        <h2 class="headline text-yellow">$responseCode</h2>
        <div class="error-content">
        <h3><i class="fa fa-warning text-yellow"></i> Type: $class_except.</h3>
        <p>
           Message: $messages <br> File: $file <br> Line: $line
        </p>
        </div>
        </div>
        </section>
        </div>
MSG;

        print $msg;
  
    } else {

        $data = ["Type:" => $class_except, "Messages:"=> $messages, "File:"=>$file, "Line:"=>$line];
       
        file_put_contents( $logPath, json_encode($data) . PHP_EOL, FILE_APPEND );
        direct_page('index.php', 301);
  
    }


}

public static function messageError($code, $description, $logPath, $file = null, $line = null)
{

static::$display_errors = ini_get("display_errors");
static::$display_errors = strtolower(static::$display_errors);

if (error_reporting() === 0 || ini_set('display_errors', '1') === false || static::$display_errors === "on") {

    return false;

}

list($error, $log) = self::errorCodeMessage($code);

$data = array(
    'level' => $log,
    'code' => $code,
    'error' => $error,
    'description' => $description,
    'file' => $file,
    'line' => $line,
    'path' => $file,
    'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
);

return self::writeMessageToFile($data, $logPath);

}

private static function writeMessageToFile($data, $logPath)
{

 $fh = fopen($logPath, 'a+');

 if (is_array($data)) {

    $data = print_r($data, 1);

 }

 $status = fwrite($fh, $data);
 
 fclose($fh);

 return ($status) ? true : false;

}

private static function errorCodeMessage($code)
{

  $error = $log = null;
    
    switch ($code) {
        case E_PARSE:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            $log = LOG_ERR;
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_COMPILE_WARNING:
        case E_RECOVERABLE_ERROR:
            $error = 'Warning';
            $log = LOG_WARNING;
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            $log = LOG_NOTICE;
            break;
        case E_STRICT:
            $error = 'Strict';
            $log = LOG_NOTICE;
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $error = 'Deprecated';
            $log = LOG_NOTICE;
            break;
        default :
            break;
    }

    return array($error, $log);

  }
 
}