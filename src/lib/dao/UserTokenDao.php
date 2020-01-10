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
 * @return 
 * 
 */
  public function getTokenByUserEmail($user_email, $expired, $fetchMode = null)
  {
      $sql = "SELECT t.ID, t.user_id, t.pwd_hash, t.selector_hash, 
                     t.is_expired, t.expired_date,
                     u.user_email 
             FROM tbl_user_token AS t
             INNER JOIN tbl_users AS u ON t.user_id = u.ID 
             WHERE u.user_email = :user_email AND t.is_expired = :expired";

      $this->setSQL($sql);
  
      $userToken = (is_null($fetchMode)) ? $this->findRow([':user_email' => $user_email, ':expired' => $expired]) : $this->findRow([':user_email' => $user_email, ':expired' => $expired], $fetchMode);
      
      return (empty($userToken)) ?: $userToken;

  }

/**
 * Update token expired
 * 
 * @param string $userTokenId
 * 
 */
  public function updateTokenExpired($userTokenId)
  {
    $bind = ['is_expired' => 1];
    $this->modify("tbl_user_token", $bind, "ID = {$userTokenId}");
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

      'user_id' => $bind['user_id'],
      'pwd_hash' => $bind['pwd_hash'],
      'selector_hash' => $bind['selector_hash'],
      'expired_date' => $bind['expired_date']

    ]);

  }

}