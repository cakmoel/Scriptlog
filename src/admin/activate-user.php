<?php
/**
 * file activate-user.php
 * 
 * @category  file user activation
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * 
 */
if (file_exists(__DIR__ . '/../config.php')) {
    
  include(dirname(dirname(__FILE__)).'/lib/main.php');
  
} else {
  
  header("Location: ../install");
  exit();
  
}

$userActivationKey = isset($_GET['key']) ? escape_html($_GET['key']) : '';

if (empty($userActivationKey)) {

  // activation key not found direct page to web root
  direct_page();

} else {

  // activate user
  $authenticator -> activateUserAccount($userActivationKey);

}



