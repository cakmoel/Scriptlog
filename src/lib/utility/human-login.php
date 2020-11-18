<?php
/**
 * human_login_id()
 * 
 * generate random numbers for loginId
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return void
 * 
 */
function human_login_id()
{
 
 $random_int = ircmaxell_generator_numbers(0, 99999);
 
 $_SESSION['human_login_id'] = $random_int;
 
 return $random_int;

}

/**
 * Verify_human_login_id()
 * 
 * a function to verify human_login_id
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $loginId
 * @return void
 * 
 */
function verify_human_login_id($loginId)
{
// check if a session is started and a token is transmitted, if not return an error
if(!isset($_SESSION['human_login_id'])) { 
     
   return false;
   
 }
  
 // check if the form is sent with token in it
 if(!isset($loginId)) {
   
    return false;

 }
   
 // compare the tokens against each other if they are still the same
 if ($_SESSION['human_login_id'] !== $loginId) {
       
     return false;
   
 }
  
 return true;

}

/**
 * human_login_request()
 * 
 * generate login query string parameters
 *
 * @category function
 * @author M.Noermooehammad
 * @license MIT
 * @version 1.0
 * @param string $base
 * @param array $data
 * @return mixed
 * 
 */
function human_login_request($base, array $data)
{

$html = [];

$action = (is_array($data) && array_key_exists(0, $data)) ? rawurlencode($data[0]) : '';
$id = (is_array($data) && array_key_exists(1, $data)) ? urlencode($data[1]) : null;
$uniqueKey =  (is_array($data) && array_key_exists(2, $data)) ? urlencode($data[2]) : null;

$query_data = array(

   'action' => $action,
   'Id' => abs((int)$id),
   'uniqueKey'=> sanitize_urls($uniqueKey),

);

$html['doLogin'] = build_query($base, $query_data);

return $html;

}

/**
 * safe_human_login()
 *
 * checking form login security
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 * 
 */
function safe_human_login($ip, $loginId, $uniqueKey, array $values)
{

if( check_form_request($values, ['login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_code', 'remember', 'csrf', 'LogIn']) == false )  {

     header(APP_PROTOCOL.' 413 Payload Too Large');
     header('Status: 413 Payload Too Large');
     header('Retry-After: 3600');
     die("413 Payload Too Large");

}

if(false === verify_human_login_id($loginId)) {

   http_response_code(400);
   write_log($ip, 'unpleasant login attempt!');
   
}

if(!isset($uniqueKey) && ($uniqueKey !== md5(app_key().$ip))) {

   http_response_code(400);
   write_log($ip, 'unpleasant login attempt!');

}

}

/**
 * processiong_human_login()
 *
 * @param object $authenticator
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 * 
 */
function processing_human_login($authenticator, $ip, $loginId, $uniqueKey, $errors, array $values)
{

$login = (isset($values['login'])) ? prevent_injection($values['login']) : null;
$user_pass = (isset($values['user_pass'])) ? prevent_injection($values['user_pass']) : null;
$csrf = isset($values['csrf']) ? $values['csrf'] : '';
$captcha_code = isset($values['captcha_code']) ? $values['captcha_code'] : '';

$is_authenticated = false;
$captcha = false;

// validate form
safe_human_login($ip, $loginId, $uniqueKey, $values);
$valid = !empty($csrf) && verify_form_token('login_form', $csrf);

if(!$valid) {

   $errors['errorMessage'] = "Sorry, attack detected!";

}

$failed_login_attempt = get_login_attempt($ip)['failed_login_attempt'];

if ((empty($login)) && (empty($user_pass))) {
  
   $errors['errorMessage'] = "All Column must be filled";

}

if ($authenticator->validateUserAccount($login, $user_pass)) {

   $is_authenticated = true;
   
}

if ( ( $is_authenticated == true) && ( $captcha == true ) ) {

   unset($_SESSION['human_login_id']);

   $authenticator->login($values);

   delete_login_attempt($ip);

   direct_page('index.php?load=dashboard', 200);

}  else {

   $errors['errorMessage'] = "Invalid Login!";

   if ($failed_login_attempt < 5 ) {

      create_login_attempt($ip);

   } else {

      $errors['errorMessage'] = "You have tried more than 5 times. Please enter a captcha code!";

   } 

}

if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

   if ($authenticator->checkEmailExists($login) == false) {

      $errors['errorMessage'] = "Your email address is not registered";
       
   }

} else {

   if (!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $login)) {

     $errors['errorMessage'] = "Incorrect username!";
     
   }
    
}

if ( ( isset($_SESSION['scriptlog_captcha_code']) ) && ( $captcha_code !== $_SESSION['scriptlog_captcha_code'] ) ) {
  
   $captcha = false;
   $errors['errorMessage'] = "Please enter a captcha code correctly";

} else {

   $captcha = true;
   
}

if (scriptpot_validate($values) == false) {

   $errors['errorMessage'] = "anomaly behaviour detected!";

}

return array($errors, $failed_login_attempt);

}