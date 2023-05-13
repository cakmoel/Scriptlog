<?php 
/**
 * index.php
 * 
 * @category admin/index.php file
 * @author   M.Noermoehammad 
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */
if (file_exists(__DIR__.'/../config.php') && is_file(__DIR__.'/../config.php')) {
    
    include __DIR__ . '/../lib/main.php';

    $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : get_ip_address();

    include __DIR__ . '/authenticator.php';

    if ((isset($ubench)) && (true === APP_DEVELOPMENT)) {
        
        $ubench->start();

    }

} else {

    header("Location: ../install");
    exit();
       
}

if (!$loggedIn) {
   
    direct_page('login.php', 302);
   
} else {

$decrypt_login = isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipher_key) : "";
    
$user_login = isset($_COOKIE['scriptlog_auth']) ? user_info($authenticator, $decrypt_login)['user_login'] : Session::getInstance()->scriptlog_session_login;
$user_email = isset($_SESSION['scriptlog_session_email']) ? Session::getInstance()->scriptlog_session_email : user_info($authenticator, $user_login)['user_email'];
$user_level = isset($_SESSION['scriptlog_session_level']) ? Session::getInstance()->scriptlog_session_level : user_info($authenticator, $user_login)['user_level'];
$user_id = isset($_SESSION['scriptlog_session_id']) ? Session::getInstance()->scriptlog_session_id : user_info($authenticator, $user_login)['ID'];
$user_session = isset(user_info($authenticator, $user_login)['user_session']) ? user_info($authenticator, $user_login)['user_session'] : do_logout($authenticator);

// module accessed or path to link accessed by request
$breadcrumb = isset($_GET['load']) ? htmlspecialchars(sanitize_urls($_GET['load']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : http_response_code();

// Current URL
$current_url =  preg_replace("/\/index\.php.*$/i", "", app_url().DS.APP_ADMIN);

include dirname(__FILE__) . DS .'admin-layout.php';

admin_header($current_url, $breadcrumb);

include dirname(__FILE__) . DS .'navigation.php';
 
include dirname(__FILE__) . DS . 'sidebar-nav.php';

echo sidebar_navigation($breadcrumb, $current_url, $user_id, $user_session);

include dirname(__FILE__) . DS .'request.php';

admin_footer($current_url, $ubench);

ob_end_flush();

}