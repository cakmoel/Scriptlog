<?php
/**
 * Collection of form Security function
 * this form security goals are:
 * the form is being submitted by human being
 * that human being is not doing anything nefarious
 * 
 * @see https://css-tricks.com/serious-form-security/
 * @see https://dev.to/felipperegazio/how-to-create-a-simple-honeypot-to-protect-your-web-forms-from-spammers--25n8
 * @see https://gist.github.com/andrewlimaza/958826feac907114a57462bfc8d535ff
 * @see https://stackoverflow.com/questions/17930068/php-form-with-validation-honeypot
 * @see https://stackoverflow.com/questions/9447716/honeypot-php-for-comment-form/9447733#9447733
 * @see https://solutionfactor.net/blog/2014/02/01/honeypot-technique-fast-easy-spam-prevention/
 * @see https://www.sitepoint.com/client-side-form-validation-html5/
 * 
 */

/**
 * Generate form token
 * generating unique value token 
 * 
 * @param string $form
 * @param number|integer $length
 * @return void
 * 
 */
function generate_form_token($form, $length)
{

  $key = random_generator(13);

  $token = null;

  if (function_exists("random_bytes")) {

     $token = bin2hex(random_bytes($length).$key);

  } elseif (function_exists("openssl_random_pseudo_bytes")) {

     $token = bin2hex(openssl_random_pseudo_bytes($length).$key);
       
  } else {

       $token = bin2hex(generate_hash($length).$key);

  }

  $_SESSION[$form.'_token'] = $token;

  return $token;

}

/**
 * verifying form token
 *
 * @param string $form
 * @return void
 * 
 */
function verify_form_token($form)
{

 // check if a session is started and a token is transmitted, if not return an error
  if(!isset($_SESSION[$form.'_token'])) { 
     
    return false;
    
  }
	
  // check if the form is sent with token in it
  if(!isset($_POST['csrf'])) {
    
     return false;

  }
    
  // compare the tokens against each other if they are still the same
  if ($_SESSION[$form.'_token'] !== $_POST['csrf']) {
        
      return false;
    
  }
	
  return true;
    
}

/**
 * Check form request function
 * checking whether data request as same as form field 
 * whitelisting
 *
 * @param array $data
 * @param array $whitelist
 * @return bool
 * 
 */ 
function check_form_request($data, array $whitelist)
{
  
  foreach($data as $key => $value) {

      if (!in_array($key, $whitelist)) {

          return false;

      } else {

          return true;

      }

  }

}

/**
 * scriptpot validate function
 * checking if a honeypot field was filled on the form
 * 
 * @param array $req $_REQUEST superglobal
 * @return boolean 
 * 
 */
function scriptpot_validate($req)
{
  if (!empty($req)) {

      $scriptpot_field = [
          "scriptpot_name", "scriptpot_email"
      ];

      foreach($scriptpot_field as $field) {

          if ((isset($req[$field])) && (!empty($req[$field]))) {

               return false;

          }

      }

  }

  return true;

}
