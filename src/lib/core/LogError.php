<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
      
      if (APP_DEVELOPMENT == true) {

        self::newMessage($e);
        
      } else {

        self::newMessage($e);
        self::customErrorMessage('admin');

      }
      
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
  public static function newMessage($exception)
  {

   return MessageLog::messageException($exception, self::logPath().'exceptions.log');
   
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