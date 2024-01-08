<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * CSRFGuard
 * 
 * @author Thibaut Despoulain
 * @author M.Noermoehammad
 * @category Core Class
 * @license MIT
 * @version 1.0
 * 
 */
class CSRFGuard
{

private static $checkOrigin = false;

/**
 * check
 * 
 * @method public static check()
 * @return true|false|Exception
 * 
 */
public static function check($key, $origin, $throwException = false, $timespan = null, $multiple = false)
{

 try {

    if (! isset($_SESSION['csrf_'.$key])) {

        if ($throwException) {

            if (version_compare(phpversion(), '7.4.33', '<=')) {

                http_response_code(400);

                throw new CoreException('Missing CSRF Session Token');

            } 

        } else {

            return false;

        }
   
    }

    if (! isset($origin[$key])) {

        if ($throwException) {

            if (version_compare(phpversion(), '7.4.33', '<=')) {

                http_response_code(400);

                throw new CoreException('Missing CSRF form token');

            } 

        } else {

            return false;

        }

    }

    $hash = isset($_SESSION['csrf_'.$key]) ? $_SESSION['csrf_'.$key] : "";

    if (!$multiple) {

        $_SESSION['csrf_'.$key] = null;

    }

    if (self::$checkOrigin && sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) != substr(base64_decode($hash), 10, 40)) {

        if ($throwException) {

            if (version_compare(phpversion(), '7.4.33', '<=')) {

                http_response_code(400);

                throw new CoreException('Form origin does not match token origin.');

            } 
             
        } else {

            return false;

        }

    } elseif ($origin[$key] != $hash) {

        if ($throwException) {

            if (version_compare(phpversion(), '7.4.33', '<=')) {

                http_response_code(400);

                throw new CoreException('Invalid CSRF token');

            } 
            
        } else {

            return false;

        }

    } elseif ($timespan != null && is_int($timespan) && intval(substr(base64_decode($hash), 0, 10)) + $timespan < time() ) {

        if ($throwException) {

            if (version_compare(phpversion(), '7.4.33', '<=')) {

                http_response_code(400);

                throw new CoreException('CSRF token has expired.');

            } 

        } else {

            return false;

        }

    }

    return true;
    
 } catch (\Throwable $th) {
     
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);
    
 } catch (CoreException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);

 }

}

/**
 * generate
 *
 * @method static generate()
 * @param string $key
 * @return string
 */
public static function generate($key)
{

 $extra = (self::enableOriginCheck() === true) ? sha1($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) : '';
 
 $token = base64_encode(time() . $extra . self::randomString(32));
        
 $_SESSION['csrf_' . $key] = $token;

 return $token;

}

/**
 * enableOriginCheck
 *
 * @return bool
 */
private static function enableOriginCheck()
{
 
 self::$checkOrigin = true;

 return self::$checkOrigin;

}

/**
 * randomString
 *
 * @param int|numeric $length
 * @return string
 * 
 */
private static function randomString($length)
{
  return ircmaxell_random_generator($length);
}

}