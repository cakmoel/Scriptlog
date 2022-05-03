<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * UserEvent Class
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class UserEvent
{
    
 /**   
  * User's ID
  * @var integer
  */
 private $user_id;
 
 /**
  * User Login
  * @var string
  */
 private $user_login;
 
 /**
  * User E-mail
  * @var string
  */
 private $user_email;
 
 /**
  * User password
  * @var string
  */
 private $user_pass;

 /**
  * User level
  * @var string
  */
 private $user_level;

 /**
  * User fullname
  * @var string
  */
 private $user_fullname;
 
 /**
  * User url
  * @var string
  */
 private $user_url;
 
 /**
  * user activation key
  * @var string
  */
 private $user_activation_key;

/**
 * Password validator for cookies
 *
 * @var string
 * 
 */
 private $pwd_hash;

/**
 * Selector validator for cookies
 *
 * @var string
 * 
 */
 private $selector_hash;

/**
 * Cookie expired date
 *
 * @var string
 * 
 */
 private $cookie_expired_date;
 
 /**
  * user session
  * @var string
  */
 private $user_session;

/**
 * user_banned
 *
 * @var int|numeric
 */
 private $user_banned;

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
 * sanitize
 *
 * @var object
 * 
 */
 private $sanitize;

 private $key;

 private $validator_verified = false;

 private $selector_verified = false;

 private $expired_verified = false;

 /**
  * @method __constructor()
  * 
  */
 public function __construct(UserDao $userDao, FormValidator $validator, UserTokenDao $userToken, Sanitize $sanitize)
 {
    $this->userDao = $userDao;

    $this->userToken = $userToken;

    $this->validator = $validator;
    
    $this->sanitize = $sanitize;

    if ( Registry::isKeySet('key') ) {

      $this->key = Registry::get('key');

    }

 }

/**
 * set user Id
 *
 * @method public setUserId()
 * @param int $userId
 * @return void
 * 
 */
 public function setUserId($userId)
 {
   $this->user_id = $userId;   
 }
 
 /**
  * Set user login
  *
  * @param string $user_login
  *
  */
 public function setUserLogin($user_login)
 {
   $this->user_login = remove_accents($user_login);
 }
 
 /**
  * Set user email
  * @param string $user_email
  *
  */
 public function setUserEmail($user_email)
 {
   $this->user_email = $user_email;
 }
 
/**
 * Set user password
 * @param string $user_pass
 */
 public function setUserPass($user_pass)
 {
   $this->user_pass = $user_pass;
 }

/**
 * setPwdHash
 * set password hash validator for cookies
 * 
 * @param string $pwd_hash
 * @return void
 * 
 */
 public function setPwdHash($pwd_hash)
 {
   $this->pwd_hash = $pwd_hash;
 }

/**
 * setSelectorHash
 * set selector hash validator for cookies
 *
 * @param string $selector_hash
 * @return void
 * 
 */
 public function setSelectorHash($selector_hash)
 {
   $this->selector_hash = $selector_hash;
 }

 public function setCookieExpireDate($cookie_expired_date)
 {
   $this->cookie_expired_date = $cookie_expired_date;
 }

/**
 * Set user level
 * @param string $user_level
 */
 public function setUserLevel($user_level)
 {
   $this->user_level = $user_level;
 }

/**
 * Set user fullname
 * @param string $user_fullname
 */
 public function setUserFullname($user_fullname)
 {
   $this->user_fullname = $user_fullname;
 }

/**
 * SetUserUrl
 * 
 * @method public setUserUrl()
 * @param string $user_url
 * 
 */
 public function setUserUrl($user_url)
 {
   $this->user_url = $user_url;
 }

/**
 * SetUserActivationKey
 * 
 * @method public setUserActivationKey($activation_key)
 * @param string $activation_key
 * 
 */
 public function setUserActivationKey($activation_key)
 {
   $this->user_activation_key = $activation_key;
 }
 
/**
 * SetUserSession()
 * @param string $user_session
 * 
 */
 public function setUserSession($user_session)
 {
   $this->user_session = $user_session;
 }
 
/**
 * setUserBanned
 *
 * @param [type] $user_banned
 * @return void
 */
 public function setUserBanned($user_banned)
 {
   $this->user_banned = $user_banned;
 }

/**
 * Retrieving All records from table users
 *
 * @method public grabUsers()
 * @param string $orderBy
 * @param [type] $fetchMode
 * @return array
 * 
 */
 public function grabUsers($orderBy = 'ID', $fetchMode = null)
 {
   $orderBySanitized = sanitize_sql_orderby($orderBy);
   return $this->userDao->getUsers($orderBySanitized, $fetchMode);    
 }
 
 public function grabUser($userId, $fetchMode = null)
 {
   return $this->userDao->getUserById($userId, $this->sanitize, $fetchMode);
 }

 public function grabUserByLogin($user_login, $fetchMode = null)
 {
   return $this->userDao->getUserByLogin($user_login, $fetchMode);
 }
 
 public function grabTokenByLogin($login, $expired, $fetchMode = null)
 {
   return $this->userToken->getTokenByLogin($login, $expired, $fetchMode);
 }

 public function addUser()
 {
   
  $this->validator->sanitize($this->user_login, 'string');
  $this->validator->sanitize($this->user_fullname, 'string');
  $this->validator->sanitize($this->user_email, 'email');

    if (empty($this->user_activation_key)) {
           
        return $this->userDao->createUser([

            'user_login' => $this->user_login,
            'user_email' => $this->user_email,
            'user_pass'  => $this->user_pass,
            'user_level' => $this->user_level,
            'user_fullname' => $this->user_fullname,
            'user_url' => $this->user_url,
            'user_registered' => date("Y-m-d H:i:s"),
            'user_session' => $this->user_session

        ]);
        
    } else {
        
        return $this->userDao->createUser([

            'user_login' => $this->user_login,
            'user_email' => $this->user_email,
            'user_pass'  => $this->user_pass,
            'user_level' => $this->user_level,
            'user_fullname' => $this->user_fullname,
            'user_url' => $this->user_url,
            'user_activation_key' => $this->user_activation_key,
            'user_session' => $this->user_session

        ]);
        
    }
    
 }
 
 public function modifyUser()
 {
  
  $this->validator->sanitize($this->user_url, 'url');
  $this->validator->sanitize($this->user_fullname, 'string');
  $this->validator->sanitize($this->user_email, 'email');
  
  $secret = ScriptlogCryptonize::generateSecretKey();

  if ($this->isUserLevel() != 'administrator') {
   
       if (!empty($this->user_pass)) {
           
           $bind = [
               'user_email'    => $this->user_email,
               'user_pass'     => $this->user_pass,
               'user_fullname' => $this->user_fullname,
               'user_url'      => $this->user_url
              ];

       } else {
           
           $bind = [
               'user_email'    => $this->user_email,
               'user_fullname' => $this->user_fullname,
               'user_url'      => $this->user_url
           ];
           
       }
   
   } else {
       
       if (!empty($this->user_pass)) {
           
           $bind = [
               'user_email' => $this->user_email,
               'user_pass' => $this->user_pass,
               'user_level' => $this->user_level,
               'user_fullname' => $this->user_fullname,
               'user_url' => $this->user_url,
               'user_banned' => $this->user_banned
           ];
           
       } else {
           
           $bind = [
               'user_email' => $this->user_email,
               'user_level' => $this->user_level,
               'user_fullname' => $this->user_fullname,
               'user_url' => $this->user_url,
               'user_banned' => $this->user_banned
           ];
           
       }
       
   }

   if($this->identifyCookieToken($secret)) {
 
      $bind_meta = ['pwd_hash' => $this->pwd_hash, 'selector_hash' => $this->selector_hash, 'expired_date' => $this->cookie_expired_date];

      $this->userToken->updateUserToken($bind_meta, $this->user_login);

   }

   return $this->userDao->updateUser($this->isUserLevel(), $this->sanitize, $bind, $this->user_id);

 }
 
/**
 * Update token expired
 *
 * @param integer $userTokenId
 * @return void
 * 
 */
 public function modifyTokenExpired($userTokenId)
 {
   return $this->userToken->updateTokenExpired($userTokenId);
 }

/**
 * removeUser
 * remove user from record on user table
 * 
 */
 public function removeUser()
 {
   
   $this->validator->sanitize($this->user_id, 'int');

   if (!$this->userDao->getUserById($this->user_id, $this->sanitize)) {
       
      direct_page('index.php?load=users&error=userNotFound', 404);
   
   }
   
   return $this->userDao->deleteUser($this->user_id, $this->sanitize);
   
 }
 
 /**
  * User Level DropDown
  * 
  * @param string $selected
  * @return string
  */
 public function userLevelDropDown($selected = "") 
 {
    return $this->userDao->dropDownUserLevel($selected);
 }

 /**
  * Checking User login
  * @param string $user_login
  * @return boolean
  *
  */
 public function checkUserLogin($user_login)
 {
    return $this->userDao->isUserLoginExists($user_login);
 }

 /**
  * Whether email address exists or not
  *
  * @param string $user_email
  * @return boolean
  */
 public function isEmailExists($user_email)
 {
   return $this->userDao->checkUserEmail($user_email);    
 }
 
/**
 * isUserLevel
 * Check whether user level session defined or not
 * if defined then return it
 * 
 * @method public isUserLevel()
 * @return boolean
 * 
 */ 
 public function isUserLevel()
 {

   if (isset($_COOKIE['scriptlog_auth'])) {

      Authorization::setAuthInstance(new Authentication($this->userDao, $this->userToken, $this->validator));

      return Authorization::authorizeLevel();

   }
   
   if (isset(Session::getInstance()->scriptlog_session_level)) {

      return Session::getInstance()->scriptlog_session_level;
  
   }
  
   return false;

 }

/**
 * reAuthenticateUserPrivilege
 *
 * @param string $login
 * @param string $password
 * @return bool
 * 
 */
public function reAuthenticateUserPrivilege($login, $password)
{
  return $this->userDao->checkUserPassword($login, $password);
}

/**
 * identifyCookieToken
 * Check whether session cookies defined or not
 * if defined then return it
 *
 * @method public identifyUserLogin()
 * @return void
 * 
 */
 public function identifyCookieToken($secret)
 {

   $this->user_login = isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $this->key) : Session::getInstance()->scriptlog_session_login;

   if ( ( isset($_COOKIE['scriptlog_validator']) ) && ( isset($_COOKIE['scriptlog_selector']) ) ) {

      //retrieve user token info
      $token_info = $this->grabTokenByLogin($this->user_login, 0);

      $expected_validator = crypt($_COOKIE['scriptlog_validator'], $token_info['pwd_hash']);
      $correct_validator = crypt($_COOKIE['scriptlog_validator'], $token_info['pwd_hash']);

      $expected_selector = crypt($_COOKIE['scriptlog_selector'], $token_info['selector_hash']);
      $correct_selector = crypt($_COOKIE['scriptlog_selector'], $token_info['selector_hash']);

      if ( hash_equals($expected_validator, $correct_validator) ) {

          if ( Tokenizer::getRandomPasswordProtected($_COOKIE['scriptlog_validator'], $token_info['pwd_hash']) ) {

            $this->validator_verified = true;

          }

      } 

      if ( hash_equals($expected_selector, $correct_selector) ) {

          if ( Tokenizer::getRandomSelectorProtected($_COOKIE['scriptlog_selector'], $token_info['selector_hash'], $secret) ) {

            $this->selector_verified = true;

          }

      }

      if ( $token_info['expired_date'] >= date("Y-m-d H:i:s", time())) {

          $this->expired_verified = true;

      }

      if ((!empty($token_info['ID'])) && $this->validator_verified && $this->selector_verified && $this->expired_verified ) {

           return true;

      } else {

          return false;

      }
      
   }

 }

/**
 * Total User recorded
 * 
 * @param array $data
 * @return integer
 * 
 */
 public function totalUsers($data = null)
 {
  return $this->userDao->totalUserRecords($data);
 }
 
}
