<?php 
/**
 * User class extends Dao
 * insert, update, delete 
 * and select records from users table
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class UserDao extends Dao
{
 
 // overrides Dao constructor
 public function __construct()
 {
	parent::__construct();
 }
 
 /**
  * getUsers
  * list of users
  * 
  * @param integer $position
  * @param integer $limit
  * @param static $fetchMode
  * @param string $orderBy
  * @return boolean|array|object
  */
 public function getUsers($orderBy = 'ID', $fetchMode = null)
 {
    
 $sql = "SELECT ID, user_login, user_email, user_fullname,
				user_level, user_session, user_banned, user_signin_count, user_locked_until, login_time
		FROM tbl_users ORDER BY :orderBy ";
     
 $this->setSQL($sql);
     
 $users = (is_null($fetchMode)) ? $this->findAll([':orderBy' => $orderBy]) : $this->findAll([':orderBy' => $orderBy], $fetchMode);

 return (empty($users)) ?: $users;
    
 }

/**
 * getUserById
 * fetch single value of record by ID
 * 
 * @param integer $userID
 * @param object $sanitize
 * @param static $fetchMode = null
 * @return boolean|array|object
 * 
 */
 public function getUserById($userID, $sanitize, $fetchMode = null)
 {
   $cleanID = $this->filteringId($sanitize, $userID, 'sql');
   
   $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
                user_session, user_banned, user_signin_count, user_locked_until, login_time
           FROM tbl_users WHERE ID = :ID LIMIT 1";
   
   $this->setSQL($sql);

   $userById = (is_null($fetchMode)) ? $this->findRow([':ID' => (int)$cleanID]) : $this->findRow([':ID' => (int)$cleanID], $fetchMode);

   return (empty($userById)) ?: $userById;

 }

 /**
  * get user by email
  * 
  * @param string $user_email
  * @param static PDO::FETCH_MODE $fetchMode
  * @return boolean|array|object
  *
  */
 public function getUserByEmail($user_email, $fetchMode = null)
 {
     
   $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
             user_session, user_banned, user_signin_count, user_locked_until, login_time 
           FROM tbl_users WHERE user_email = :user_email LIMIT 1";
   
   $this->setSQL($sql);
   
   $userByEmail = (is_null($fetchMode)) ? $this->findRow([':user_email' => $user_email]) : $this->findRow([':user_email' => $user_email], $fetchMode);
   
   return (empty($userByEmail)) ?: $userByEmail;

 }

/**
 * getUserByLogin()
 *
 * retrieving user record based on user_login
 * 
 * @param string $user_login
 * @param static PDO::FETCH_MODE $fetchMode
 * @return mixed
 * 
 */
 public function getUserByLogin($user_login, $fetchMode = null) 
 {

   $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
                user_session, user_banned, user_signin_count, user_locked_until, login_time
           FROM tbl_users WHERE user_login = :user_login LIMIT 1";

   $this->setSQL($sql);

   $userByLogin = (is_null($fetchMode)) ? $this->findRow([':user_login' => $user_login]) : $this->findRow([':user_login' => $user_login], $fetchMode);

   return (empty($userByLogin)) ?: $userByLogin;

 }

