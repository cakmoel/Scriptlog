<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : get_ip_address();
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

if(isset($_GET['load']) && $_GET['load'] == basename('logout')) {

    if(Session::getInstance()->scriptlog_session_ip !== $ip_address || Session::getInstance()->scriptlog_session_agent !== $user_agent) {

        session_destroy();
        session_regenerate_id();
        Session::getInstance()->scriptlog_session_agent = $user_agent;
        Session::getInstance()->scriptlog_session_ip = $ip_address;
        
    }

    $authenticator -> logout();
    
}


