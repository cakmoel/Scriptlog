<?php
// Clear cache based on PHP version
(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);
/**
 * index.php file
 * 
 * @category  installation the index.php file
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   0.1
 * @since     Since Release 0.1
 * 
 */
// Include necessary files
require dirname(__FILE__) . '/include/settings.php';
require dirname(__FILE__) . '/include/setup.php';
require dirname(__FILE__) . '/install-layout.php';

// Initialize variables
$ca = false; // certificate authority
$current_path = preg_replace("/\/index\.php.*$/i", "", current_url());
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$server_host = $_SERVER['HTTP_HOST'];

// Check if installation is already completed
if (file_exists(__DIR__ . '/../config.php')) {
    $set_config = require __DIR__ . '/../config.php';

    // Validate database connection
    $dbconnect = make_connection($set_config['db']['host'], $set_config['db']['user'], $set_config['db']['pass'], $set_config['db']['name'], $set_config['db']['port']);

    // Check if database tables exist
    $required_tables = [
        'tbl_users', 'tbl_user_token', 'tbl_topics', 'tbl_themes',
        'tbl_settings', 'tbl_posts', 'tbl_post_topic', 'tbl_plugin',
        'tbl_menu', 'tbl_mediameta', 'tbl_media', 'tbl_media_download', 'tbl_comments'
    ];

    $tables_exist = true;
    foreach ($required_tables as $table) {
        if (!check_dbtable($dbconnect, $table)) {
            $tables_exist = false;
            break;
        }
    }

    if ($tables_exist) {
        // Redirect to setup-db.php if tables exist
        $create_db = $protocol . '://' . $server_host . dirname(htmlspecialchars($_SERVER['PHP_SELF'])) . DIRECTORY_SEPARATOR . 'setup-db.php';
        header("Location: $create_db", true, 302);
    } else {
        // Database already installed
        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
        exit("Database has been installed!");
    }
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
$completed = false;
$errors = [];
$installation_path = $protocol . '://' . $server_host . dirname(htmlspecialchars($_SERVER['PHP_SELF'])) . DIRECTORY_SEPARATOR;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
     // Sanitize and validate inputs
     $dbhost = isset($_POST['db_host']) ? escapeHTML($_POST['db_host']) : "";
     $dbname = isset($_POST['db_name']) ? escapeHTML($_POST['db_name']) : "";
     $dbport = isset($_POST['db_port']) ? escapeHTML($_POST['db_port']) : "";
     $dbpass = isset($_POST['db_pass']) ? escapeHTML($_POST['db_pass']) : "";
     $dbuser = isset($_POST['db_user']) ? remove_bad_characters($_POST['db_user'], $dbhost, $_POST['db_user'], $dbpass, $dbname, $dbport) : "";
     $username = isset($_POST['user_login']) ? remove_bad_characters($_POST['user_login'], $dbhost, $dbuser, $dbpass, $dbname, $dbport) : "";
     $password = isset($_POST['user_pass1']) ? escapeHTML($_POST['user_pass1']) : "";
     $confirm = isset($_POST['user_pass2']) ? escapeHTML($_POST['user_pass2']) : "";
     $email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);

     // Validate database credentials
     if (empty($dbhost) || empty($dbname) || empty($dbuser) || empty($dbpass)) {
         $errors['errorSetup'] = "Database: requires name, hostname, user, and password.";
     }

     // Validate port
     if (!ctype_digit(strval($dbport))) {
         $errors['errorSetup'] = "Database port must be a valid integer.";
     }

     // Validate username
     if (strlen($username) < 8 || strlen($username) > 20 || !preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username)) {
         $errors['errorSetup'] = "Username must be 8-20 characters long and contain only alphanumerics, underscores, and dots.";
     }

     // Validate email
     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $errors['errorSetup'] = "Please enter a valid email address.";
     }

     // Validate password
     if (empty($password) || $password !== $confirm || !preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {
         $errors['errorSetup'] = "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.";
     }

     // Check PHP extensions and server requirements
     if (!check_php_version() || !check_pdo_mysql() || !check_mysqli_enabled() || !check_gd_enabled() || !check_fileinfo_enabled()) {
         $errors['errorSetup'] = "Server does not meet the minimum requirements.";
     }

     // If no errors, proceed with installation
     if (empty($errors)) {
         try {
             $completed = true;

             $_SESSION['install'] = true;

             // Generate a secure key
             $key = installation_key(32);
             
             $_SESSION['token'] = $key;

             // Create database connection
             $link = make_connection($dbhost, $dbuser, $dbpass, $dbname, $dbport);

             // Install database tables
             if (check_mysql_version($link, "5.7")) {
                 install_database_table($link, $protocol, $server_host, $username, $password, $email, $key);

                 // Write configuration file
                 if (write_config_file($protocol, $server_host, $dbhost, $dbuser, $dbpass, $dbname, $dbport, $email, $key, $ca)) {
                     // Redirect to finish page
                     header("Location:" . $protocol . "://" . $server_host . dirname(htmlspecialchars($_SERVER['PHP_SELF'])) . DIRECTORY_SEPARATOR . "finish.php?status=success&token={$key}", true, 302);
                 }
             }
         } catch (mysqli_sql_exception $e) {
             $errors['errorSetup'] = "Database error: " . $e->getMessage();
         }
     }
}

