<?php
/**
 * Final class PostContentProvider
 * 
 * @category Provider Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
final class PostContentProvider
{

/**
 * uri
 * 
 * @var object
 * 
 */
static $uri;

/**
 * parameters
 *
 * @var array
 * 
 */
static private $parameters = [];

/**
 * postSanitized
 *
 * @param string $value
 * @param string $type
 * @return string
 * 
 */
private static function postSanitized($value, $type)
{
  return sanitizer($value, $type);
}

/**
 * callPostProviderService
 *
 * @method private static callPostProviderService()
 * @return object
 * 
 */
private static function callPostProviderService()
{
  return new PostProviderService(new PostProviderModel, new Sanitize);
}

/**
 * callPostProvider
 *
 * @method private|static callPostProvider()
 * @return object
 * 
 */
private static function callPostProvider()
{
  return new PostProviderController(self::callPostProviderService());
}

/**
 * invokeAllPosts
 *
 * Retrieve all posts records published
 * 
 * @method private static invokeAllPosts()
 * @return object
 * 
 */
private static function invokeAllPosts()
{
   return self::callPostProvider()->getItems();
}

/**
 * invokeDetailPost
 *
 * Retrieve single record of post
 * 
 * @method private static invokeDetailPost()
 * @param int|string $parameters
 * @return array|mixed
 * 
 */
private static function invokeDetailPost($parameters)
{
  
  $clean_parameters = prevent_injection($parameters);

  if (check_integer($clean_parameters)) {

    return self::callPostProvider()->getItemById(self::postSanitized($clean_parameters, 'sql'));

  } else {

    return self::callPostProvider()->getItemBySlug(self::postSanitized($clean_parameters, 'xss'));
      
  }
  
}

/**
 * retrievePost
 *
 * @method public static retrievePost()
 * @return mixed
 * 
 */
public static function retrivePost()
{

  try {

    if (Registry::isKeySet('uri')) {

      (is_object(Registry::get('uri'))) ? self::$uri = Registry::get('uri') : null;
    
      self::$parameters['matched'] = self::$uri->matched;
      self::$parameters['param1'] = self::$uri->param1;
      self::$parameters['param2'] = self::$uri->param2;
      self::$parameters['param3'] = self::$uri->param3;
    
      if ( (empty(self::$parameters['param3'])) && (empty(self::$parameters['[param2'])) ) {
    
          return self::invokeAllPosts();
    
      } else {
    
         if (!check_integer(self::$parameters['param2'])) {
    
          throw new ProviderException("Invalid parameter ID");
    
         } else {

          return self::invokeDetailPost(self::$parameters['param2']);

         }
    
      }
    
    }

  } catch (Throwable $th) {
    
    LogError::setStatusCode(http_response_code());
    LogError::newMessage($th);
    LogError::customErrorMessage();

  } catch (ProviderException $e) {

    LogError::setStatusCode(http_response_code());
    LogError::newMessage($e);
    LogError::customErrorMessage();

  }

}

}