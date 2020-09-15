<?php
/**
 * Authentication Class
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Authentication
{

  /**
   * user's ID
   * 
   * @var integer
   * 
   */
  private $user_id;

  /**
   * user session
   * 
   * @var mixed
   * 
   */
  private $user_session;
  
  /**
   * User Agent
   * 
   * @var string
   * 
   */
  private $agent;

  /**
   * User's Email
   * 
   * @var string
   * 
   */
  private $user_email;

  /**
   * Username for login
   * 
   * @var string
   * 
   */
  private $user_login;

  /**
   * user nicename
   * 
   * @var string
   * 
   */
  private $user_fullname;

  /**
   * user level
   * 
   * @var string
   * 
   */
  private $user_level;

  /**
   * session data
   *
   * @var string
   * 
   */
  private $session_data = [];

  /**
   * Constant COOKIE_EXPIRE
   * default 1 month
   * 
   * @var const|numeric
   * 
   */
  const COOKIE_EXPIRE =  2592000;  

  /**
   * Constant COOKIE_PATH
   * Available in whole domain
   * 
   */
  const COOKIE_PATH = APP_ADMIN;  
 
  public function __construct(UserDao $userDao, UserTokenDao $userToken, FormValidator $validator)
  {
    $this->userDao = $userDao;
    $this->userToken = $userToken;
    $this->validator = $validator;
  
  }
  
  /**
   * Find User by Email
   * @param string $email
   * @return 
   * 
   */
  public function findUserByEmail($email)
  {
    return $this->userDao->getUserByEmail($email);
  }

/**
 * Find User by Login
 *
 * @param string $user_login
 * @return void
 * 
 */
  public function findUserByLogin($user_login)
  {
    return $this->userDao->getUserByLogin($user_login);
  }

  public function findTokenByLogin($login, $expired)
  {
    return $this->userToken->getTokenByLogin($login, $expired);
  }
  
  public function markCookieAsExpired($tokenId)
  {
    return $this->userToken->updateTokenExpired($tokenId);
  }

  /**
   * Is Email Exists
   * 
   * @param string  $email
   * @return boolean
   */
  public function checkEmailExists($email)
  {

    if ($this->userDao->checkUserEmail($email) > 0) {

       return true;

    }

    return false;
    
  }

/**
 * Checking access level
 * 
 * @return boolean 
 * 
 */
 public function accessLevel()
 {
   
   if (isset($this->getSessionInstance()->scriptlog_session_level)) {

      return $this->getSessionInstance()->scriptlog_session_level;
       
   }
      
  return false;

 }
 
