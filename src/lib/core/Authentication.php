<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class Authentication
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
   * Account's info
   *
   * @var array
   * 
   */
  private $account_info = [];

  /**
   * session_cookies
   *
   * @var string
   * 
   */
  private $session_cookies;

  /**
   * user_login
   *
   * @var string
   * 
   */
  private $user_login;

  /**
   * User Agent
   * 
   * @var string
   * 
   */
  private $agent;

  /**
   * accept_charset
   *
   * @var string
   * 
   */
  private $accept_charset;

  /**
   * accept_language
   *
   * @var string
   * 
   */
  private $accept_language;

  /**
   * accept_encoding
   *
   * @var string
   * 
   */
  private $accept_encoding;

  /**
   * ip_address
   *
   * @var string
   * 
   */
  private $ip_address;

  /**
   * key
   *
   * @var string
   * 
   */
  private $key;

  /**
   * userDao
   *
   * @var object
   * 
   */
  private $userDao;

  /**
   * userToken
   *
   * @var object
   * 
   */
  private $userToken;

  /**
   * validator
   *
   * @var object
   * 
   */
  private $validator;

  /**
   * Constant COOKIE_EXPIRE
   * default 1 month
   * 
   * @var null|int|numeric
   * 
   */
  const COOKIE_EXPIRE = 2592000;

  /**
   * Constant COOKIE_PATH
   * Available in whole domain
   * 
   */
  const COOKIE_PATH = APP_ADMIN;

  public function __construct(UserDao $userDao, UserTokenDao $userToken, FormValidator $validator)
  {

    if (Registry::isKeySet('key')) {

      $this->key = Registry::get('key');
    }

    $this->userDao = $userDao;
    $this->userToken = $userToken;
    $this->validator = $validator;

    $this->session_cookies = isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $this->key) : Session::getInstance()->scriptlog_session_login;
    $this->agent =  isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $this->ip_address = get_ip_address();
    $this->accept_charset = isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : '';
    $this->accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
    $this->accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
  }

  /**
   * findUserById
   *
   * @param integer|num $id
   * @return array
   * 
   */
  public function findUserById($id)
  {
    return $this->userDao->getUserById($id);
  }

  /**
   * findUserByEmail
   * 
   * @param string $email
   * @return array
   * 
   */
  public function findUserByEmail($email)
  {
    return $this->userDao->getUserByEmail($email);
  }

  /**
   * findUserByLogin()
   *
   * @param string $user_login
   * @return void
   * 
   */
  public function findUserByLogin($user_login)
  {
    return $this->userDao->getUserByLogin($user_login);
  }

  /**
   * findTokenByLogin
   *
   * @param string $login
   * @param string $expired
   * @return void
   * 
   */
  public function findTokenByLogin($login, $expired)
  {
    return $this->userToken->getTokenByLogin($login, $expired);
  }

  /**
   * markCookieAsExpired
   *
   * @param string $tokenId
   * @return void
   * 
   */
  public function markCookieAsExpired($tokenId)
  {
    return $this->userToken->updateTokenExpired($tokenId);
  }

  /**
   * setPersistentLoginCookie
   *
   * @param array $bind
   * @param string $login
   * @return void
   * 
   */
  public function renewPersistentLogin($bind, $login)
  {
    return $this->userToken->updateUserToken($bind, $login);
  }

  /**
   * Is Email Exists
   * 
   * @param string  $email
   * @return boolean
   * 
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

    if (!$getUser = $this->findUserByLogin($this->session_cookies)) {

      return false;
    }

    if (isset($_COOKIE['scriptlog_auth'])) {

      return (isset($getUser['user_level'])) ? $getUser['user_level'] : "";
    }

    if (isset(Session::getInstance()->scriptlog_session_level)) {

      return Session::getInstance()->scriptlog_session_level;
    }
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

    $login = isset($values['login']) ? $values['login'] : null;
    $password = isset($values['user_pass']) ? $values['user_pass'] : null;
    $remember_me = isset($values['remember']) ? $values['remember'] : null;

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

      $this->validator->sanitize($login, 'email');
      $this->validator->validate($login, 'email');
      $this->account_info = $this->findUserByEmail($login);
    } else {

      $this->validator->sanitize($login, 'string');
      $this->account_info = $this->findUserByLogin($login);
    }

    $this->validator->sanitize($password, 'string');
    $this->user_login = $this->account_info['user_login'];

    clear_duplicate_cookies();

    Session::getInstance()->scriptlog_session_id = intval($this->account_info['ID']);
    Session::getInstance()->scriptlog_session_email = $this->account_info['user_email'];
    Session::getInstance()->scriptlog_session_level = $this->account_info['user_level'];
    Session::getInstance()->scriptlog_session_login = $this->account_info['user_login'];
    Session::getInstance()->scriptlog_session_fullname = $this->account_info['user_fullname'];
    Session::getInstance()->scriptlog_session_agent = sha1($this->accept_charset . $this->accept_encoding . $this->accept_language . $this->agent);
    Session::getInstance()->scriptlog_session_ip = $this->ip_address;
    Session::getInstance()->scriptlog_fingerprint = hash_hmac('sha256', $this->agent, hash('sha256', $this->ip_address, true));
    Session::getInstance()->scriptlog_last_active = time();

    $bind_session = ['user_session' => regenerate_session()];

    $this->userDao->updateUserSession($bind_session, (int)$this->account_info['ID']);

    // Set Auth Cookies if 'Remember Me' checked
    if ($remember_me) {

      $encrypt_auth = ScriptlogCryptonize::scriptlogCipher($this->user_login, $this->key);
      set_cookies_scl('scriptlog_auth', $encrypt_auth, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

      $random_password = Tokenizer::createToken(128);
      set_cookies_scl('scriptlog_validator', $random_password, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

      $random_selector = Tokenizer::createToken(128);
      set_cookies_scl('scriptlog_selector', $random_selector, time() + self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);

      $hashed_password = Tokenizer::setRandomPasswordProtected($random_password);

      $secret = ScriptlogCryptonize::generateSecretKey();
      $hashed_selector = Tokenizer::setRandomSelectorProtected($random_selector, $secret);

      $expiry_date = date("Y-m-d H:i:s", time() + self::COOKIE_EXPIRE);

      $token_info = $this->findTokenByLogin($login, 0);

      if (!empty($token_info['ID'])) {

        $this->userToken->updateTokenExpired($token_info['ID']);
      }

      $bind_token = ['user_login' => $this->user_login, 'pwd_hash' => $hashed_password, 'selector_hash' => $hashed_selector, 'expired_date' => $expiry_date];
      $this->userToken->createUserToken($bind_token);

    } else {

      $this->clearAuthCookies($this->user_login);

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

    Session::getInstance()->startSession();

    $this->removeCookies();

    Session::getInstance()->destroy();

    direct_page('login.php', 302);
  }

  /**
   * validateUserAccount
   *
   * @method public validateUserAccount()
   * @param string $login
   * @param string $password
   * @return boolean
   * 
   */
  public function validateUserAccount($login, $password)
  {

    $verified = false;

    $result = $this->userDao->checkUserPassword($login, $password);

    (isset($verified) && true === $result) ? $verified = true : $verified = false;

    return $verified;
  }

  /**
   * ResetUserPassword
   * 
   * updating reset key and send notification to user
   * 
   * @method public resetUserPassword()
   * @param string $email
   * 
   */
  public function resetUserPassword($user_email)
  {

    $reset_key = ircmaxell_random_generator(32);

    if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {

      $bind = ['user_reset_key' => $reset_key, 'user_reset_complete' => 'No'];

      if ($this->userDao->updateResetKey($bind, $user_email)) {

        // send notification to user email account
        reset_password($user_email, $reset_key);
      }
    }
  }

  /**
   * UpdateNewPassword
   * 
   * Recovering user password
   * 
   * @param string $user_pass
   * @param integer $user_id
   * 
   */
  public function updateNewPassword($user_pass, $user_id, $user_email)
  {

    $this->validator->sanitize($user_id, 'int');
    $this->validator->validate($user_id, 'number');
    $this->validator->validate($user_pass, 'password');

    $bind = ['user_pass' => $user_pass, 'user_reset_complete' => 'Yes'];

    if (($this->userDao->recoverNewPassword($bind, $user_id)) && (is_ssl() === true)) {

      // send email notification to user
      recover_password($user_pass, $user_email);
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

    if ((isset($_COOKIE['scriptlog_auth'])) && (isset($_COOKIE['scriptlog_validator'])) && (isset($_COOKIE['scriptlog_selector']))) {

      unset($_COOKIE['scriptlog_auth']);
      unset($_COOKIE['scriptlog_validator']);
      unset($_COOKIE['scriptlog_selector']);

      set_cookies_scl('scriptlog_auth', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
      set_cookies_scl('scriptlog_validator', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
      set_cookies_scl('scriptlog_selector', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
    }
  }

  /**
   * clearAuthCookies
   * 
   * @param string $user_login
   *
   */
  public function clearAuthCookies($user_login)
  {

    $this->userToken->deleteUserToken($user_login);

    if ((isset($_COOKIE['scriptlog_auth'])) && (isset($_COOKIE['scriptlog_validator'])) && (isset($_COOKIE['scriptlog_selector']))) {

      unset($_COOKIE['scriptlog_auth']);
      unset($_COOKIE['scriptlog_validator']);
      unset($_COOKIE['scriptlog_selector']);

      set_cookies_scl('scriptlog_auth', " ", time() - self::COOKIE_EXPIRE, self::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
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
   * userAccessControl
   * 
   * @param string $control
   * 
   */
  public function userAccessControl($control = null)
  {
    switch ($control) {

      case ActionConst::USERS:

        if ($this->accessLevel() !== 'administrator') {

          return false;
        }

        break;

      case ActionConst::PLUGINS:
      case ActionConst::THEMES:
      case ActionConst::CONFIGURATION:
      case ActionConst::PAGES:
      case ActionConst::NAVIGATION:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'manager')) {

          return false;
        }

        break;

      case ActionConst::MEDIALIB:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'manager')
          && ($this->accessLevel() !== 'editor') && ($this->accessLevel() !== 'author')
        ) {

          return false;
        }

        break;

      case ActionConst::TOPICS:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'manager') && ($this->accessLevel() !== 'editor')) {

          return false;
        }

        break;

      case ActionConst::COMMENTS:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'author') && ($this->accessLevel() !== 'contributor')) {

          return false;
        }

        break;

      case ActionConst::REPLY:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'editor') && ($this->accessLevel() !== 'author')) {

          return false;
        }

        break;

      case ActionConst::POSTS:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'manager')
          && ($this->accessLevel() !== 'editor') && ($this->accessLevel() !== 'author')
          && ($this->accessLevel() !== 'contributor')
        ) {

          return false;
        }

        break;

      case ActionConst::DASHBOARD:
      default:

        if (($this->accessLevel() !== 'administrator') && ($this->accessLevel() !== 'manager')
          && ($this->accessLevel() !== 'editor') && ($this->accessLevel() !== 'author')
          && ($this->accessLevel() !== 'contributor') && ($this->accessLevel() !== 'subscriber')
        ) {

          return false;
        }

        break;
    }

    return true;
  }

  /**
   * getUserAuthSession()
   *
   * @see https://www.php.net/manual/en/function.session-unset.php#107089
   * @return void
   * 
   */
  private function getUserAuthSession()
  {

    if (Session::getInstance()->scriptlog_session_ip !== $this->ip_address || Session::getInstance()->scriptlog_session_agent !== sha1($this->accept_charset . $this->accept_encoding . $this->accept_language . $this->agent)) {

      session_unset();
      session_destroy();
      session_start();
      session_regenerate_id(true);

      Session::getInstance()->scriptlog_session_agent = $this->agent;
      Session::getInstance()->scriptlog_session_ip = $this->ip_address;
    }
  }
}