/**
 * getUserBySession()
 *
 * retrieving user record based on user_session
 * 
 * @param string $user_session
 * @param static PDO::FETCH_MODE $fetchMode $fetchMode
 * @return void
 * 
 */
 public function getUserBySession($user_session, $fetchMode = null)
 {
  
    $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
                   user_session, user_banned, user_signin_count, user_locked_until, login_time
            FROM tbl_users 
            WHERE user_session = :user_session
            AND (login_time >= (NOW() - INTERVAL 7 DAY)) AND user_banned = 0; 
            LIMIT 1";

    $this->setSQL($sql);

    $userBySession = (is_null($fetchMode)) ? $this->findRow([':user_session' => $user_session]) : $this->findRow([':user_session' => $user_session], $fetchMode);

    return (empty($userBySession)) ?: $userBySession;

 }

 /**
  * get User by reset key
  * 
  * @param string $reset_key
  * @return mixed
  *  
  */
 public function getUserByResetKey($reset_key)
 {
   $sql = "SELECT ID, user_reset_key, user_reset_complete FROM tbl_users 
           WHERE user_reset_key = :reset_key LIMIT 1";
   
   $this->setSQL($sql);
   
   $resetKeyDetails = $this->findRow([':reset_key' => $reset_key]);

   return (empty($resetKeyDetails)) ?: $resetKeyDetails;
   
 }

 /**
  * Create user
  * insert new record
  * 
  * @param array $bind
  *
  */
 public function createUser($bind) 
 {
	
  $hash_password = scriptlog_password($bind['user_pass']);
  
  $user_url = (empty($bind['user_url'])) ? '#' : $bind['user_url'];

  if (!empty($bind['user_activation_key'])) {
	  
	  $this->create("tbl_users", [
	          
	          'user_login' => $bind['user_login'],
	          'user_email' => $bind['user_email'],
	          'user_pass'  => $hash_password,
	          'user_level' => $bind['user_level'],
	          'user_fullname' => $bind['user_fullname'],
	          'user_url'   => $user_url,
	          'user_activation_key' => $bind['user_activation_key'],
	          'user_session' => $bind['user_session']
	          
	      ]);
	      
   } else {
	      
      $this->create("tbl_users", [
	          
          'user_login' => $bind['user_login'],
          'user_email' => $bind['user_email'],
          'user_pass'  => $hash_password,
          'user_level' => $bind['user_level'],
          'user_fullname' => $bind['user_fullname'],
          'user_url'   => $user_url,
          'user_registered' => $bind['user_registered'],
          'user_session' => $bind['user_session']
          
      ]);
	      
   }
	   
 }

 /**
  * Update user
  * Modify user record in user table
  * 
  * @param string $accessLevel
  * @param array $bind
  * @param integer $ID
  */
 public function updateUser($accessLevel, $sanitize, $bind, $userID)
 {
  
    $cleanID = $this->filteringId($sanitize, $userID, 'sql');
  
    $hash_password = scriptlog_password($bind['user_pass']);
  
     if ($accessLevel != 'administrator') {
         
         if (empty($bind['user_pass'])) {
             
             $bind = array(
                'user_email' => $bind['user_email'],
                'user_fullname' => $bind['user_fullname'],
                'user_url' => $bind['user_url'] 
             );
             
         } else {
             
             $bind = array(
                 'user_email' => $bind['user_email'],
                 'user_pass' => $hash_password,
                 'user_fullname' => $bind['user_fullname'],
                 'user_url' => $bind['user_url']
             );
             
         }
         
     } else {
         
         if (empty($bind['user_pass'])) {
             
             $bind = array(
                 'user_email' => $bind['user_email'],
                 'user_level' => $bind['user_level'],
                 'user_fullname' => $bind['user_fullname'],
                 'user_url'=> $bind['user_url']
             );
             
         } else {
              
             $bind = array(
                 'user_email' => $bind['user_email'],
                 'user_pass' => $hash_password,
                 'user_level' => $bind['user_level'],
                 'user_fullname' => $bind['user_fullname'],
                 'user_url' => $bind['user_url']
             );
             
         }
          
     }
     
     $this->modify("tbl_users", $bind, "ID = ".(int)$cleanID);
     
 }

 /**
  * Update user session
  * 
  * @param string $accessLevel
  * @param object $sanitize
  * @param array $bind
  * @param integer $userID
  */
 public function updateUserSession($bind, $userID)
 { 
   $this->modify("tbl_users", ['user_session' => generate_session_key($bind['user_session'], 32), 'login_time' => date('Y-m-d H:i:s')], "ID = {$userID}");
 }

 /**
  * Update reset key
  * update temporary reset key 
  * to reset password with key sent to user email account
  *
  * @param string $reset_key
  * @param string $user_email
  *
  */
 public function updateResetKey($bind, $user_email)
 {
   $this->modify("tbl_users", ['user_reset_key' => $bind['user_reset_key'], 'user_reset_complete' => $bind['user_reset_complete']], "user_email = '{$user_email}'");
 }

 /**
  * Recover New password
  * 
  * @param array $bind
  * @param integer $userID
  *
  */
 public function recoverNewPassword($bind, $userID)
 {
   $recoverPassword = scriptlog_password($bind['user_pass']);
   $this->modify("tbl_users", ['user_pass' => $recoverPassword, 'user_reset_complete' => $bind['user_reset_complete']], "ID = '{$userID}'");
          
 }

 /**
  * Activate user
  * 
  * @param string $key
  * @return int
  */
 public function activateUser($key)
 {
   $userAccount = false;

   $cek_user_key = $this->checkActivationKey($key);
   
   if (false === $cek_user_key) {
       
       $userAccount = false;
       
   } else {
       
       $bind = ['user_activation_key' => '1', 'user_registered' => date("Y-m-d H:i:s")];
       $this->modify("tbl_users", $bind, "user_activation_key = {$key}");
       $userAccount = true;
       
   }
   
   return $userAccount;

 }
 
 /**
  * Delete user
  * delete an existing records in user table
  * 
  * @param integer $ID
  * @param object $sanitizing
  */
 public function deleteUser($ID, $sanitize)
 {
  
  $cleanID = $this->filteringId($sanitize, $ID, 'sql');
   
  $this->deleteRecord("tbl_users", "ID = ".(int)$cleanID);
	 
 }
 
 /**
  * set user level
  * 
  * @param string $selected
  * @return string
  */
 public function dropDownUserLevel($selected = '')
 {
  
  $name = 'user_level';
  $levels = array('manager'=>'Manager', 
                  'editor' => 'Editor', 
                  'author'=>'Author', 
                  'contributor'=>'Contributor');
  
  if ($selected != '') {
      $selected = $selected;
  } 
  
  return dropdown($name, $levels, $selected);
  
 }

