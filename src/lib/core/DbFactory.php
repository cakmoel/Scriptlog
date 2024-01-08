<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
   * Connect
   * 
   * @param string $connection
   * @param array $options
   * @throws DbException
   * 
   */
  public static function connect($connection, $options = [])
  {

     try {
         
         $database = "Db";  // hard code database factory's name
         
         if (class_exists($database)) {
             
            return new $database($connection, $options);  
            
         } else {

            throw new DbException("Database object does not exists");

         }
         
     } catch (\Throwable $th) {
        
        LogError::setStatusCode(http_response_code(500));
        LogError::exceptionHandler($th);
         
     } catch (DbException $e) {

        LogError::setStatusCode(http_response_code(500));
        LogError::exceptionHandler($e);

     }

  }
  
}