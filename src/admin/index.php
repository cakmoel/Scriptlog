<?php
ob_start();
/**
 * index.php
 * 
 * @category admin/index.php file
 * @author   M.Noermoehammad 
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version 1.0
 * 
 */

if (file_exists(__DIR__ . '/../config.php') && is_file(__DIR__ . '/../config.php')) {

    require __DIR__ . '/../lib/main.php';

    $ip = (function_exists('get_ip_address')) ? get_ip_address() : "";

    require __DIR__ . '/authenticator.php';

    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        $authenticator->logout(); // Contains setcookie() and direct_page() (header operations)
        // Execution must stop here via the redirect inside logout()
    }

    if ((isset($ubench)) && (true === APP_DEVELOPMENT)) {

        (method_exists($ubench, 'start')) ? $ubench->start() : "";
    }
    
} else {

    header("Location: ../install");
    exit();
}

if (!$loggedIn) {

    direct_page('login.php', 302);
} else {

    // 1. Decrypt the login token (Still relies on the fix to ScriptlogCryptonize.php)
    $decrypt_login = isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipher_key) : "";
    
    // Determine the user's login name, prioritizing the session data if available
    $user_login = isset($_COOKIE['scriptlog_auth']) ? $decrypt_login : Session::getInstance()->scriptlog_session_login;

    // 2. Fetch user data from the database
    $user_data = user_info($authenticator, $user_login);

    // 3. Populate variables, falling back to the single $user_data array
    $user_login = isset($_COOKIE['scriptlog_auth']) ? $user_data['user_login'] : Session::getInstance()->scriptlog_session_login;
    $user_email = isset($_SESSION['scriptlog_session_email']) ? Session::getInstance()->scriptlog_session_email : $user_data['user_email'];
    $user_level = isset($_SESSION['scriptlog_session_level']) ? Session::getInstance()->scriptlog_session_level : $user_data['user_level'];
    $user_id    = isset($_SESSION['scriptlog_session_id']) ? Session::getInstance()->scriptlog_session_id : $user_data['ID'];
    $user_session = isset($user_data['user_session']) ? $user_data['user_session'] : do_logout($authenticator);

    // module accessed or path to link accessed by request
    $breadcrumb = isset($_GET['load']) ? htmlspecialchars(sanitize_urls($_GET['load']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : http_response_code();

    // Current URL
    $current_url =  preg_replace("/\/index\.php.*$/i", "", current_load_url());

    include dirname(__FILE__) . DS . 'admin-layout.php';

    admin_header($current_url, $breadcrumb);

    include dirname(__FILE__) . DS . 'navigation.php';

    include dirname(__FILE__) . DS . 'sidebar-nav.php';

    echo sidebar_navigation($breadcrumb, $current_url, $user_id, $user_session);

    require dirname(__FILE__) . DS . 'request.php';

    admin_footer($current_url, $ubench);

    ob_end_flush();
}
