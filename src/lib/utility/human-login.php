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
 
 $random_int = ircmaxell_generator_numbers(0, 999);
 
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

if( ( !isset($_SESSION['human_login_id']) ) || ( !isset($loginId) ) || ( $_SESSION['human_login_id'] !== $loginId ) ) { 
     
   return false;
   
}
    
return true;

}

/**
 * review_login_attempt
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $ip
 * @return bool
 * 
 */
function review_login_attempt($ip)
{

 return ( alert_login_attempt($ip)['alert_login_attempt'] >= 20 ? true : false );

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

$form = [];

$action = ( is_array($data) && array_key_exists(0, $data) ? rawurlencode($data[0]) : '' );
$id = ( is_array($data) && array_key_exists(1, $data) ? urlencode($data[1]) : null );
$uniqueKey =  ( is_array($data) && array_key_exists(2, $data) ? urlencode($data[2]) : null );

$query_data = array(

   'action' => $action,
   'Id' => abs((int)$id),
   'uniqueKey'=> sanitize_urls($uniqueKey),

);

$form['doLogin'] = build_query($base, $query_data);

return Sanitize::severeSanitizer($form);

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

if( check_form_request($values, ['login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_login', 'remember', 'csrf', 'LogIn']) === false )  {

     header(APP_PROTOCOL.' 413 Payload Too Large', true, 413);
     header('Status: 413 Payload Too Large');
     header('Retry-After: 3600');
     exit("413 Payload Too Large");

}

if( false === verify_human_login_id($loginId)) {

   http_response_code(400);
   exit("400 Bad Request");

}

if(!isset($uniqueKey) && ($uniqueKey !== md5(app_key().$ip))) {

   http_response_code(400);
   exit("400 Bad Request ");
   
}

if ( review_login_attempt($ip) === true ) {

   write_log($ip, 'unpleasant login attempt!');
   
   delete_login_attempt($ip);

}

if ( midfielder() === true) {

   sleep(20);

   defender();
   
}

}

/**
 * processing_human_login()
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
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

   $login = ( isset($values['login']) && $values['login'] == $_POST['login'] ? prevent_injection($values['login']) : null );
   $user_pass = ( isset($values['user_pass']) && $values['user_pass'] == $_POST['user_pass'] ? prevent_injection($values['user_pass']) : null );
   $csrf = ( isset($values['csrf']) && $values['csrf'] == $_POST['csrf']  ? $values['csrf'] : '' );
      
   $captcha_verified = true;
      
   // validate form
   safe_human_login($ip, $loginId, $uniqueKey, $values);
   $valid = !empty($csrf) && verify_form_token('login_form', $csrf);
      
   if(!$valid) {
      
      $errors['errorMessage'] = "Sorry, attack detected!";
      
   }
   
   if ( ( !empty($values) ) && ( isset( $values['captcha_login'] ) && $values['captcha_login'] == $_POST['captcha_login'] ) && ( $values['captcha_login'] != Session::getInstance()->captcha_login)) {
      
      $captcha_verified = false;
      $errors['errorMessage'] = "Enter captcha code correctly";
       
   }
      
   $failed_login_attempt = get_login_attempt($ip)['failed_login_attempt'];
   $data = get_user_signin($login);
   $datetime = ( !empty($data['user_locked_until']) ? strtotime($data['user_locked_until']) : null );
   $signin = ( !empty($data['user_signin_count']) ? $data['user_signin_count'] : 0 );
   
 if ( !empty($values) && $captcha_verified === true ) {
       
   $authenticate_user = is_a($authenticator, 'Authentication') ? $authenticator->validateUserAccount($login, $user_pass) : "";
   
   if (time() > $datetime) {
   
      if ($authenticate_user === false) {
   
         http_response_code(403);
         $errors['errorMessage'] = "Check your login details";
      
         $signin++;
      
         if ( ($failed_login_attempt < 5) || ( $signin % 15 ) ) {
           
            create_login_attempt($ip);
      
            sign_in_count($signin, $login);
      
         } else {
      
            $errors['errorMessage'] = "Please enter a captcha code!";
   
            $multiplicator = $signin / 15;
        
            if ($multiplicator > 5) {
            
               $multiplicator = 5;
        
            }
        
            locked_down_until($signin, date('Y-m-d H:i:s', time() + 60 * 5 * $multiplicator), $login);
      
         } 
      
      } else {
      
         if ((Session::getInstance()->human_login_id) && (Session::getInstance()->captcha_login)) {
   
            unset($_SESSION['human_login_id']);
            unset($_SESSION['captcha_login']);
   
         } 
         
         if (!$data['user_banned']) {
      
           if ($data['user_signin_count']) {
      
              signin_count_to_zero($login);
      
           }
      
           if ($datetime) {
      
              locked_down_to_null($login);
      
           }
           
           $authenticator->login($_POST);
      
           delete_login_attempt($ip);
      
           direct_page('index.php?load=dashboard', 302);
      
         }
         
      }
   
      if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
      
         if ($authenticator->checkEmailExists($login) === false) {
         
            $errors['errorMessage'] = "Email or password is not correct";
                
         }
         
      } else {
           
         if (!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $login)) {
         
            $errors['errorMessage'] = "Username or password is not correct";
              
         } 
             
      }
      
      if (scriptpot_validate($values) === false) {
         
         http_response_code(403);
         $errors['errorMessage'] = "anomaly behaviour detected!";
         
      }
   
   } else {
   
      http_response_code(403);
      $datetime = date("Y-m-d H:i:s", $datetime);
      $errors['errorMessage'] = "Account is locked until {$datetime}";
   
   }
       
 } 
      
 return array($errors, $failed_login_attempt);
         
}