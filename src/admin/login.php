<?php
/**
 * login.php
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

$ip = (isset($_SERVER["REMOTE_ADDR"])) ? $_SERVER["REMOTE_ADDR"] : get_ip_address();

if (file_exists(__DIR__ . '/../config.sample.php')) {
    
    include __DIR__ . '/../lib/main-dev.php';
    require __DIR__ . '/authorizer.php';

} else {

   header("Location: ../install");
   exit();
  
}

if ($loggedIn) {

   direct_page('index.php?load=dashboard', 302);
   
}

if ((isset($_POST['LogIn'])) && ($_POST['LogIn'] == 'Login')) {
  
  $login = isset($_POST['login']) ? prevent_injection($_POST['login']) : "";
  $user_pass = isset($_POST['user_pass']) ? prevent_injection($_POST['user_pass']) : "";

  $is_authenticated = false;
  $captcha = true;
  $badCSRF = true;

  $login_attempt = get_login_attempt($ip);
  $failed_login_attempt = $login_attempt['failed_login_attempt'];

  if (false === verify_form_token('loginForm')) {
     
     $badCSRF = true;
     write_log($ip, 'formLogin');
     $errors['errorMessage'] = "Sorry, there was a security issue";
   
  } 

  if ((isset($_POST["captcha_code"]) && (isset($_SESSION['scriptlog_captcha_code']))) 
         && ($_POST["captcha_code"] !== $_SESSION['scriptlog_captcha_code'])) {

      $captcha = false;
      $errors['errorMessage'] = "Please enter correct captcha code";

  }

  if ((empty($login)) && (empty($user_pass))) {
  
    $errors['errorMessage'] = "All Column must be filled";
 
  } else {
  
    if ($authenticator -> validateUserAccount($login, $user_pass)) {

      $is_authenticated = true;
      
    }

    if (($is_authenticated) && ($captcha == true)) {

      $badCSRF = false;
  
      unset($_SESSION['CSRF']);
  
      $authenticator->login($_POST);
  
      delete_login_attempt($ip);
  
      direct_page('index.php?load=dashboard', 302);
  
    }  else {

      $errors['errorMessage'] = "Invalid Login!";

      if ($failed_login_attempt < 3 ) {

        create_login_attempt($ip);
    
      } else {
    
        $errors['errorMessage'] = "You have tried more than 3 invalid attempts. Enter captcha code.";
    
      }

    }

 }

 if (false === check_form_request($_POST, ['login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_code', 'remember', 'csrf', 'LogIn'])) {

    write_log($ip, 'formLogin');
    $errors['errorMessage'] = "Unknown request";

 }
 
 if (filter_var($login, FILTER_VALIDATE_EMAIL)) {

      if ($authenticator->checkEmailExists($login) == false) {

         $errors['errorMessage'] = "Your email address is not registered";
          
      }

 } else {

    if (!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $login)) {

      $errors['errorMessage'] = "Incorrect username!";
        
    }
       
  }

  if (scriptpot_validate($_POST) == false) {

       $errors['errorMessage'] = "anomaly behaviour detected";

  }
    
}
  
?>
  
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Log In | Scriptlog</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="assets/components/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="assets/components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="assets/components/Ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="assets/components/iCheck/square/blue.css">
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="assets/dist/js/html5shiv.js"></script>
  <script src="assets/dist/js/respond.min.js"></script>
  <![endif]-->
  
<!-- Google Font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
<link href="favicon.ico" rel="Shortcut Icon">
<style>
.scriptpot{opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1}
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
<div class="login-logo">
  <a href="#"><img class="d-block mx-auto mb-4" src="assets/dist/img/icon612x612.png" alt="Log In" width="72" height="72"></a>
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
  ?>

<?php 	
  
  if (isset($_GET['status']) && $_GET['status'] == 'changed') {
  
     echo '<div class="alert alert-info alert-dismissable">
           <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>The password has been ' . htmlspecialchars($_GET['status']) . '. Please enter with your new password!</div>';
  
  } elseif (isset($_GET['status']) && $_GET['status'] == 'actived') {

     echo '<div class="alert alert-info alert-dismissable">
           <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>The account has been ' . htmlspecialchars($_GET['status']) . '. Pleas log in with your email and password!</div>';

  }
  
?>
  
<form name="formlogin" action="<?=app_url().'admin/login.php';?>" method="post" onSubmit="return validasi(this)"  role="form">

<div class="form-group has-feedback">
<label>Username or Email Address</label>
<input type="text"  class="form-control" placeholder="username or email" name="login" maxlength="186" value="
<?php if (isset($_COOKIE['scriptlog_cookie_login'])) : echo $_COOKIE['scriptlog_cookie_login'];
elseif (isset($_COOKIE['scriptlog_cookie_email'])) : echo $_COOKIE['scriptlog_cookie_email']; endif; ?>" autocomplete="off" autofocus required>
<span class="glyphicon glyphicon-user form-control-feedback"></span>
</div>

<div class="form-group has-feedback">
<label>Password</label>
<input type="password" class="form-control" placeholder="Password" name="user_pass" maxlength="50" autocomplete="off" value="
<?=(isset($_COOKIE['user_pass'])) ? $_COOKIE['user_pass'] : ""; ?>" required>
<span class="glyphicon glyphicon-lock form-control-feedback"></span>  
</div>

<div class="form-group has-feedback">
<input type="text" name="scriptpot_name" class="form-control scriptpot" autocomplete="off">
</div>

<div class="form-group has-feedback">
<input type="email" name="scriptpot_email" class="form-control scriptpot" autocomplete="off">
</div>

<?php if (isset($failed_login_attempt) && $failed_login_attempt >= 3) : ?> 

<div class="form-group has-feedback">
<label>Enter captcha code</label>
<input type="text" class="form-control" name="captcha_code">
<span class="glyphicon glyphicon-hand-down form-control-feedback"></span>
<img src="<?=app_url().'admin/captcha.php'; ?>" alt="image_captcha">
</div>

<?php endif; ?>

<div class="row">
  <div class="col-xs-8">
    <div class="checkbox icheck">
      <label>
        <input type="checkbox" name="remember" <?php if (isset($_COOKIE['scriptlog_cookie_login'])) : echo "checked"?> 
        <?php elseif(isset($_COOKIE['scriptlog_cookie_email'])) : echo "checked";?><?php endif; ?>> Remember Me
      </label>
    </div>
  </div>
          
  <div class="col-xs-4">
  <?php 
    $block_csrf = generate_form_token('loginForm', 32); // prevent csrf
  ?>
    <input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
    <input type="submit" class="btn btn-primary btn-block btn-flat" name="LogIn" value="Login">
  </div>

  </div>
</form>
  <a href="reset-password.php" class="text-center">Lost your password?</a>    
</div>
  
</div>
  
<script src="assets/components/jquery/dist/jquery.min.js"></script>
<script src="assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="assets/components/iCheck/icheck.min.js"></script>
<script src="assets/dist/js/checklogin.js"></script>
<script>
    $(function () {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' 
      });
    });
</script>
<script>
$('img').bind('contextmenu',function(e){return false;}); 
</script>
<script>
$(document).bind("contextmenu",function(e){return false;});
</script>
<script>
document.onkeydown=function(e){if(e.ctrlKey&&(e.keyCode===67||e.keyCode===86||e.keyCode===85||e.keyCode===117)){return false;}else{return true;}};$(document).keypress("u",function(e){if(e.ctrlKey)
{return false;}
else
{return true;}});
</script>

</body>
</html>
