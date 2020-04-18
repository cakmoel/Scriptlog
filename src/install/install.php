<?php
/**
 * File install.php
 * this file will be used when file config.php exists and  
 * successfully created but database tables has not been installed yet
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

use Sinergi\BrowserDetector\Browser;

if (!file_exists(__DIR__ . '/../config.php')) {

    header("Location: ".$protocol . '://' . $server_host . dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR);
    exit();

} else {

$set_config = require __DIR__ . '/../config.php';

$link = make_connection($set_config['db']['host'], 
            $set_config['db']['user'], 
            $set_config['db']['pass'], 
            $set_config['db']['name']);

if((check_dbtable($link, 'tbl_users') == false) || (check_dbtable($link, 'tbl_user_token') == false)
   || (check_dbtable($link, 'tbl_topics') == false) || (check_dbtable($link, 'tbl_themes') == false) 
   || (check_dbtable($link, 'tbl_ settings') == false) || (check_dbtable($link, 'tbl_posts') == false)
   || (check_dbtable($link, 'tbl_post_topic') == false) || (check_dbtable($link, 'tbl_plugin') == false)
   || (check_dbtable($link, 'tbl_menu_child') == false) || (check_dbtable($link, 'tbl_menu') == false)
   || (check_dbtable($link, 'tbl_mediameta') == false) || (check_dbtable($link, 'tbl_media') == false)
   || (check_dbtable($link, 'tbl_comments') == false) || (check_dbtable($link, 'tbl_comment_reply') == false)) {

  header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
  exit("Database has been installed");

}

$install_path = preg_replace("/\/install\.php.*$/i", "", current_url());

install_header($install_path, $protocol, $server_host);

$setup = isset($_POST['setup']) ? stripcslashes($_POST['setup']) : '';

if($setup != 'install') {

    if (version_compare(PHP_VERSION, '5.6', '>=')) {
        
        clearstatcache();
        
    } else {
        
        clearstatcache(true);
        
    }
    
    $_SESSION['install'] = false;
    
    header($install_path);

} else {

    $username = isset($_POST['user_login']) ? remove_bad_characters($_POST['user_login']) : "";
    $password = isset($_POST['user_pass1']) ? $_POST['user_pass1'] : "";
    $confirm = isset($_POST['user_pass2']) ? $_POST['user_pass2'] : "";
    $email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);

    if (strlen($username) < 8) {

      $errors['errorSetup'] = 'Admin username must be at least 8 characters.';

    } 
    
    if (strlen($username) > 20) {

      $errors['errorSetup'] = 'Admin username may not be longer than 20 characters.';

    } 
    
    if (preg_match('/^[0-9]*$/', $username)) {

     $errors['errorSetup'] = 'Sorry, admin username must have letters too!';
   
    } 
   
    if ((!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $username))) {

      $errors['erroSetup'] = 'Admin username can only contain Admin username can only contain alphanumerics characters, underscore and dot. Number of characters must be between 8 to 20';
      
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['errorInstall'] = 'Please enter a valid email address';

    }

    if(empty($password) && (empty($confirm))) {

        $errors['errorInstall'] = 'Admin password should not be empty';

    } elseif($password != $confirm) {

        $errors['errorInstall'] = 'Admin password should be equal';

    } elseif(!preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{8,50}$/', $password)) {

        $errors['errorInstall'] = 'Admin password may contain letter and numbers, at least one number and one letter, any of these characters !@#$%';

    }

    if(!is_writable(__DIR__ . '/index.php')) {

        $errors['errorInstall'] = 'Permission denied. Directory installation is not writable';

    }

    if(empty($errors['errorInstall']) == true) {

        try {

          $completed = true;

          $length = 32;

          $_SESSION['install'] = true;

          if(function_exists("random_bytes")) {

            $token = random_bytes(ceil($length / 2));

          } elseif(function_exists("openssl_random_pseudo_bytes")) {

            $token = openssl_random_pseudo_bytes(ceil($length/2));

          } else {

            trigger_error("No cryptographically secure random function available", E_USER_ERROR);
          
          }

           $key = bin2hex($token);

           $_SESSION['token'] = $key;

          if(check_mysql_version($link, "5.6")) {

             install_database_table($link, $protocol, $server_host, $username, $password, $email, $key);

             header("Location:".$protocol."://".$server_host.dirname($_SERVER['PHP_SELF']).DIRECTORY_SEPARATOR."finish.php?status=success&token={$key}");

          }

        } catch(mysqli_sql_exception $e) {

          throw $e;

        }

    }
    
}

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

<?= check_mod_rewrite(); ?>

<h4 class="d-flex justify-content-between align-items-center mb-3">
<span class="text-muted">Directories and Files</span>
</h4>

<?= check_dir_file(); ?>

</div>
  
  <div class="col-md-8 order-md-1">
  <?php 
  if (count($errors) && !empty($errors['errorChecking']) === true) :
  ?>
   <div class="alert alert-danger" role="alert">
    Scriptlog may not work correctly with your environment
  </div>
  <?php 
  elseif (isset($errors['errorSetup']) && (!$completed)) :
  ?>
   <div class="alert alert-danger" role="alert">
  <?= $errors['errorSetup']; ?>
   </div>
  <?php 
  else:
  ?>
    <div class="alert alert-success" role="alert">
      We are going to use this information to setup database table. 
      You should enter your administrator account details. 
    </div>
    
    <form method="post" action="<?= $install_path.'install.php'; ?>" class="needs-validation" novalidate>
               
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
        <input type="password" class="form-control" name="user_pass1" id="pass1" placeholder="Enter your password" required>
        <div class="invalid-feedback">
          Please enter your password.
        </div>
      </div>
       <div class="mb-3">
        <label for="pass2">Confirm password</label>
        <input type="password" class="form-control" name="user_pass2" id="pass2" placeholder="Confirm your password" required>
        <div class="invalid-feedback">
          Please confirm your password.
        </div>
      </div>
       <div class="mb-3">
        <label for="email">Email <span class="text-muted">(Administrator's E-mail)</span></label>
        <input type="email" class="form-control" id="email" name="user_email" placeholder="you@example.com" value="<?=(isset($_POST['user_email'])) ? escapeHTML($_POST['user_email']) : ""; ?>" required>
        <div class="invalid-feedback">
          Please enter a valid email address.
        </div>
      </div>
       <div class="row"></div>
      <hr class="mb-4">

<input type="hidden" name="setup" value="install">
<button class="btn btn-success btn-lg btn-block" type="submit">Install</button>
</form>

<?php 
endif;
?>
  
</div>

</div>

<?php

install_footer($install_path, $protocol, $server_host);

}