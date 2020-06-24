<?php
/**
 * Safe human login
 *
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 * 
 */
function safe_human_login(array $values)
{

if( check_form_request($values, ['login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_code', 'remember', 'csrf', 'LogIn']) == false )  {

     header(APP_PROTOCOL.' 413 Payload Too Large');
     header('Status: 413 Payload Too Large');
     header('Retry-After: 3600');
     die("413 Payload Too Large");

}

}

/**
 * Processiong human login
 *
 * @param object $authenticator
 * @param string $ip
 * @param integer|number $loginId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 * 
 */
function processing_human_login($ip, $authenticator, $errors, array $values)
{

$login = (isset($values['login'])) ? prevent_injection($values['login']) : null;
$user_pass = (isset($values['user_pass'])) ? prevent_injection($values['user_pass']) : null;
$csrf = isset($values['csrf']) ? $values['csrf'] : '';
$captcha_code = isset($values['captcha_code']) ? $values['captcha_code'] : '';

$is_authenticated = false;
$captcha = true;

// validate form
$valid = !empty($csrf) && verify_form_token('login_form', $csrf);

if(!$valid) {

   $errors['errorMessage'] = "Sorry, attack detected!";

}

if (isset($_SESSION['scriptlog_captcha_code']) && ($captcha_code !== $_SESSION['scriptlog_captcha_code'])) {
  
   $captcha = false;
   $errors['errorMessage'] = "Please enter a captcha code correctly.";

}

$failed_login_attempt = get_login_attempt($ip)['failed_login_attempt'];

if ((empty($login)) && (empty($user_pass))) {
  
   $errors['errorMessage'] = "All Column must be filled";

}

if ($authenticator -> validateUserAccount($login, $user_pass)) {

   $is_authenticated = true;
   
}

if (($is_authenticated == true) && ($captcha == true)) {

   unset($_SESSION['human_login_id']);

   $authenticator->login($values);

   delete_login_attempt($ip);

   direct_page('index.php?load=dashboard', 302);

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

if (scriptpot_validate($values) == false) {

   $errors['errorMessage'] = "anomaly behaviour detected!";

}

return array($errors, $failed_login_attempt);

}