/**
 * isUserLoginExists
 *
 * checkign whether user_login availability
 * 
 * @param [type] $user_login
 * @return boolean
 */
 public function isUserLoginExists($user_login)
 {
   
   $sql = "SELECT COUNT(ID) FROM tbl_users WHERE user_login = ?";
   $this->setSQL($sql);
   $stmt = $this->findColumn([$user_login]);
     
   if ($stmt == 1) {
      
      return true;
   
   } else {
      
      return false;
       
   }
	
 }
	 
/**
 * checkUserSession
 *
 * checking whether user_session availability
 * 
 * @param string $user_session
 * @return bool
 * 
 */
 public function checkUserSession($user_session)
 {

    $sql = "SELECT COUNT(ID) FROM tbl_users WHERE user_session = :user_session ";
    
    $this->setSQL($sql);
    
    $stmt = $this->findColumn([':user_session' => $user_session]);     
    
    return ($stmt == 1) ? true : false;
 
 }

 /**
  * checking email
  * 
  * @param string $email
  * @return boolean
  */
 public function checkUserEmail($email)
 {
    $sql = "SELECT ID FROM tbl_users WHERE user_email = :email LIMIT 1";
    $this->setSQL($sql);
    $stmt = $this->checkCountValue([':email' => $email]);
    return($stmt > 0);
 }

/**
 * Checking user password
 * 
 * @method public checkUserPassword()
 * @param string $email
 * @param string $password
 * @return bool
 * 
 */
 public function checkUserPassword($login, $password)
 {
    
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

        $sql = "SELECT user_pass FROM tbl_users WHERE user_email = :user_email LIMIT 1";
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([':user_email' => $login]);

        if ($stmt > 0) {
        
            $row = $this->findRow([':user_email' => $login]);
            
            $expected = crypt($password, $row['user_pass']);
            $correct = crypt($password, $row['user_pass']);
    
            if(!function_exists('hash_equals')) {
    
                if(timing_safe_equals($expected, $correct) == 0) {
    
                    if(scriptlog_verify_password($password, $row['user_pass'])) {
    
                        return true;
    
                    }
    
                }
    
            } else {
    
                if(hash_equals($expected, $correct)) {
    
                    if (scriptlog_verify_password($password, $row['user_pass'])) {
    
                        return true;
    
                    }
    
                }
                
            }
            
        }
    
    } else {

        $sql = "SELECT user_pass FROM tbl_users WHERE user_login = :user_login LIMIT 1";
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([':user_login' => $login]);

        if ($stmt > 0) {
        
            $row = $this->findRow([':user_login' => $login]);
            
            $expected = crypt($password, $row['user_pass']);
            $correct = crypt($password, $row['user_pass']);
    
            if(!function_exists('hash_equals')) {
    
                if(timing_safe_equals($expected, $correct) == 0) {
    
                    if(scriptlog_verify_password($password, $row['user_pass'])) {
    
                        return true;
    
                    }
    
                }
    
            } else {
    
                if(hash_equals($expected, $correct)) {
    
                    if (scriptlog_verify_password($password, $row['user_pass'])) {
    
                        return true;
    
                    }
    
                }
                
            }
            
        }

    }
    
    return false;
    
 }
 
/**
 * checkUserId
 *
 * @param integer $userID
 * @param object $sanitize
 * @return numeric
 * 
 */
 public function checkUserId($userID, $sanitize)
 {
     
     $sql = "SELECT ID FROM tbl_users WHERE ID = ?";

     $idsanitized = $this->filteringId($sanitize, $userID, 'sql');

     $this->setSQL($sql);

     $stmt = $this->checkCountValue([$idsanitized]);

     return($stmt > 0);

 }
 
/**
 * Total user records
 * 
 * @param array $data default = null
 * @return integer
 *  
 */
 public function totalUserRecords($data = array())
 {
    $sql = "SELECT ID FROM tbl_users";
     
    $this->setSQL($sql);
     
    return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);
     
 }
 
 /**
  * Check Activation Key
  * 
  * @param string $key
  * @return boolean
  */
 private function checkActivationKey($key)
 {
    $sql = "SELECT COUNT(ID) FROM tbl_users WHERE user_activation_key = :user_activation_key";
    
    $this->setSQL($sql);
     
    if (!($this->findColumn([':user_activation_key' => $key])) == 1) {
         
       return false;
         
    } 
    
 }
 
}


