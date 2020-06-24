<?php
/**
 * login attempt function
 *
 * @category function
 * @param string $ip
 * @return mixed
 * 
 */
function get_login_attempt($ip)
{
 
  if(version_compare(PHP_VERSION, "7.3", ">=")) {

    $sql = "SELECT count(ip_address) AS failed_login_attempt 
            FROM tbl_login_attempt 
            WHERE ip_address = '$ip' 
            AND login_date BETWEEN DATE_SUB( NOW() , INTERVAL 1 DAY ) AND NOW()";

    if($result = db_simple_query($sql)) {

      if(is_array($result)) {

        while ($row = $result->fetch_assoc()) {
         
             $row['failed_login_attempt'];

        }

      }
       
    }
    
  } else {

    $sql = "SELECT count(ip_address) AS failed_login_attempt 
    FROM tbl_login_attempt 
    WHERE ip_address = ? 
    AND login_date BETWEEN DATE_SUB( NOW() , INTERVAL 1 DAY ) AND NOW()";

    $row = db_prepared_query($sql, [$ip], "s")->get_result()->fetch_assoc();


  }

  return $row;

}

/**
 * Create login attempt
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

   unset($_SESSION['scriptlog_captcha_code']);

   $sql = "DELETE FROM tbl_login_attempt WHERE ip_address = ?";

   $removed = db_prepared_query($sql, [$ip], "s");

   return $removed;

}