/**
 * Login
 * 
 * @method public login()
 * @param array $values
 * @uses regenerate_session()
 * @uses get_session_data()
 * @uses clear_duplicate_cookies()
 * @see regenerate-session.php on lib/utility
 * 
 */
 public function login(array $values)
 {
    
     $login = (isset($values['login'])) ? $values['login'] : null;
     $password = (isset($values['user_pass'])) ? $values['user_pass'] : null;
     $remember_me = (isset($values['remember'])) ? $values['remember'] : null;

     if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        
       $this->validator->sanitize($login, 'email');
       $this->validator->validate($login, 'email');
       $account_info = $this->findUserByEmail($login);

     } else {

       $this->validator->sanitize($login, 'string');
       $this->validator->validate($login, 'string');
       $account_info = $this->findUserByLogin($login);

     }

      $account_id = (int)$account_info['ID'];
      $account_login = $account_info['user_login'];
      $account_email = $account_info['user_email'];
      $account_level = $account_info['user_level'];
      $account_name = $account_info['user_fullname'];

      $this->validator->validate($password, 'password'); 

      $this->session_data = $this->getSessionInstance();
      $this->session_data->scriptlog_session_id = $this->user_id = intval($account_info['ID']);
      $this->session_data->scriptlog_session_email = $this->user_email = $account_info['user_email'];
      $this->session_data->scriptlog_session_level = $this->user_level = $account_info['user_level'];
      $this->session_data->scriptlog_session_login = $this->user_login = $account_info['user_login'];
      $this->session_data->scriptlog_session_fullname = $this->user_fullname = $account_info['user_fullname'];

      $user_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
      $accept_charset = (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : '';
      $accept_encoding = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
      $accept_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

      $this->session_data->scriptlog_session_agent = $this->agent = sha1($accept_charset.$accept_encoding.$accept_language.$user_agent);
      
      $ip_address = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : zend_ip_address();
      $this->session_data->scriptlog_session_ip = $ip_address;

      $fingerprint = hash_hmac('sha256', $user_agent, hash('sha256', $ip_address, true));
      
      clear_duplicate_cookies();

      $bind_session = ['user_session' => regenerate_session()];

      $this->session_data->scriptlog_fingerprint = $fingerprint;
      $this->session_data->scriptlog_last_active = time();
      
      $this->userDao->updateUserSession($bind_session, (int)$account_info['ID']);

      // Set Auth Cookies if 'Remember Me' checked
      if ($remember_me == true) {

          $tokenizer = new Tokenizer();
          
          set_cookies_scl('scriptlog_auth', $account_login, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
          
          $random_password = $tokenizer -> createToken(64);
          set_cookies_scl('scriptlog_validator', $random_password, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
        
          $random_selector = $tokenizer -> createToken(64);
          set_cookies_scl('scriptlog_selector', $random_selector, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
        
          $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
          $hashed_selector = password_hash($random_selector, PASSWORD_DEFAULT);
          
          $expiry_date = date("Y-m-d H:i:s", time() + self::COOKIE_EXPIRE);
                  
          $token_info = $this->findTokenByLogin($login, 0);
        
          if (!empty($token_info['ID'])) {
        
             $this->userToken->updateTokenExpired($token_info['ID']);
        
          }
        
          $bind_token = ['user_login' => $account_login, 'pwd_hash' => $hashed_password, 'selector_hash' => $hashed_selector, 'expired_date' => $expiry_date];
        
          $this->userToken->createUserToken($bind_token);

      } else {

          $this->clearAuthCookies($account_login);

      }

 }

/**
 * logout
 *
 * @see https://stackoverflow.com/questions/3512507/proper-way-to-logout-from-a-session-in-php
 * @see https://www.php.net/session_destroy
 * @return void
 * 
 */
public function logout()
{

  $this->getUserAuthSession();
  
  $this->getSessionInstance()->startSession();

  $_SESSION = array();

  $this->removeCookies();

  $this->getSessionInstance()->destroy();

  direct_page('login.php', 302);
  
}
  
/**
 * Validate User Account
 *
 * @method public validateUserAccount()
 * @param string $login
 * @param string $password
 * @return boolean
 * 
 */
public function validateUserAccount($login, $password)
{
    
  $result = $this->userDao->checkUserPassword($login, $password);

  if ($result == false) {

      return false;

  }
  
  return true;
    
}

/**
 * Reset user password
 * updating reset key and send notification to user
 * 
 * @param string $email
 * 
 */
public function resetUserPassword($user_email)
{
  
  $reset_key = ircmaxell_random_generator(32);
  
  if(filter_var($user_email, FILTER_VALIDATE_EMAIL)) {

    $bind = ['user_reset_key' => $reset_key, 'user_reset_complete' => 'No'];
    
    if ($this->userDao->updateResetKey($bind, $user_email)) {
      
      # send notification to user email account
      reset_password($user_email, $reset_key);
    
    }
      
  }

}

/**
 * Update new password
 * Recovering user password
 * 
 * @param string $user_pass
 * @param integer $user_id
 * 
 */
public function updateNewPassword($user_pass, $user_id)
{

  $this->validator->sanitize($user_id, 'int');
  $this->validator->validate($user_id, 'number');
  $this->validator->validate($user_pass, 'password');

  $bind = ['user_pass' => $user_pass, 'user_reset_complete' => 'Yes'];

  if ($this->userDao->recoverNewPassword($bind, $user_id)) {
      recover_password($user_pass);
  }

}

/**
 * Remove cookies
 * removing cookies when logging out
 * from administrator page
 * 
 */
public function removeCookies()
{

  if ((isset($_COOKIE['scriptlog_auth'])) && (isset($_COOKIE['scriptlog_validator'])) && (isset($_COOKIE['scriptlog_selector'])) ) {

     unset($_COOKIE['scriptlog_auth']);
     unset($_COOKIE['scriptlog_validator']);
     unset($_COOKIE['scriptlog_selector']);

     set_cookies_scl('scriptlog_auth', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH,  domain_name(), is_cookies_secured(), true);
     set_cookies_scl('scriptlog_validator', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);  
     set_cookies_scl('scriptlog_selector', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
    
  }

}

/**
 * Clear authentication cookies
 *
 */
public function clearAuthCookies($user_login)
{

  $this->userToken->deleteUserToken($user_login);

  if ((isset($_COOKIE['scriptlog_auth'])) && (isset($_COOKIE['scriptlog_validator'])) && (isset($_COOKIE['scriptlog_selector']))) {
     
    unset($_COOKIE['scriptlog_auth']);
    unset($_COOKIE['scriptlog_validator']);
    unset($_COOKIE['scriptlog_selector']);

    set_cookies_scl('scriptlog_auth', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH,  domain_name(), is_cookies_secured(), true);
    set_cookies_scl('scriptlog_validator', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);  
    set_cookies_scl('scriptlog_selector', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
   
  }

}

/**
 * Activate user account
 * user activation
 * 
 * @param string $keys
 * 
 */
public function activateUserAccount($key)
{
  if ($this->userDao->activateUser($key) === false) {
    
    direct_page();

  } else {

    $actived = APP_PROTOCOL . '://' . APP_HOSTNAME . dirname($_SERVER['PHP_SELF']) . '/login.php?status=actived';
    header("Location: $actived");
    exit();
    
  }

}

/**
 * User access control
 * 
 * @param string $control
 * 
 */
public function userAccessControl($control = null)
{

    switch ($control) {

        case ActionConst::USERS:
            
            if($this->accessLevel() != 'administrator') {

                return false;
            }

            break;

        case ActionConst::PLUGINS:
           
            if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager')) {

                return false;

            }

            break;

        case ActionConst::THEMES:

           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager')) {

               return false;

           }

            break;

        case ActionConst::CONFIGURATION:
      
           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager')) {

             return false;

           }

          break;

        case ActionConst::MEDIALIB:

           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager') && ($this->accessLevel() != 'editor') && ($this->accessLevel() != 'author')) {

              return false;

           }

          break;

        case ActionConst::TOPICS:

           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager') && ($this->accessLevel() != 'editor')) {

              return false;

           }

          break;
          
        case ActionConst::PAGES:

           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager')) {

              return false;

           }

          break;
  
        case ActionConst::COMMENTS:
          
           if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager') && ($this->accessLevel() != 'author')) {

              return false;

           }

          break;

        case ActionConst::NAVIGATION:

            if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager')) {

               return false;

            }

          break;

        default:
          
            if(($this->accessLevel() != 'administrator') && ($this->accessLevel() != 'manager') && ($this->accessLevel() != 'editor') 
               && ($this->accessLevel() != 'author') && ($this->accessLevel() != 'contributor')) {

              return false;
            
            }

          break;

    }

    return true;

}

/**
 * getUserAuthSession
 *
 * @return void
 * 
 */
private function getUserAuthSession()
{
  
 $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : get_ip_address();
 $user_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
 $accept_charset = (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : '';
 $accept_encoding = (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
 $accept_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
 
 if(Session::getInstance()->scriptlog_session_ip !== $ip_address 
    || Session::getInstance()->scriptlog_session_agent !== sha1($accept_charset.$accept_encoding.$accept_language.$user_agent)) {

      session_unset();
      session_destroy();
      session_regenerate_id(true);
      
      Session::getInstance()->scriptlog_session_agent = $user_agent;
      Session::getInstance()->scriptlog_session_ip = $ip_address;
        
  }

}

/**
 * getSessionInstance
 *
 * @return void
 * 
 */
private function getSessionInstance()
{

  $this->session_data = Session::getInstance();

  return $this->session_data;

}

}