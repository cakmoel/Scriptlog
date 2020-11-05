<?php 
/**
 * File index.php 
 * 
 * @category  installation file -- index.php
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   0.1
 * @since     Since Release 0.1
 * 
 */

require dirname(__FILE__) . '/include/settings.php';
require dirname(__FILE__) . '/include/setup.php';
require dirname(__FILE__) . '/install-layout.php';

use Sinergi\BrowserDetector\Browser;

if (file_exists(__DIR__ . '/../config.php')) {

  $set_config = require __DIR__ . '/../config.php';

  $dbconnect = make_connection($set_config['db']['host'], $set_config['db']['user'], $set_config['db']['pass'], $set_config['db']['name']);
  
// check if database table exists or not
if((check_dbtable($dbconnect, 'tbl_users') == true) || (check_dbtable($dbconnect, 'tbl_user_token') == true)
|| (check_dbtable($dbconnect, 'tbl_topics') == true) || (check_dbtable($dbconnect, 'tbl_themes') == true)
|| (check_dbtable($dbconnect, 'tbl_settings') == true) || (check_dbtable($dbconnect, 'tbl_posts') == true)
|| (check_dbtable($dbconnect, 'tbl_post_topic') == true) || (check_dbtable($dbconnect, 'tbl_plugin') == true)
|| (check_dbtable($dbconnect, 'tbl_menu') == true) || (check_dbtable($dbconnect, 'tbl_mediameta') == true) 
|| (check_dbtable($dbconnect, 'tbl_media') == true) || (check_dbtable($dbconnect, 'tbl_media_download') == true) 
|| (check_dbtable($dbconnect, 'tbl_comments') == true) || (check_dbtable($dbconnect, 'tbl_comment_reply') == true)) {

  $create_db = $protocol . '://' . $server_host . dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR .'install.php';

  header("Location: $create_db");

} else {

   header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
   exit("Database has been installed!");

}

} else {

  $current_path = preg_replace("/\/index\.php.*$/i", "", current_url());

  $installation_path = $protocol . '://' . $server_host . dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR;

  $completed = false;

  $install = isset($_POST['setup']) ? stripcslashes($_POST['setup']) : '';

if ($install != 'install') {
    
    if (version_compare(PHP_VERSION, '5.6', '>=')) {

        clearstatcache();

    } else {

        clearstatcache(true);
        
    }
    
    $_SESSION['install'] = false;

    header($installation_path);
    
  } else {
    
    $dbhost = isset($_POST['db_host']) ? escapeHTML($_POST['db_host']) : "";
    $dbname = filter_input(INPUT_POST, 'db_name', FILTER_SANITIZE_STRING);
    $dbuser = isset($_POST['db_user']) ? remove_bad_characters($_POST['db_user']) : "";
    $dbpass = isset($_POST['db_pass']) ? escapeHTML($_POST['db_pass']) : "";
    
    $username = isset($_POST['user_login']) ? remove_bad_characters($_POST['user_login']) : "";
    $password = isset($_POST['user_pass1']) ? escapeHTML($_POST['user_pass1']) : "";
    $confirm = isset($_POST['user_pass2']) ? escapeHTML($_POST['user_pass2']) : "";
    $email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);
    
    if ($dbhost == '' || $dbname == '' || $dbuser == '' || $dbpass == '') {
        
        $errors['errorSetup'] = "database: requires name, hostname, user and password";
        
    } 

    $link = make_connection($dbhost, $dbuser, $dbpass, $dbname);
    
    if (strlen($username) < 8) {

       $errors['errorSetup'] = 'username for admin must be at least 8 characters.';

    } 
    
    if (strlen($username) > 20) {

       $errors['errorSetup'] = 'username for admin may not be longer than 20 characters.';

    } 
    
    if (preg_match('/^[0-9]*$/', $username)) {

      $errors['errorSetup'] = 'Sorry, username for admin must have letters too!';
    
    } 
    
    if ((!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username))) {

      $errors['errorSetup'] = 'username for admin requires only alphanumerics characters, underscore and dot. Number of characters must be between 8 to 20';
      
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        
        $errors['errorSetup'] = 'Please enter a valid email address';
        
    } 
    
    if (empty($password) && (empty($confirm))) { 

        $errors['errorSetup'] = 'Admin password should not be empty';

    } elseif ($password != $confirm) {

        $errors['errorSetup'] = 'Admin password should both be equal';

    } elseif (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[\W])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password)) {

        $errors['errorSetup'] = 'Admin password requires at least 8 characters, uppercase and lowercase letters, numbers and special characters';

    }
    
    if (!is_writable(__DIR__ . '/index.php')) {
       
       $errors['errorSetup'] = 'Permission denied. Directory installation is not writable';
       
    }

    if (false === check_php_version()) {

       $errors['errorSetup'] = 'Requires PHP 5.6 or newer';

    }

    if (true === check_pcre_utf8()) {

       $errors['errorSetup'] = 'PCRE has not been compiled with UTF-8 or Unicode property support';

    }

    if (false === check_spl_enabled('spl_autoload_register')) {

        $errors['errorSetup'] = 'spl autoload register is either not loaded or compiled in';

    }

    if (false === check_filter_enabled()) {

       $errors['errorSetup'] = 'The filter extension is either not loaded or compiled in';

    }

    if (false === check_iconv_enabled()) {

       $errors['errorSetup'] = 'The Iconv extension is not loaded';

    }

    if (true === check_character_type()) {

       $errors['errorSetup'] = 'The ctype extension is overloading PHP\'s native string functions';

    } 

    if (false === check_gd_enabled()) {
       
       $errors['errorSetup'] = 'requires GD v2 for the image manipulation';

    }

    if (false === check_pdo_mysql()) {

       $errors['errorSetup'] = 'requires PDO MySQL enabled';

    }

    if (false === check_mysqli_enabled()) {

       $errors['errorSetup'] = 'requires MySQLi enabled';
       
    }

    if (false === check_uri_determination()) {

      $errors['errorSetup'] = 'Neither $_SERVER[REQUEST_URI], $_SERVER[PHP_SELF] or $_SERVER[PATH_INFO] is available';

    }

    if (false === check_log_dir()) {

      $errors['errorSetup'] = 'requires log directory writeable';

    }

    if (false === check_cache_dir()) {

      $errors['errorSetup'] = 'requires cache directory writeable';

    }

    if (false === check_theme_dir()) {

      $errors['errorSetup'] = 'requires theme directory writeable';

    }

    if (false === check_plugin_dir()) {

      $errors['errorSetup'] = 'requires plugin directory writeable';
      
    }

    if (empty($errors['errorSetup']) === true) {
        
      try {

          $completed = true;
        
          $length = 32;

          $_SESSION['install'] = true;
        
          if (function_exists("random_bytes")) {
       
            $token = random_bytes(ceil($length / 2));
          
          } elseif (function_exists("openssl_random_pseudo_bytes")) {
          
            $token = openssl_random_pseudo_bytes(ceil($length / 2));
          
          } else {
          
            trigger_error("No cryptographically secure random function available", E_USER_ERROR);
          
          }
              
            $key = bin2hex($token);
        
            $_SESSION['token'] = $key;
        
           if (check_mysql_version($link, "5.6")) {

              install_database_table($link, $protocol, $server_host, $username, $password, $email, $key);

              if (true === write_config_file($protocol, $server_host, $dbhost, $dbuser, $dbpass, $dbname, $email, $key)) {

                header("Location:".$protocol."://".$server_host.dirname($_SERVER['PHP_SELF']).DIRECTORY_SEPARATOR."finish.php?status=success&token={$key}");

            }       
        
          }

        } catch(mysqli_sql_exception $e) {

           throw $e;
           
        }
        
    }
    
}

