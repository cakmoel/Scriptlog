<?php
/**
 * class LogError
 * 
 * Error Handling and Logging
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class LogError
{

   private static $logFilePath = APP_ROOT . APP_PUBLIC . '/log/';

   private static $httpResponseCode;
   
   public static function logPath()
   {
      return static::$logFilePath;
   }

    /**
     * 
     * @method customErrorMessage
     * @param string $privilege
     * 
     */
    public static function customErrorMessage($privilege = null)
    {
        
        if ($privilege === 'admin') {
            
            switch (self::getStatusCode()) {
                
                case 400 :
                    
                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Bad Request', $privilege);
                    
                    break;
                    
                case 403 :

                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Forbidden', $privilege);
                 
                  break;

                case 404 :
                    
                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Not Found', $privilege);
                    
                    break;

                case 405 :

                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Method Not Allowed', $privilege);
                
                  break;
                
                case 413 :

                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Payload Too Large', $privilege);

                  break;

                case 500 :

                    echo MessageLog::contentMessage(static::$httpResponseCode, 'Internal Server Error', $privilege);

                  break;
                             
            }
            
        } else {
            
            echo MessageLog::contentMessage(static::$httpResponseCode);

        }
        
    }

    /**
     * exception handler
     *
     * @method exceptionHandler
     * @param object $e
     */
    public static function exceptionHandler($e)
    {
        self::newMessage($e);
        self::customErrorMessage();
    }

    /**
     *
     * errorHandler
     *
     * @method errorHandler
     * @param integer $errorNumber
     * @param string $errorString
     * @param string $file
     * @param string $line
     * @return number
     * 
     */
    public static function errorHandler($code, $description, $file = null, $line = null)
    {
      return MessageLog::messageError($code, $description, self::logPath().'error.log', $file, $line); 
    }

    /**
     *
     * @static method newMessage
     * @param Exception|Throwable $exception
     * @param string $_printError
     * @param string $clear
     * @param string $error_file
     */
    public static function newMessage($exception, $_printError = false, $clear = false, $error_file = 'logerror.html')
    {

      if ( ! $exception instanceof Exception) {

        $exception = new CoreException($exception);

      }

      $message = $exception->getMessage();
      $code = $exception->getCode();
      $file = $exception->getFile();
      $line = $exception->getLine();
      $trace = $exception->getTraceAsString();
      
      $date = date('M d, Y G:iA');
        
        $log_message = "<h3>Exception information:</h3>\n
		<p><strong>Date:</strong> {$date}</p>\n
		<p><strong>Message:</strong> {$message}</p>\n
		<p><strong>Code:</strong> {$code}</p>\n
		<p><strong>File:</strong> {$file}</p>\n
		<p><strong>Line:</strong> {$line}</p>\n
		<h3>Stack trace:</h3>\n
		<pre>{$trace}</pre>\n
		<hr />\n";
        
        if (is_readable(self::logPath() . $error_file) === false) {
            file_put_contents(self::logPath() . $error_file, '');
        }
        
        if ($clear) {

            $content = '';

        } else {
            
            $content = file_get_contents(self::logPath() . $error_file);

        }
        
        file_put_contents(self::logPath() . $error_file, $log_message . $content);
        
        if ($_printError == true) {
            
            echo $log_message;
            
            exit();
            
        }
        
    }

  public static function setStatusCode($statusCode)
  {
    self::$httpResponseCode = $statusCode;
  }
    
  private static function getStatusCode()
  {
    return static::$httpResponseCode;
  }

}