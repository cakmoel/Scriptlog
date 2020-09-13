<?php
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

 $this->key = substr(hash('SHA256', $key), 0, 32);
 $this->name = $name;
 $this->cookie = $cookie;
 
 if (ini_get('session.use_cookies')) {

    $current_cookie_params = session_get_cookie_params();

 }

 if (PHP_VERSION_ID >= 70300) {

    $this->cookie += [
       
       'lifetime' => $current_cookie_params['lifetime'],
       'path' => ini_get('session.cookies_path'),
       'domain' => $current_cookie_params['domain'],
       'secure' => is_cookies_secured(),
       'httponly' => 1,
       'samesite' => 'Strict'

    ];

 } else {

     $this->cookie += [

      'lifetime' => $current_cookie_params['lifetime'],
      'path' => '/; samesite=Strict',
      'domain' => $current_cookie_params['domain'],
      'secure' => is_cookies_secured(),
      'httponly' => 1
         
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
 
 if (PHP_VERSION_ID >= 70300) {

    session_set_cookie_params([

        $this->cookie['lifetime'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly'], $this->cookie['samesite']

    ]);

 } else {

    session_set_cookie_params($this->cookie['lifetime'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);

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

if ((isset($_COOKIE[session_name()])) || (self::isSessionStarted() === false)) {

   if(session_start()) {

      return (mt_rand(0, 4) === 0) ? $this->refresh() : true;

   }

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

 if (self::isSessionStarted() === false) {

     return false;

 }

 $_SESSION = [];

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

 $ip = getenv('REMOTE_ADDR', true) ? zend_ip_address() : getenv('REMOTE_ADDR');

 $hash = md5( $agent . (ip2long($ip) & ip2long('255.255.0.0')));

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

$iv = null;

if (function_exists('random_bytes')) {

   $iv = random_bytes(16);

} elseif (function_exists('openssl_random_pseudo_bytes')) {

   $iv = openssl_random_pseudo_bytes(16);

} else {

   $iv = ircmaxell_random_generator(16);

}

// encryption
$ciphertext = openssl_encrypt($data, 'AES-256-CBC', mb_substr($key, 0, 32, '8bit'), OPENSSL_RAW_DATA, $iv);

// authentication
$hmac = hash_hmac('SHA256', $iv.$ciphertext, mb_substr($key, 32, null, '8bit'), true);

return $hmac . $iv . $ciphertext;

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

 $hmac       = mb_substr($data, 0, 32, '8bit');
 $iv         = mb_substr($data, 32, 16, '8bit');
 $ciphertext = mb_substr($data, 48, null, '8bit');

 // authentication
 $hmac_new = hash_hmac('SHA256', $iv.$ciphertext, mb_substr($key, 32, null, '8bit'), true);

 if (! hash_equals($hmac, $hmac_new)) {

     throw new SessionMakerException('Authentication of cryptography failed');

 }

 // Decrypt
 return openssl_decrypt($ciphertext, 'AES-256-CBC', mb_substr($key, 0, 32, '8bit'), OPENSSL_RAW_DATA, $iv);

}

/**
 * isSessionStarted
 *
 * @return boolean
 * 
 */
private static function isSessionStarted()
{

   if(php_sapi_name() !== 'cli') {

      if(version_compare(phpversion(), '5.6.0', '>=')) {
  
         return session_status() === PHP_SESSION_ACTIVE ? true : false;
  
      } else {
  
         return session_id() === '' ? false : true;
  
      }
  
   }
  
   return false;

}

}