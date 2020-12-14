<?php 
/**
 * DbFactory Class
 *  
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class DbFactory
{
  /**
   * Error
   * @var string
   */
  private static $error;
  
  /**
   * Connect
   * 
   * @param string $connection
   * @param array $options
   * @throws DbException
   * @return object
   * 
   */
  public static function connect($connection, $options = [])
  {
     try {
         
         # hard code database factory's name
         $database = "Db";
         
         if (class_exists($database)) {
             
            return new $database($connection, $options);  
            
         } else {

            throw new DbException("Database object does not exists");

         }
         
     } catch (DbException $e) {
        
        self::$error = LogError::setStatusCode(http_response_code(500));
        self::$error = LogError::newMessage($e);
        self::$error = LogError::customErrorMessage();
         
     } catch (PDOException $e) {

        self::$error = LogError::setStatusCode(http_response_code(500));
        self::$error = LogError::newMessage($e);
        self::$error = LogError::customErrorMessage();

     }

  }
  
}