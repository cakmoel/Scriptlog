<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class SessionMaker extends SessionHandler
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */

class SessionMaker extends SessionHandler
{

/**
 * key
 *
 * @var string
 * 
 */
protected $key;

/**
 * name
 *
 * @var string
 * 
 */
protected $name; 

/**
 * cookie
 *
 * @var array
 * 
 */
protected $cookie = [];

/**
 * Instantiate session cookies
 *
 * @param string $key
 * @param string $name
 * @param array $cookie
 * 
 */
public function __construct($key, $name = '_scriptlog', $cookie = [])
{

 $this->key = substr(hash('sha256', $key), 0, 32);
 $this->name = $name;
 $this->cookie = $cookie;
 
 if (ini_get('session.use_cookies')) {

   $current_cookie_params = session_get_cookie_params();

 }

 $httponly = true; 
 
 if (PHP_VERSION_ID < 70300) {

   $this->cookie += [

      'lifetime' => $current_cookie_params['lifetime'],
      'path'     => '/; samesite=lax',
      'domain'   => $current_cookie_params['domain'],
      'secure'   => is_cookies_secured(),
      'httponly' => $httponly
            
   ];
   
 } else {

   $this->cookie += [
       
      'lifetime' => $current_cookie_params['lifetime'],
      'path'     => ini_get('session.cookies_path'),
      'domain'   => $current_cookie_params['domain'],
      'secure'   => is_cookies_secured(),
      'httponly' => $httponly,
      'samesite' => 'lax'
   
   ];

 }

 $this->setup();

}

/**
 * setup
 * set the session cookie parameters
 * 
 * @method private setup()
 * @return void
 * 
 */
private function setup()
{

 session_name($this->name);
 
 if (PHP_VERSION_ID < 70300) {

   session_set_cookie_params($this->cookie['lifetime'],  $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);

 } else {

   session_set_cookie_params([
      
      'lifetime' => $this->cookie['lifetime'],
      'path' => $this->cookie['path'],
      'domain' => $this->cookie['domain'],
      'secure' => $this->cookie['secure'],
      'httponly' => $this->cookie['httponly'],
      'samesite' => $this->cookie['samesite']

  ]);

 }

}

/**
 * start
 *
 * @return bool 
 * 
 */
public function start()
{

if ((!isset($_COOKIE[session_name()])) || ($this->isSessionStarted() === false)) {

   if (version_compare(PHP_VERSION, '5.6.0', '<')) {
      
      if(session_id() == '') {
         
         session_start();
         
      }
   
   } else  {
     
       if (session_status() == PHP_SESSION_NONE) {
          
         session_start();
      
      }
  
   }

   return (mt_rand(0, 4) === 0) ? $this->refresh() : true; 

}

return false;

}

/**
 * forget
 * destroy session
 * 
 * @return void
 * 
 */
public function forget()
{

 if ($this->isSessionStarted() === false) {

     return false;

 }

 unset($_SESSION);

 set_cookies_scl($this->name, '', time() - Authentication::COOKIE_EXPIRE, $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);

 session_unset();

 return session_destroy();

}

/**
 * refresh
 *
 * @return void
 * 
 */
public function refresh()
{
  return session_regenerate_id(true);
}


/**
 * read
 *
 * @see https://www.php.net/manual/en/function.session-start.php#120589
 * @param string $id
 * @return string
 * 
 */
public function read($id)
{
  
 $data = parent::read($id);

 return empty($data) ? '' : $this->decrypt($data, $this->key);
 
}

/**
 * write
 *
 * @param string $id
 * @param string $data
 * @return boolean
 * 
 */
public function write($id, $data)
{
 
 return parent::write($id, $this->encrypt($data, $this->key));

}

/**
 * isExpired
 *
 * @param integer $ttl
 * @return boolean
 */
public function isExpired($ttl = 60)
{

 $last_activity = isset($_SESSION['_last_activity']) ? $_SESSION['_last_activity'] : false;

 if ($last_activity !== false && time() - $last_activity > $ttl * 3600) {

    return true;

 }

 $_SESSION['_last_activity'] = time();

 return false;

}

/**
 * isGenuine
 *
 * @return boolean
 */
public function isGenuine()
{
 
 $agent = getenv('HTTP_USER_AGENT', true) ?: getenv('HTTP_USER_AGENT');

 $ip_client = getenv('REMOTE_ADDR', true) ? get_ip_address() : getenv('REMOTE_ADDR');

 $hash = md5( $agent . (ip2long($ip_client) & ip2long('255.255.0.0')));

 if (isset($_SESSION['_genuine'])) {

   return $_SESSION['_genuine'] === $hash;

 }

 $_SESSION['_genuine'] = $hash;

 return true;

}

/**
 * isValid
 *
 * @param integer $ttl
 * @return boolean
 * 
 */
public function isValid($ttl = 60)
{
 return ! $this->isExpired($ttl) && $this->isGenuine();
}

/**
 * encrypy
 *
 * @param string $data
 * @param string $key
 * @return void
 * 
 */
protected function encrypt($data, $key)
{

return ScriptlogCryptonize::encryptAES($data, $key);

}

/**
 * decrypt
 *
 * @param string $data
 * @param string $key
 * @return void
 * 
 */
protected function decrypt($data, $key)
{

return ScriptlogCryptonize::decryptAES($data, $key);

}

/**
 * isSessionStarted
 *
 * @see https://www.php.net/manual/en/function.session-status.php#113468
 * @return boolean
 * 
 */
private function isSessionStarted()
{

   if(!headers_sent() && php_sapi_name() !== 'cli') {

      if(version_compare(phpversion(), '5.6.0', '>=')) {
  
         return (session_status() === PHP_SESSION_ACTIVE) ? true : false;
  
      } else {
  
         return (session_id() === '') ? false : true;
  
      }

   }

   return false;

}

}