<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

if(isset($_GET['load']) && $_GET['load'] == basename('logout')) {

 $authenticator -> logout();
    
}

