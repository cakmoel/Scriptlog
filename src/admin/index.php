<?php 
/**
 * File index.php
 * 
 * @category admin/index.php file
 * @author   M.Noermoehammad 
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */

if (file_exists(__DIR__.'/../config.php') && is_file(__DIR__.'/../config.php')) {
    
    include __DIR__ . '/../lib/main.php';

    $ip = (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : get_ip_address();

    include __DIR__ . '/authorizer.php';

    if ((isset($ubench)) && (true === APP_DEVELOPMENT)) {
        
        $ubench->start();

    }

} else {

    header("Location: ../install");
    exit();
       
}

if (!$loggedIn) {
   
   header("Location: login.php");
   exit();
   
} else {

    $decrypt_login = isset($_COOKIE['scriptlog_auth']) ? scriptlog_decipher($_COOKIE['scriptlog_auth'], $key) : "";
    
    $user_login = isset($_COOKIE['scriptlog_auth']) ? user_info($authenticator, $decrypt_login)['user_login'] : Session::getInstance()->scriptlog_session_login;
    $user_email = isset($_SESSION['scriptlog_session_email']) ? Session::getInstance()->scriptlog_session_email : user_info($authenticator, $user_login)['user_email'];
    $user_level = isset($_SESSION['scriptlog_session_level']) ? Session::getInstance()->scriptlog_session_level : user_info($authenticator, $user_login)['user_level'];
    $user_id = isset($_SESSION['scriptlog_session_id']) ? Session::getInstance()->scriptlog_session_id : user_info($authenticator, $user_login)['ID'];
    $user_session = user_info($authenticator, $user_login)['user_session'];

    // Breadcrumb
    $breadcrumb = isset($_GET['load']) ? htmlentities(sanitize_urls($_GET['load']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : http_response_code();
    
    // Current URL
    $current_url =  preg_replace("/\/index\.php.*$/i", "", app_url().DS.APP_ADMIN);
    
    // retrieve plugin actived -- for administrator
    $plugin_navigation = setplugin($user_level, 'private');
    
    include dirname(__FILE__) . DS .'admin-layout.php';
    
    admin_header($current_url, $breadcrumb, admin_query());
    
    include dirname(__FILE__) . '/navigation.php';
     
    include dirname(__FILE__) . DS .'request.php';
    
    admin_footer($current_url, $ubench);
    
    ob_end_flush();
    
}

