<?php 
/**
 * File index.php
 * 
 * @category admin\index.php file
 * @author   M.Noermoehammad 
 * @license  https://opensource.org/licenses/MIT MIT License
 * 
 */
$ip = (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : get_ip_address();

if (file_exists(__DIR__ . '/../config.php')) {
    
    include __DIR__ . '/../lib/main.php';
    require __DIR__ . '/authorizer.php';

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

    $user_id = (isset($_COOKIE['scriptlog_cookie_id'])) ? $_COOKIE['scriptlog_cookie_id'] : Session::getInstance()->scriptlog_session_id;
    $user_email = (isset($_COOKIE['scriptlog_cookie_email'])) ? $_COOKIE['scriptlog_cookie_email'] : Session::getInstance()->scriptlog_session_email;
    $user_level = (isset($_COOKIE['scriptlog_cookie_level'])) ? $_COOKIE['scriptlog_cookie_level'] : Session::getInstance()->scriptlog_session_level;
    $user_login = (isset($_COOKIE['scriptlog_cookie_login'])) ? $_COOKIE['scriptlog_cookie_login'] : Session::getInstance()->scriptlog_session_login;
    $user_session = user_info($authenticator, $user_login)['user_session'];
        
    // BreadCrumbs
    $breadCrumbs = isset($_GET['load']) ? htmlentities(sanitize_urls($_GET['load']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : http_response_code();
    
    // Current URL
    $currentURL =  preg_replace("/\/index\.php.*$/i", "", app_url().DS.APP_ADMIN);
    
    // retrieve plugin actived -- for administrator
    $plugin_navigation = setplugin($user_level, 'private');
    
    include dirname(__FILE__) . '/admin-layout.php';
    
    admin_header($currentURL, $breadCrumbs, admin_query());
    
    include dirname(__FILE__) . '/navigation.php';
    
    include dirname(__FILE__) . '/request.php';
    
    admin_footer($currentURL, $ubench);
    
    ob_end_flush();
    
}

