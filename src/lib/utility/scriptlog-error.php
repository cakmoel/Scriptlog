<?php
/**
 * Scriptlog Error Function
 * Generates user-level error/warning/notice message
 * 
 * @category function
 * @see https://www.php.net/manual/en/function.trigger-error.php#118794
 * @param string $message
 * @param string $level
 * 
 */
function scriptlog_error($error_message, $error_type = E_USER_NOTICE, $context = 1)
{

  if (version_compare(phpversion(), "5.6", "==")) {

    $stack = debug_backtrace();

    $frame = [];

    for ($i = 0; $i < $context; $i++) {

      if (false === ($frame = next($stack))) { 
          
        if (isset($frame['function']) || isset($frame['file']) || isset($frame['line'])) {
          
        $error_message .= "<p>";
        $error_message .= " in: " . $frame['function'] . '() function called from: ' . $frame['file'] . ' on line ' . $frame['line'] .' '."\n ";
        $error_message .= '</p>';
  
        break;

        }

      }

    }

    return trigger_error($error_message, $error_type);
  
  } else {

     throw new EventException($error_message);
     
  }
  
}

/**
 * Function scriptlog_shutdown_fatal
 * 
 * @category function
 * 
 */
function scriptlog_shutdown_fatal()
{
 
  $site_info = app_info();
  $app_url = $site_info['app_url'];
  $site_name = $site_info['site_name'];
  $sender = $site_info['email_address'];
  $sanitize_sender = sanitize_email($sender);
  $date_error = date("Y-m-d H:i:s (T)");

    $errorType = [
      E_ERROR              => 'Error',
      E_WARNING            => 'Warning',
      E_PARSE              => 'Parsing Error',
      E_NOTICE             => 'Notice',
      E_CORE_ERROR         => 'Core Error',
      E_CORE_WARNING       => 'Core Warning',
      E_COMPILE_ERROR      => 'Compile Error',
      E_COMPILE_WARNING    => 'Compile Warning',
      E_USER_ERROR         => 'User Error',
      E_USER_WARNING       => 'User Warning',
      E_USER_NOTICE        => 'User Notice',
      E_STRICT             => 'Runtime Notice',
      E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
    ];
  
    $scriptlogError = [E_ERROR, E_USER_ERROR, E_COMPILE_ERROR, E_CORE_ERROR];
  
    $lastError =  error_get_last();
      
    if ($lastError !== NULL) {
    
      if (in_array($lastError['type'], $scriptlogError, true)) {
        
        $errno = $lastError['type'];
        $errfile = $lastError['file'];
        $errline = $lastError['line'];
        $errstr = $lastError['message'];

        $headers = 'From '. $sanitize_sender . "\r\n" .
        'Reply-To: '. $sanitize_sender . "\r\n" .
        'Return-Path: '. $sanitize_sender . "\r\n".
        'MIME-Version: 1.0'. "\r\n".
        'Content-Type: text/html; charset=utf-8'."\r\n".
        'X-Mailer: PHP/' . phpversion(). "\r\n" .
        'X-Priority: 1'. "\r\n".
        'X-Sender:'.$sanitize_sender."\r\n";

        scriptlog_error_mail(scriptlog_format_error($date_error, $errno, $errstr, $errfile, $errline), 1, "scriptlog@yandex.com", $headers);

      }
      
    }
    

}

/**
 * Function scriptlog_format_error
 * 
 * @category function
 * @return string
 * 
 */
function scriptlog_format_error($datetime, $errno, $errstr, $errfile, $errline)
{
  $trace = print_r(debug_backtrace(false), true);

  $content = "<html><body><table>
        <thead><th>Item</th><th>Description</th></thead>
        <tbody>
            <tr>
               <th>Date-Time</th>
               <td><pre>$datetime</pre></td>
            </tr>
            <tr>
                <th>Error</th>
                <td><pre>$errstr</pre></td>
            </tr>
            <tr>
                <th>Errno</th>
                <td><pre>$errno</pre></td>
            </tr>
            <tr>
                <th>File</th>
                <td>$errfile</td>
            </tr>
            <tr>
                <th>Line</th>
                <td>$errline</td>
            </tr>
            <tr>
                <th>Trace</th>
                <td><pre>$trace</pre></td>
            </tr>
        </tbody>
    </table></body></html>";

    return $content;

}

/**
 * Function scriptlog_error_mail
 * 
 * @category function
 * @return bool
 * 
 */
function scriptlog_error_mail($errorMsg, $MsgType, $destination, $headers)
{
  if ($MsgType === 1) {

    return error_log($errorMsg, $MsgType, $destination, $headers);
    
  }
  
  return false;

}

