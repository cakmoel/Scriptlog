<?php

use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * user_init_dao
 *
 * @return object|bool Returns true if class is a defined class, false otherwise. 
 * 
 */
function user_init_dao()
{
  return (class_exists('UserDao')) ? new UserDao() : "";
}

/**
 * checking_user_login
 *
 * @param string $userLogin
 * @return bool
 * 
 */
function is_username_available($userLogin)
{
  $userDao = user_init_dao();
  return $userDao->isUserLoginExists($userLogin);
}

/**
 * is_email_exists
 * 
 * @param string $userEmail
 * @return boolean
 */
function is_email_exists($userEmail)
{
  $userDao = user_init_dao();
  return $userDao->checkUserEmail($userEmail);
}

/**
 * signup_id
 *
 * @category function
 * @author Nirmala Khanza <nirmala.adiba.khanza@email.com>
 * @license MIT
 * @version 1.0
 * @return bool
 * 
 */
function signup_id()
{
  return form_id("signup");
}

/**
 * verify_signup_id
 *
 * @param int|num $signupId
 * @return bool
 */
function verify_signup_id($signupId)
{
  if ((!isset($_SESSION['human_signup_id'], $signupId)) || ($_SESSION['human_signup_id'] !== $signupId)) {

    return false;
  }

  return true;
}

/**
 * checking_signup_request
 *
 * @param string $ip
 * @param int $signupId
 * @param string $uniqueKey
 * @param array $values
 * @return void
 * 
 */
function checking_signup_request($ip, $signupId, $uniqueKey, array $values)
{
  if (function_exists('check_form_request') && check_form_request($values, ['user_login', 'user_email', 'user_pass', 'user_pass2', 'scriptpot_name', 'scriptpot_email', 'iagree', 'csrf', 'SignUp']) === false) {
    header(APP_PROTOCOL . ' 413 Payload Too Large', true, 413);
    header('Status: 413 Payload Too Large');
    header('Retry-After: 3600');
    exit("413 Payload Too Large");
  }

  if (false === verify_signup_id($signupId)) {
    http_response_code(400);
    exit("400 Bad Request");
  }

  if (!isset($uniqueKey) && ($uniqueKey !== md5(app_key() . $ip))) {

    http_response_code(400);
    exit("400 Bad Request ");
  }
}

/**
 * processing_signup
 *
 * @param string $ip
 * @param int|num $signupId
 * @param string $uniqueKey
 * @param mixed $errors
 * @param array $values
 * @return void
 * 
 */
function processing_signup($ip, $signupId, $uniqueKey, $errors, array $values)
{

  $checkError = true;
  $signup_success = array();

  $user_login = (isset($values['user_login']) && $values['user_login'] == $_POST['user_login'] ? prevent_injection($values['user_login']) : null);
  $user_email = (isset($values['user_email']) && $values['user_email'] == $_POST['user_email'] ? prevent_injection($values['user_email']) : null);
  $user_pass = (isset($values['user_pass']) && $values['user_pass'] == $_POST['user_pass'] ? prevent_injection($values['user_pass']) : null);
  $confirm_pass = (isset($values['user_pass2']) && $values['user_pass2'] == $_POST['user_pass2'] ? prevent_injection($values['user_pass2']) : null);
  $iagree = (isset($values['iagree']) && $values['iagree'] == $_POST['iagree'] ? $values['iagree'] : null);
  $csrf = (isset($values['csrf']) && $values['csrf'] == $_POST['csrf']  ? $values['csrf'] : '');

  $user_session = function_exists('openssl_random_pseudo_bytes') ? substr(hash('sha256', bin2hex(openssl_random_pseudo_bytes(ceil(32 / 2)))), 0, 32) : '';
  $hash_password = function_exists('scriptlog_password') ? scriptlog_password($user_pass) : '';
  $user_level = function_exists('membership_default_role') ? membership_default_role() : '';

  // validate registration request
  checking_signup_request($ip, $signupId, $uniqueKey, $values);
  $valid = !empty($csrf) && verify_form_token('signup_form', $csrf);

  if (!$valid) {

    $checkError = false;
    $errors['errorMessage'] = "Sorry, attack detected!";
  }

  if (empty($user_login) || empty($user_email) || empty($user_pass) || empty($confirm_pass)) {

    $checkError = false;
    $errors['errorMessage'] = "All columns required must be filled";
  }

  if (!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $user_login)) {

    $checkError = false;
    $errors['errorMessage'] = "Username requires only alphanumerics characters, underscore and dot. Number of characters must be between 8 to 20";
  } elseif (is_username_available($user_login)) {

    $checkError = false;
    $errors['errorMessage'] = "Username already in use";
  }

  if (!email_validation($user_email, new RFCValidation())) {

    $checkError = false;
    $errors['errorMessage'] = MESSAGE_INVALID_EMAILADDRESS;
  } elseif ((checking_internet_connection()) && (!email_multiple_validation($user_email))) {

    $checkError = false;
    $errors = MESSAGE_UNKNOWN_DNS;
  } elseif (is_email_exists($user_email)) {

    $checkError = false;
    $errors['errorMessage'] = "Email already in use";
  }

  if (isset($user_pass)) {

    if ((function_exists('check_common_password')) && (check_common_password($user_pass) === true)) {

      $checkError = false;
      $errors['errorMessage'] = "Your password seems to be the most hacked password, please try another";
    }

    if (false === check_pwd_strength($user_pass)) {
      $checkError = false;
      $errors['errorMessage'] = MESSAGE_WEAK_PASSWORD;
    }
  }

  if (scriptpot_validate($values) === false) {

    http_response_code(403);
    $errors['errorMessage'] = "anomaly behaviour detected!";
  }

  if ((!empty($iagree)) && ($checkError === true)) {

    medoo_insert("tbl_users", [
      "user_login" => $user_login,
      "user_email" => $user_email,
      "user_pass" => $hash_password,
      "user_level" => $user_level,
      "user_url"   => "#",
      "user_registered" => date("Y-m-d H:i:s"),
      "user_session" => $user_session
    ]);

    $signup_success['successMessage'] = "Register Successful";
  } else {

    $checkError = false;
    $errors['errorMessage'] = "Please checked terms of use !";
  }

  return array($errors, $signup_success);
}