// Display installation form
install_header($current_path);
?>

<div class="container">
    <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="<?= $current_path; ?>assets/img/icon612x612.png" alt="Scriptlog Installation Procedure" width="72" height="72">
        <h2>Scriptlog</h2>
        <p class="lead">Installation procedure</p>
    </div>

    <div class="row">
        <div class="col-md-4 order-md-2 mb-4">
            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Getting System Info</span>
            </h4>
            <?= get_sisfo(); ?>

            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Required PHP Settings</span>
            </h4>
            <?= required_settings(); ?>

            <?php if (strtolower(check_web_server()['WebServer']) == 'nginx') : ?>
                <h4 class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Directories and Files</span>
                </h4>
                <?= check_dir_file(); ?>
            <?php else : ?>
                <?= check_mod_rewrite(); ?>
                <h4 class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Directories and Files</span>
                </h4>
                <?= check_dir_file(); ?>
            <?php endif; ?>
        </div>

        <div class="col-md-8 order-md-1">
            <?php if (isset($errors['errorSetup']) && !$completed) : ?>
                <div class="alert alert-danger" role="alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $errors['errorSetup']; ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-success" role="alert">
                We are going to use this information to create a config.php file.
                You should enter your database connection details and administrator account.
            </div>

            <form method="post" action="<?= $installation_path; ?>" class="needs-validation" novalidate>
                
                <h4 class="mb-3">Database Settings</h4>
                <div class="mb-3">
                    <label for="databaseHost">Host</label>
                    <input type="text" class="form-control" id="databaseHost" name="db_host" placeholder="database host" value="<?= htmlspecialchars($dbhost ?? ''); ?>" required>
                    <div class="invalid-feedback">Valid database host is required.</div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="databaseName">Database</label>
                        <input type="text" class="form-control" id="databaseName" name="db_name" placeholder="database name" value="<?= htmlspecialchars($dbname ?? ''); ?>" required>
                        <div class="invalid-feedback">Valid database name is required.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="databaseUser">User</label>
                        <input type="text" class="form-control" id="databaseUser" name="db_user" placeholder="database user" value="<?= htmlspecialchars($dbuser ?? ''); ?>" required>
                        <div class="invalid-feedback">Valid database user is required.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="databasePort">Port</label>
                        <input type="text" class="form-control" id="databasePort" name="db_port" placeholder="3306" value="<?= htmlspecialchars($dbport ?? '3306'); ?>" required>
                        <div class="invalid-feedback">Valid database port is required.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="databasePass">Password</label>
                    <input type="password" class="form-control" id="databasePass" name="db_pass" placeholder="database password" required>
                    <div class="invalid-feedback">Valid database password is required.</div>
                </div>

                <hr class="mb-4">

                <h4 class="mb-3">Administrator Account</h4>
                <div class="mb-3">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="user_login" id="username" placeholder="username for administrator" value="<?= htmlspecialchars($username ?? ''); ?>" required>
                    <div class="invalid-feedback">Your username is required.</div>
                </div>
                <div class="mb-3">
                    <label for="pass">Password</label>
                    <input type="password" class="form-control" name="user_pass1" id="pass1" placeholder="password for administrator" required>
                    <div class="invalid-feedback">Please enter your password.</div>
                    <span class="text-muted">8+ characters, an uppercase and a lowercase letter, numbers and any symbols</span>
                </div>
                <div class="mb-3">
                    <label for="pass2">Confirm password</label>
                    <input type="password" class="form-control" name="user_pass2" id="pass2" placeholder="Confirm password for administrator" required>
                    <div class="invalid-feedback">Please confirm your password.</div>
                </div>
                <div class="mb-3">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="user_email" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? ''); ?>" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                    <span class="text-muted">E-mail address for administrator</span>
                </div>

                <hr class="mb-4">
                <input type="hidden" name="setup" value="install">
                <button class="btn btn-success btn-lg btn-block" type="submit">Install</button>
            </form>
        </div>
    </div>
</div>

<?php
install_footer($current_path, $protocol, $server_host);
ob_end_flush();