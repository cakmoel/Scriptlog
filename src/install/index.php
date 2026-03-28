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

    $prefix = isset($set_config['db']['prefix']) ? $set_config['db']['prefix'] : '';

    // Check if database tables exist
    $required_tables = [
        $prefix . 'tbl_users', $prefix . 'tbl_user_token', $prefix . 'tbl_topics', $prefix . 'tbl_themes',
        $prefix . 'tbl_settings', $prefix . 'tbl_posts', $prefix . 'tbl_post_topic', $prefix . 'tbl_plugin',
        $prefix . 'tbl_menu', $prefix . 'tbl_mediameta', $prefix . 'tbl_media', $prefix . 'tbl_media_download', $prefix . 'tbl_comments'
    ];

    $tables_exist = true;
    foreach ($required_tables as $table) {
        if (!check_dbtable($dbconnect, $table)) {
            $tables_exist = false;
            break;
        }
    }

    if ($tables_exist) {
        // Redirect to setup-db.php
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

// Generate random table prefix for security
$generated_prefix = generate_table_prefix(6);

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
              
              // Use user-provided prefix or generate random one
              $table_prefix = !empty($_POST['tbl_prefix']) ? trim($_POST['tbl_prefix']) : $generated_prefix;
              
              // Validate prefix format (alphanumeric with underscore, ending with underscore)
              if (!preg_match('/^[a-zA-Z0-9]+_$/', $table_prefix)) {
                  $table_prefix = $generated_prefix;
              }
              
              $_SESSION['token'] = $key;

              // Create database connection
              $link = make_connection($dbhost, $dbuser, $dbpass, $dbname, $dbport);

              // Install database tables
              if (check_mysql_version($link, "5.7")) {
                  install_database_table($link, $protocol, $server_host, $username, $password, $email, $key, $table_prefix);

                  // Write configuration file
                  if (write_config_file($protocol, $server_host, $dbhost, $dbuser, $dbpass, $dbname, $dbport, $email, $key, $ca, $table_prefix)) {
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

<div class="container my-5">
    <div class="text-center mb-5">
        <img class="install-icon" src="<?= $current_path; ?>assets/img/icon612x612.png" alt="Scriptlog Logo">
        <h1 class="h3 font-weight-bold">Scriptlog Installation</h1>
        <p class="text-muted">Setting up your personal blogware</p>
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
            <?php if (isset($errors['errorSetup']) && !$completed) : ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa fa-exclamation-triangle mr-2"></i> <?= $errors['errorSetup']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <h4 class="mb-0 text-muted"><i class="fa fa-database mr-2"></i> Database Settings</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small shadow-none">
                        <i class="fa fa-info-circle mr-1"></i> We'll use this info to create your <code>config.php</code> file.
                    </div>

                    <form method="post" action="<?= $installation_path; ?>" class="needs-validation" novalidate>
                        
                        <div class="mb-3">
                            <label for="databaseHost" class="font-weight-bold">Host</label>
                            <input type="text" class="form-control" id="databaseHost" name="db_host" placeholder="localhost" value="<?= htmlspecialchars($dbhost ?? 'localhost'); ?>" required>
                            <div class="invalid-feedback">Valid database host is required.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="databaseName" class="font-weight-bold">Database Name</label>
                                <input type="text" class="form-control" id="databaseName" name="db_name" placeholder="scriptlog_db" value="<?= htmlspecialchars($dbname ?? ''); ?>" required>
                                <div class="invalid-feedback">Valid database name is required.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="databaseUser" class="font-weight-bold">User</label>
                                <input type="text" class="form-control" id="databaseUser" name="db_user" placeholder="db_user" value="<?= htmlspecialchars($dbuser ?? ''); ?>" required>
                                <div class="invalid-feedback">Valid database user is required.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="databasePort" class="font-weight-bold">Port</label>
                                <input type="text" class="form-control" id="databasePort" name="db_port" placeholder="3306" value="<?= htmlspecialchars($dbport ?? '3306'); ?>" required>
                                <div class="invalid-feedback">Valid port is required.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="databasePass" class="font-weight-bold">Password</label>
                            <input type="password" class="form-control" id="databasePass" name="db_pass" placeholder="Database password" required>
                            <div class="invalid-feedback">Database password is required.</div>
                        </div>

                        <div class="mb-4">
                            <label for="tablePrefix" class="font-weight-bold">Table Prefix <span class="text-muted font-weight-normal">(Optional)</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="tablePrefix" name="tbl_prefix" placeholder="<?= htmlspecialchars($generated_prefix); ?>" value="<?= htmlspecialchars($generated_prefix); ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tablePrefix').value = '<?= htmlspecialchars($generated_prefix); ?>'">Reset</button>
                                </div>
                            </div>
                            <small class="form-text text-muted mt-2">Tables will be created with this prefix for security.</small>
                        </div>

                        <hr class="my-4">

                        <h4 class="mb-4 text-muted"><i class="fa fa-user-circle mr-2"></i> Administrator Account</h4>
                        
                        <div class="mb-3">
                            <label for="username" class="font-weight-bold">Username</label>
                            <input type="text" class="form-control" name="user_login" id="username" placeholder="Admin username" value="<?= htmlspecialchars($username ?? ''); ?>" required>
                            <div class="invalid-feedback">Username is required.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pass1" class="font-weight-bold">Password</label>
                                <input type="password" class="form-control" name="user_pass1" id="pass1" placeholder="Admin password" required>
                                <div class="invalid-feedback">Password is required.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pass2" class="font-weight-bold">Confirm Password</label>
                                <input type="password" class="form-control" name="user_pass2" id="pass2" placeholder="Confirm password" required>
                                <div class="invalid-feedback">Please confirm password.</div>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">8+ characters, including uppercase, lowercase, numbers, and symbols.</p>

                        <div class="mb-4">
                            <label for="email" class="font-weight-bold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="user_email" placeholder="admin@example.com" value="<?= htmlspecialchars($email ?? ''); ?>" required>
                            <div class="invalid-feedback">Valid email address is required.</div>
                        </div>

                        <hr class="my-4">
                        <input type="hidden" name="setup" value="install">
                        <button class="btn btn-success btn-lg btn-block shadow-sm" type="submit">
                            <i class="fa fa-rocket mr-2"></i> Install
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
install_footer($current_path);
ob_end_flush();