install_header($current_path, $protocol, $server_host);

?>

<div class="container">

      <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="assets/img/icon612x612.png" alt="Scriptlog Installation Procedure" width="72" height="72">
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

    <?php if(check_web_server()['WebServer'] == 'nginx') : ?>
      
      <h4 class="d-flex justify-content-between align-items-center mb-3">
      <span class="text-muted">Directories and Files</span>
      </h4>
    
    <?= check_dir_file(); ?>

    <?php else: ?>

    <?= check_mod_rewrite(); ?>
    
    <h4 class="d-flex justify-content-between align-items-center mb-3">
      <span class="text-muted">Directories and Files</span>
    </h4>
    
    <?= check_dir_file(); ?>

    <?php endif; ?>

</div>
        
        <div class="col-md-8 order-md-1">
        <?php 
        if (isset($errors['errorSetup']) && (!$completed)):
        ?>
         <div class="alert alert-danger"  role="alert">
         <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;
         </button>
         <?= $errors['errorSetup']; ?>
        </div>
        <?php 
          endif;
        ?>
          <div class="alert alert-success" role="alert">
            We are going to use this information to create a config.php file. 
            You should enter your database connection details and administrator account. 
          </div>
          
          <form method="post" action="<?= $installation_path; ?>" class="needs-validation" novalidate>
          
            <h4 class="mb-3">Database Settings</h4>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="databaseHost">Database Host</label>
                <input type="text" class="form-control" id="databaseHost" name="db_host" placeholder="Database host" value="<?=(isset($_POST['db_host'])) ? escapeHTML($_POST['db_host'])  : ""; ?>"  required>
                <div class="invalid-feedback">
                  Valid database host is required.
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="lastName">Database Name</label>
                <input type="text" class="form-control" id="databaseName" name="db_name" placeholder="Database name" value="<?=(isset($_POST['db_name'])) ? escapeHTML($_POST['db_name']) : "";  ?>" required>
                <div class="invalid-feedback">
                  Valid database name is required.
                </div>
              </div>
            </div>

            <div class="row">
            <div class="col-md-6 mb-3">
                <label for="databaseUser">MySQL Username</label>
                <input type="text" class="form-control" id="databaseUser" name="db_user" placeholder="Database user" value="<?=(isset($_POST['db_user'])) ? escapeHTML($_POST['db_user']) : ""; ?>" required>
                <div class="invalid-feedback">
                  Valid database username is required.
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="databasePass">MySQL Password</label>
                <input type="password" class="form-control" id="databasePass" name="db_pass" placeholder="Database password" required>
                <div class="invalid-feedback">
                  Valid database password is required.
                </div>
              </div>
            </div>
            
            <hr class="mb-4">
                       
            <h4 class="mb-3">Administrator Account</h4>

            <div class="mb-3">
              <label for="username">Username</label>
              <input type="text" class="form-control" name="user_login" id="username" placeholder="username for administrator" value="<?=(isset($_POST['user_login'])) ? escapeHTML($_POST['user_login']) : ""; ?>" required>
              <div class="invalid-feedback">
                Your username is required.
              </div>
            </div>
             <div class="mb-3">
              <label for="pass">Password</label>
              <input type="password" class="form-control" name="user_pass1" id="pass1" placeholder="password for administrator" required>
              <div class="invalid-feedback">
                Please enter your password.
              </div>
              <span class="text-muted">8+ characters, an uppercase and a lowercase letter, numbers and any symbols</span>
            </div>
             <div class="mb-3">
              <label for="pass2">Confirm password</label>
              <input type="password" class="form-control" name="user_pass2" id="pass2" placeholder="Confirm password for administrator" required>
              <div class="invalid-feedback">
                Please confirm your password.
              </div>
            </div>
             <div class="mb-3">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" name="user_email" placeholder="you@example.com" value="<?=(isset($_POST['user_email'])) ? escapeHTML($_POST['user_email']) : ""; ?>" required>
              <div class="invalid-feedback">
                Please enter a valid email address.
              </div>
              <span class="text-muted">E-mail address for administrator</span>
            </div>
             <div class="row"></div>
            <hr class="mb-4">
   
     <input type="hidden" name="setup" value="install">
     <button class="btn btn-success btn-lg btn-block" type="submit">Install</button>
    </form>
    
  </div>

  </div>

<?php

install_footer($current_path, $protocol, $server_host);

ob_end_flush();

} 