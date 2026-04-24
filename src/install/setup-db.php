<?php

(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

/**
 * File install.php
 *
 * this file will be used when the config.php file  exists and
 * successfully created but database tables have not been installed yet
 *
 * @category Installation file -- install.php
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  0.1
 * @since    Since Release 0.1
 *
 */
require dirname(__FILE__) . '/include/settings.php';
require dirname(__FILE__) . '/include/setup.php';
require dirname(__FILE__) . '/install-layout.php';

if (!file_exists(__DIR__ . '/../config.php')) {
    $installPath = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    if ($installPath === '/') {
        $installPath = '';
    }
    header("Location: " . $protocol . '://' . $server_host . $installPath . '/', true, 302);
    exit();
} else {
    $set_config = require __DIR__ . '/../config.php';

    $dbconnect = make_connection($set_config['db']['host'], $set_config['db']['user'], $set_config['db']['pass'], $set_config['db']['name'], $set_config['db']['port']);

    $prefix = isset($set_config['db']['prefix']) ? $set_config['db']['prefix'] : '';

    // required table
    $required_tables = [
      $prefix . 'tbl_users', $prefix . 'tbl_user_token', $prefix . 'tbl_topics', $prefix . 'tbl_themes',
      $prefix . 'tbl_settings', $prefix . 'tbl_posts', $prefix . 'tbl_post_topic', $prefix . 'tbl_plugin',
      $prefix . 'tbl_menu', $prefix . 'tbl_mediameta', $prefix . 'tbl_media', $prefix . 'tbl_media_download', $prefix . 'tbl_comments'
    ];

    foreach ($required_tables as $table) {
        if (!check_dbtable($dbconnect, $table)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            exit("Database has been installed!");
        }
    }

    $install_path = preg_replace("/\/install\.php.*$/i", "", current_url());

    install_header($install_path, $protocol, $server_host);

    $setup = isset($_POST['setup']) ? stripcslashes($_POST['setup']) : '';

    $completed = false;

    if ($setup === 'install') {
        $username = isset($_POST['user_login']) ? remove_bad_characters($_POST['user_login'], $set_config['db']['host'], $set_config['db']['user'], $set_config['db']['pass'], $set_config['db']['name'], $set_config['db']['port']) : "";
        $password = isset($_POST['user_pass1']) ? escapeHTML($_POST['user_pass1']) : "";
        $confirm = isset($_POST['user_pass2']) ? escapeHTML($_POST['user_pass2']) : "";
        $email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);

        if (strlen($username) < 8 || strlen($username) > 20) {
            $errors['errorInstall'] = 'Username for admin must be between 8 and 20 characters.';
        } elseif (preg_match('/^[0-9]*$/', $username)) {
            $errors['errorInstall'] = 'Username for admin must include at least one letter.';
        } elseif ((!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username))) {
            $errors['errorInstall'] = 'Username for admin only contain alphanumerics characters, underscore and dot. Number of characters must be between 8 to 20';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['errorInstall'] = 'Please enter a valid email address';
        }

        if (empty($password) && (empty($confirm))) {
            $errors['errorInstall'] = 'Admin password should not be empty';
        } elseif ($password != $confirm) {
            $errors['errorInstall'] = 'Admin password should be equal';
        } elseif (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {
            $errors['errorInstall'] = 'Admin password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters';
        }

        if (!is_writable(__DIR__ . '/index.php')) {
            $errors['errorInstall'] = 'Permission denied. Directory installation is not writable';
        }

        if (false === check_php_version()) {
            $errors['errorInstall'] = 'Requires PHP 7.4 or newer';
        }

        if (true === check_pcre_utf8()) {
            $errors['errorInstall'] = 'PCRE has not been compiled with UTF-8 or Unicode property support';
        }

        if (false === check_spl_enabled('spl_autoload_register')) {
            $errors['errorInstall'] = 'spl autoload register is either not loaded or compiled in';
        }

        if (false === check_filter_enabled()) {
            $errors['errorInstall'] = 'The filter extension is either not loaded or compiled in';
        }

        if (false === check_iconv_enabled()) {
            $errors['errorInstall'] = 'The Iconv extension is not loaded';
        }

        if (true === check_character_type()) {
            $errors['errorInstall'] = 'The ctype extension is overloading PHP\'s native string functions';
        }

        if (false === check_gd_enabled()) {
            $errors['errorInstall'] = 'requires GD v2 for the image manipulation';
        }

        if (false === check_pdo_mysql()) {
            $errors['errorInstall'] = 'requires PDO MySQL enabled';
        }

        if (false === check_mysqli_enabled()) {
            $errors['errorInstall'] = 'requires MySQLi enabled';
        }

        if (false === check_uri_determination()) {
            $errors['errorInstall'] = 'Neither $_SERVER[REQUEST_URI], $_SERVER[PHP_SELF] or $_SERVER[PATH_INFO] is available';
        }

        if (false === check_log_dir()) {
            $errors['errorInstall'] = 'requires log directory writeable';
        }

        if (false === check_cache_dir()) {
            $errors['errorInstall'] = 'requires cache directory writeable';
        }

        if (false === check_theme_dir()) {
            $errors['errorInstall'] = 'requires theme directory writeable';
        }

        if (false === check_plugin_dir()) {
            $errors['errorInstall'] = 'requires plugin directory writeable';
        }

        if (empty($errors['errorInstall']) === true) {
            try {
                $completed = true;

                $_SESSION['install'] = true;

                // generate installation key
                $key = installation_key(32);

                $_SESSION['token'] = $key;

                if (check_mysql_version($dbconnect, "5.7")) {
                    // Default site language
                    $site_language = 'en';

                    // Generate encryption key before database installation
                    $defuse_key_path = '';
                    try {
                        $defuse_key_path = generate_defuse_key();
                    } catch (Exception $e) {
                        error_log("Failed to generate defuse key: " . $e->getMessage());
                        $defuse_key_path = dirname(__DIR__, 2) . '/lib/utility/.lts/lts.php';
                    }

                    install_database_table($dbconnect, $protocol, $server_host, $username, $password, $email, $key, $prefix, $site_language, $defuse_key_path);

                    # Generate server config file based on web server
                    $server_config = generate_server_config();
                    $_SESSION['server_config'] = $server_config;

                    $installPath = rtrim(dirname($_SERVER['PHP_SELF']), '/');
                    if ($installPath === '/') {
                        $installPath = '';
                    }
                    header("Location:" . $protocol . "://" . $server_host . $installPath . "/finish.php?status=success&token={$key}");
                }
            } catch (mysqli_sql_exception $e) {
                $errors['errorInstall'] = "Database error: " . $e->getMessage();
            }
        }
    }

    ?>

  <div class="container my-5">

    <div class="text-center mb-5">
      <img class="install-icon" src="assets/img/icon612x612.png" alt="Scriptlog Logo">
      <h1 class="h3 font-weight-bold">Scriptlog Installation</h1>
      <p class="text-muted">Database setup already exists. Let's finish the installation.</p>
    </div>

    <div class="row">
      <div class="col-lg-4 order-lg-2 mb-4">
        <div class="card bg-transparent shadow-none border-0">
            <?= get_sisfo(); ?>
            <?= required_settings(); ?>
            <?php if (strtolower(check_web_server()['WebServer']) != 'nginx') : ?>
                <?= check_mod_rewrite(); ?>
            <?php endif; ?>
            <?= check_dir_file(); ?>
        </div>
      </div>

      <div class="col-lg-8 order-lg-1">
        <?php if (isset($errors['errorInstall']) && (!$completed)) : ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa fa-exclamation-triangle mr-2"></i> <?= $errors['errorInstall']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4">
                <h4 class="mb-0 text-primary"><i class="fa fa-user-circle mr-2"></i> Administrator Account</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-success py-2 small shadow-none">
                    <i class="fa fa-check-circle mr-1"></i> <code>config.php</code> found! Please create your administrator account to complete setup.
                </div>

                <form method="post" action="<?= $install_path . 'setup-db.php'; ?>" class="needs-validation" novalidate>

                  <div class="mb-3">
                    <label for="username" class="font-weight-bold">Username</label>
                    <input type="text" class="form-control" name="user_login" id="username" placeholder="Admin username" value="<?= (isset($_POST['user_login'])) ? escapeHTML($_POST['user_login']) : ""; ?>" required>
                    <div class="invalid-feedback">Your username is required.</div>
                  </div>

                  <div class="row">
                      <div class="col-md-6 mb-3">
                        <label for="pass1" class="font-weight-bold">Password</label>
                        <input type="password" class="form-control" name="user_pass1" id="pass1" placeholder="Admin password" required>
                        <div class="invalid-feedback">Please enter your password.</div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label for="pass2" class="font-weight-bold">Confirm Password</label>
                        <input type="password" class="form-control" name="user_pass2" id="pass2" placeholder="Confirm password" required>
                        <div class="invalid-feedback">Please confirm your password.</div>
                      </div>
                  </div>
                  <p class="small text-muted mb-3">8+ characters, including uppercase, lowercase, numbers, and symbols.</p>

                  <div class="mb-4">
                    <label for="email" class="font-weight-bold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="user_email" placeholder="admin@example.com" value="<?= (isset($_POST['user_email'])) ? escapeHTML($_POST['user_email']) : ""; ?>" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                  </div>

                  <hr class="my-4">
                  <input type="hidden" name="setup" value="install">
                  <button class="btn btn-success btn-lg btn-block shadow-sm" type="submit">
                      <i class="fa fa-rocket mr-2"></i> Complete Installation
                  </button>
                </form>
            </div>
        </div>
      </div>
    </div>
  </div>

    <?php
      install_footer($install_path, $protocol, $server_host);
}
