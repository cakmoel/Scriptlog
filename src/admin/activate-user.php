<?php
/**
 * file activate-user.php
 * 
 * @category  file user activation
 * @package   SCRIPTLOG
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

$userActivationKey = isset($_GET['key']) ? htmlspecialchars(strip_tags(trim($_GET['key']))) : '';

if (empty($userActivationKey)) {

  // activation key not found direct page to web root
  direct_page();

} else {

  // activate user
  $authenticator -> activateUserAccount($userActivationKey);

}



