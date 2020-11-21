<?php
/**
 * login_attempt()
 *
 * @category function
 * @param string $ip
 * @return array
 * 
 */
function get_login_attempt($ip)
{
 
  $sql = "SELECT count(ip_address) AS failed_login_attempt 
          FROM tbl_login_attempt 
          WHERE ip_address = ? 
          AND login_date BETWEEN DATE_SUB( NOW() , INTERVAL 1 DAY ) AND NOW()";

  $row = db_prepared_query($sql, [$ip], "s")->get_result()->fetch_assoc();

  return $row;

}

/**
 * create_login_attempt()
 *
 * @param string $ip
 * @return void
 * 
 */
function create_login_attempt($ip)
{
  
  $sql = "INSERT INTO tbl_login_attempt (ip_address, login_date) VALUES (?, NOW())";

  $stmt = db_prepared_query($sql, [$ip], "s");

  return $stmt;
  
}

/**
 * Delete login attempt
 *
 * @param string $ip
 * @return void
 * 
 */
function delete_login_attempt($ip)
{

   $sql = "DELETE FROM tbl_login_attempt WHERE ip_address = ?";

   $removed = db_prepared_query($sql, [$ip], "s");

   return $removed;

}

/**
 * get_user_signin
 *
 * @param string $user_login
 * @return array
 * 
 */
function get_user_signin($user_login)
{

if (filter_var($user_login, FILTER_VALIDATE_EMAIL)) {

  $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
                 user_session, user_banned, user_signin_count, user_locked_until, login_time
          FROM tbl_users WHERE user_email = ? LIMIT 1";

} else {

  $sql = "SELECT ID, user_login, user_email, user_pass, user_level, user_fullname, user_url, user_registered, 
                 user_session, user_banned, user_signin_count, user_locked_until, login_time
          FROM tbl_users WHERE user_login = ? LIMIT 1";

}

$stmt = db_prepared_query($sql, [$user_login], "s")->get_result()->fetch_assoc();

return $stmt;

}

/**
 * sign_in_count
 *
 * @param int|numeric $sign_in_count
 * @param string $login
 * @return void
 * 
 */
function sign_in_count($sign_in_count, $login)
{

if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

  $sql = "UPDATE tbl_users SET user_signin_count = ? WHERE user_email = ?";

} else {

  $sql = "UPDATE tbl_users SET user_signin_count = ? WHERE user_login = ?";

}

$login_count = db_prepared_query($sql, [$sign_in_count, $login], "is");

return $login_count;

}

/**
 * locked_down_until
 *
 * @param int|numeric $sign_in_count
 * @param string $locked_until
 * @param string $login
 * @return void
 * 
 */
function locked_down_until($sign_in_count, $locked_until, $login)
{

if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

$sql = "UPDATE tbl_users SET user_signin_count = ?, user_locked_until = ? WHERE  user_email = ?";

} else {

$sql = "UPDATE tbl_users SET user_signin_count = ?, user_locked_until = ? WHERE user_login = ?";

}

$locked_down = db_prepared_query($sql, [$sign_in_count, $locked_until, $login], "iss");

return $locked_down;

}