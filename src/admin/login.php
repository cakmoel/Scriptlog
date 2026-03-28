<?php

/**
 * login.php - Revamped
 */

// 1. Configuration & Dependency Injection
if (!file_exists(__DIR__ . '/../config.php')) {
    header("Location: ../install");
    exit();
}

require __DIR__ . '/../lib/main.php';

$ip = get_ip_address();

require __DIR__ . '/authenticator.php';

// Start Session if not already started in main.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Auth Check (Redirect if already logged in)
if (isset($loggedIn) && $loggedIn === true) {
    direct_page('index.php?load=dashboard', 200);
    exit();
}

// 3. Layout Resources
include __DIR__ . '/login-layout.php';
// Optimized: Don't use regex if not strictly necessary. 
// Assuming current_load_url gives full path, just strip filename.
$stylePath = dirname(current_load_url());

// 4. Input Handling
$action = $_GET['action'] ?? '';
$loginId = isset($_GET['Id']) ? (int)$_GET['Id'] : 0;
$uniqueKey = $_GET['uniqueKey'] ?? null;
$errors = [];
$failed_login_attempt = 0;

// 5. Form Processing
if ($action === 'LogIn' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate inputs exist before processing
    // NOTE: We do NOT sanitize 'user_pass' here. We pass raw to the processor.
    $login_data = [
        'login'           => filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS),
        'user_pass'       => $_POST['user_pass'] ?? '', // Raw password
        'csrf'            => $_POST['csrf'] ?? '',
        'captcha_login'   => $_POST['captcha_login'] ?? '',
        'scriptpot_name'  => $_POST['scriptpot_name'] ?? '',
        'scriptpot_email' => $_POST['scriptpot_email'] ?? ''
    ];

    if ($login_data['login'] && $login_data['user_pass']) {
        // New call using the secure $app object
        list($errors, $failed_login_attempt) = processing_human_login($app->authenticator, $ip, $loginId, $uniqueKey, $errors, $login_data);
    } else {
        $errors['errorMessage'] = "Please fill in all required fields.";
    }
}

// 6. View Rendering
login_header($stylePath);
?>

<div class="login-logo">
    <h1>
        <a href="#">
            <img class="d-block mx-auto mb-4" src="<?= htmlspecialchars($stylePath); ?>/assets/dist/img/icon612x612.png" alt="scriptlog-logo" width="72" height="72">
        </a>
    </h1>
</div>

<div class="login-box-body">

    <?php if (!empty($errors['errorMessage'])) : ?>
        <div class="alert alert-danger alert-dismissable">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= htmlspecialchars($errors['errorMessage']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status'])): ?>
        <?php
        $status_msg = '';
        if ($_GET['status'] == 'changed') $status_msg = "The password has been changed. Please log in with your new password.";
        if ($_GET['status'] == 'actived') $status_msg = "The account has been activated. Please log in.";

        if ($status_msg):
        ?>
            <div class="alert alert-info alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?= htmlspecialchars($status_msg); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    // Generate Form Action safely
    $formActionParams = ['LogIn', human_login_id(), md5(app_key() . $ip)];
    $actionUrl = form_action('login.php', $formActionParams, 'login')['doLogin'];
    ?>

    <form name="formlogin" action="<?= $actionUrl; ?>" method="post" autocomplete="off">

        <div class="form-group has-feedback">
            <label for="inputLogin">Username or Email Address</label>
            <input type="text" class="form-control" id="inputLogin" name="login" maxlength="186"
                value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login']) : (isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $app->cipher_key) : ""); ?>"
                required autofocus>
            <span class="glyphicon glyphicon-user form-control-feedback"></span>
        </div>

        <div class="form-group has-feedback">
            <label for="inputPassword">Password</label>
            <input type="password" class="form-control" id="inputPassword" name="user_pass" maxlength="50" required>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
        </div>

        <?php if ($failed_login_attempt >= 5) : ?>
            <div class="form-group has-feedback">
                <label for="inputCaptcha">Enter captcha code</label>
                <input type="text" class="form-control" id="inputCaptcha" name="captcha_login" required>
                <span class="glyphicon glyphicon-hand-down form-control-feedback"></span>
                <div class="mt-2">
                    <img src="<?= app_url() . '/admin/captcha-login.php'; ?>" alt="captcha" style="margin-top:5px;">
                </div>
            </div>
        <?php endif; ?>

        <div style="display:none; visibility:hidden;">
            <input type="text" name="scriptpot_name" autocomplete="off">
            <input type="email" name="scriptpot_email" autocomplete="off">
        </div>

        <div class="row">
            <div class="col-xs-8">
                <div class="checkbox icheck">
                    <label for="remember-me">
                        <input type="checkbox" name="remember" id="remember-me" <?= isset($_COOKIE['scriptlog_auth']) ? 'checked' : ''; ?>> Remember Me
                    </label>
                </div>
            </div>
            <div class="col-xs-4">
                <?php $block_csrf = function_exists('generate_form_token') ? generate_form_token('login_form', 40) : ''; ?>
                <input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
                <input type="submit" class="btn btn-primary btn-block btn-flat" name="LogIn" value="Log In">
            </div>
        </div>
    </form>

    <?php if (is_registration_unable() === true): ?>
        <a href="<?= app_url() . '/admin/signup.php'; ?>" class="text-center">Register |</a>
    <?php endif; ?>

    <a href="<?= app_url() . '/admin/reset-password.php'; ?>" class="text-center">Lost your password?</a>
</div>

<?php login_footer($stylePath); ?>