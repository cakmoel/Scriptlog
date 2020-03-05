<?php
/**
 * UserToken Class extends Dao Class
 * 
 * @category Dao Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class UserTokenDao extends Dao
{
    
/**
 * 
 */
  public function __construct()
  {
    parent::__construct();
  }

/**
 * get token by user email
 * 
 * @param string $user_email
 * @param string $expired
 * @param string $fetchMode default value = null
 * @return mixed
 */
  public function getTokenByLogin($login, $expired, $fetchMode = null)
  {

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

       $sql = "SELECT t.ID, t.user_login, t.pwd_hash, t.selector_hash, 
                     t.is_expired, t.expired_date,
                     u.user_email, u.user_login
             FROM tbl_user_token AS t
             INNER JOIN tbl_users AS u ON t.user_login = u.user_login 
             WHERE u.user_email = :user_email AND t.is_expired = :expired";

       $this->setSQL($sql);

       $userToken = (is_null($fetchMode)) ? $this->findRow([':user_email' => $login, ':expired' => $expired]) : $this->findRow([':user_email'=>$login, ':expired'=>$expired], $fetchMode);
    
    } else {

      $sql = "SELECT t.ID, t.user_login, t.pwd_hash, t.selector_hash, 
                     t.is_expired, t.expired_date,
                     u.user_login, u.user_email
             FROM tbl_user_token AS t
             INNER JOIN tbl_users AS u ON t.user_login = u.user_login 
             WHERE u.user_login = :user_login AND t.is_expired = :expired";

      $this->setSQL($sql);

      $userToken = (is_null($fetchMode)) ? $this->findRow([':user_login' => $login, ':expired' => $expired]) : $this->findRow([':user_login' => $login, ':expired' => $expired], $fetchMode);

    }
      
    return (empty($userToken)) ?: $userToken;

  }

/**
 * create user token
 * 
 * @param array $bind
 * 
 */
  public function createUserToken($bind)
  {

    $this->create("tbl_user_token", [

      'user_login' => $bind['user_login'],
      'pwd_hash' => $bind['pwd_hash'],
      'selector_hash' => $bind['selector_hash'],
      'expired_date' => $bind['expired_date']

    ]);

  }

/**
 * Update token expired
 * 
 * @param string $userTokenId
 * 
 */
public function updateTokenExpired($userTokenId)
{
  $this->modify("tbl_user_token", ['is_expired' => 1], "ID = {$userTokenId}");
}

/**
 * Delete user token
 * deleting user token when logout from app
 *
 * @param string $user_login
 * @return void
 * 
 */
public function deleteUserToken($user_login)
{
  $this->deleteRecord("tbl_user_token", " user_login = '{$user_login}' AND is_expired = '1'");
}

}