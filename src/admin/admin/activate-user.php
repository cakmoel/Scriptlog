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
    
  include dirname(dirname(__FILE__)).'/lib/main.php';
  
} else {
  
  header("Location: ../install");
  exit();
  
}

$userActivationKey = isset($_GET['key']) ? escape_html($_GET['key']) : '';

// activation key not found then direct page to web root
if (empty($userActivationKey)) {

  direct_page();

} else {

  // activate user
 is_a($authenticator, 'Authentication') ?  $authenticator->activateUserAccount($userActivationKey) : "";

}



