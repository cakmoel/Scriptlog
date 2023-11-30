<?php
/**
 * login.php
 * 
 * login functionality 
 * to access control panel or administrator page
 * 
 * @category login.php file
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
if (file_exists(__DIR__ . '/../config.php')) {
   
  include __DIR__ . '/../lib/main.php';
  
  $ip = get_ip_address();
  
  require __DIR__ . '/authenticator.php';
  include __DIR__ . '/login-layout.php';

  $stylePath =  preg_replace("/\/login\.php.*$/i", "", current_load_url());
 
} else {

  header("Location: ../install");
  exit();
  
}

if ($loggedIn === true) {

  direct_page('index.php?load=dashboard', 200);
   
}

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$loginId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$uniqueKey = isset($_GET['uniqueKey']) ? safe_html($_GET['uniqueKey']) : null;

if (($action == 'LogIn') && (block_request_type(current_request_method(), ['POST']) === false)) {

  list($errors, $failed_login_attempt) = processing_human_login($authenticator, $ip, $loginId, $uniqueKey, $errors, $_POST);
   
}

login_header($stylePath);

?>

<div class="login-logo">
  <h1>
  <a href="#">
    <img class="d-block mx-auto mb-4" src="<?=$stylePath; ?>/assets/dist/img/icon612x612.png" alt="scriptlog-logo" width="72" height="72">
  </a>
  </h1> 
</div>

<div class="login-box-body">  

<?php 
  if (isset($errors['errorMessage'])) : 
?>

<div class="alert alert-danger alert-dismissable">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <?= $errors['errorMessage']; ?>
</div>

<?php 
    endif; 

  if (isset($_GET['status']) && $_GET['status'] == 'changed') {
  
      echo '<div class="alert alert-info alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>The password has been ' . htmlspecialchars($_GET['status']) . '. Please enter with your new password!</div>';
   
  } 
  
  if (isset($_GET['status']) && $_GET['status'] == 'actived') {
 
      echo '<div class="alert alert-info alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>The account has been ' . htmlspecialchars($_GET['status']) . '. Pleas log in with your email and password!</div>';
 
  } 

?>

<form name="formlogin" action="<?= form_action('login.php', ['LogIn', human_login_id(), md5(app_key().$ip)], 'login')['doLogin']; ?>" method="post" onSubmit="return validasi(this)" autocomplete="off">
<div class="form-group has-feedback">
<label for="inputLogin">Username or Email Address</label>
<input type="text"  class="form-control" id="inputLogin" placeholder="username or email" name="login" maxlength="186" value="
<?= isset($_COOKIE['scriptlog_auth']) ? ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipher_key) : ""; ?>" autocomplete="off" autocapitalize="off" autofocus required>
<span class="glyphicon glyphicon-user form-control-feedback"></span>
</div>

<div class="form-group has-feedback">
<label for="inputPassword">Password</label>
<input type="password" class="form-control" id="inputPassword" placeholder="Password" name="user_pass" maxlength="50" autocomplete="off" value="" required>
<span class="glyphicon glyphicon-lock form-control-feedback"></span>  
</div>

<?php if (isset($failed_login_attempt) && $failed_login_attempt >= 5) : ?> 

<div class="form-group has-feedback">
<label for="inputCaptcha">Enter captcha code</label>
<input type="text" class="form-control" id="inputCaptcha" placeholder="Please type a captcha code here" name="captcha_login" required>
<span class="glyphicon glyphicon-hand-down form-control-feedback"></span>
<img src="<?= app_url().'/admin/captcha-login.php'; ?>" alt="image_captcha">
</div>

<?php endif; ?>

<div class="form-group has-feedback">
<input type="text" name="scriptpot_name" class="form-control scriptpot" autocomplete="off">
</div>
<div class="form-group has-feedback">
<input type="email" name="scriptpot_email" class="form-control scriptpot" autocomplete="off">
</div>

<div class="row">
  <div class="col-xs-8">
    <div class="checkbox icheck">
      <label for="remember-me">
      <input type="checkbox" aria-checked="false" name="remember" aria-selected="false" id="remember-me" <?php if (isset($_COOKIE['scriptlog_auth'])) : ?> checked<?php endif; ?>>   Remember Me
      </label>
    </div>
</div>          
<div class="col-xs-4">

<?php 
  $block_csrf = function_exists('generate_form_token') ? generate_form_token('login_form', 40) : '';
?>
    
<input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
<input type="submit" class="btn btn-primary btn-block btn-flat" name="LogIn" value="Log In">
</div>
</div>
</form>
<?php 
     if (is_registration_unable() === true):
  ?>   
    <a href="<?= app_url() . '/admin/signup.php'; ?>" class="text-center" aria-label="Register">Register |</a>
  <?php 
     endif;
  ?>

  <a href="<?= app_url() . '/admin/reset-password.php'; ?>" class="text-center" aria-label="Lost your password">Lost your password?</a> 
</div>
  
<?php login_footer($stylePath); ?>