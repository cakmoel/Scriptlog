<?php

ob_start();
/**
 * index.php
 *
 * @category admin/index.php
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0.0
 */

if (file_exists(__DIR__ . '/../config.php') && is_file(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../lib/main.php';

    $ip = (function_exists('get_ip_address')) ? get_ip_address() : "";

    // $app is created inside main.php
    require __DIR__ . '/authenticator.php';

    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        $app->authenticator->logout();
    }

    // Handle admin language switch
    if (isset($_GET['switch-lang']) && !empty($_GET['switch-lang'])) {
        $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($_GET['switch-lang']));
        $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        if (in_array($langCode, $availableLocales)) {
            admin_set_locale($langCode);
        }
        $redirect = $_GET['redirect'] ?? '/admin/index.php?load=dashboard';
        header('Location: ' . $redirect);
        exit();
    }

    if ((isset($app->ubench)) && (true === APP_DEVELOPMENT)) {
        (method_exists($app->ubench, 'start')) ? $app->ubench->start() : "";
    }
} else {
    header("Location: ../install");
    exit();
}

if (!$loggedIn) {
    direct_page('login.php', 302);
} else {
    $decrypt_login = isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $app->cipher_key) : "";

    $user_login = isset($_COOKIE['scriptlog_auth']) ? $decrypt_login : Session::getInstance()->scriptlog_session_login;

    // FIXED: Pass $app->authenticator to the helper function
    $user_data = user_info($app->authenticator, $user_login);

    $user_login = isset($_COOKIE['scriptlog_auth']) ? $user_data['user_login'] : Session::getInstance()->scriptlog_session_login;
    $user_email = isset($_SESSION['scriptlog_session_email']) ? Session::getInstance()->scriptlog_session_email : $user_data['user_email'];
    $user_level = isset($_SESSION['scriptlog_session_level']) ? Session::getInstance()->scriptlog_session_level : $user_data['user_level'];
    $user_id    = isset($_SESSION['scriptlog_session_id']) ? Session::getInstance()->scriptlog_session_id : $user_data['ID'];

    $user_session = isset($user_data['user_session']) ? $user_data['user_session'] : do_logout($app->authenticator);

    // breadcrumb logic
    $breadcrumb = isset($_GET['load']) ? htmlspecialchars(sanitize_urls($_GET['load']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : http_response_code();

    $current_url =  preg_replace("/\/index\.php.*$/i", "", current_load_url());

    include dirname(__FILE__) . DS . 'admin-layout.php';

    admin_header($current_url, $breadcrumb);

    include dirname(__FILE__) . DS . 'navigation.php';

    include dirname(__FILE__) . DS . 'sidebar-nav.php';

    echo sidebar_navigation($breadcrumb, $current_url, $user_id, $user_session);

    require dirname(__FILE__) . DS . 'request.php';

    admin_footer($current_url, $app->ubench);

    ob_end_flush();
}
