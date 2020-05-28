<?php
/**
 * file reset-password.php
 * 
 * @category resetting user password
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
if (file_exists(__DIR__ . '/../config.php')) {
    
  include(dirname(dirname(__FILE__)).'/lib/main.php');

} else {

  header("Location: ../install");
  exit();

}

if (isset($_POST['Reset'])) {

  $csrf = isset($_POST['csrf']) ? $_POST['csrf'] : '';
  $user_email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);
  $valid = !empty($csrf) && verify_form_token('reset_pwd', $csrf);

  $captcha_code = isset($_POST['captcha_code']) ? $_POST['captcha_code'] : '';
  $captcha = true;

  if( !$valid ) {
     
    $errors['errorMessage'] = "Sorry, Attack detected!";
  
  }

  if (count($_POST)> 0 && $captcha_code != $_SESSION['scriptlog_reset_pwd']) {

      $captcha = false;
      $errors['errorMessage'] = "Please enter correct captcha code";

  }

  if (empty($user_email)) {

     $errors['errorMessage'] = "Please enter email address";

  } elseif (email_validation($user_email) == 0) {

     $errors['errorMessage'] = "Please enter a valid email address";

  } elseif ($authenticator -> checkEmailExists($user_email) == false) {

     $errors['errorMessage'] = "Your email address is not registered";

  } else {
    
    if($captcha == true) {

      $authenticator -> resetUserPassword($user_email);

      direct_page('reset-password.php?status=reset', 200);
       
    }
    
  }
  
  if (scriptpot_validate($_POST) == false) {

    $errors['errorMessage'] = "anomaly behaviour detected!";

  } 

}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Lost Password | Scriptlog</title>
  <!-- Tell the browser to be responsive to screen width -->
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

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
<link href="favicon.ico" rel="Shortcut Icon">
<style>
.scriptpot{opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1}
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
<div class="login-logo">
  <a href="#"><img class="d-block mx-auto mb-4" src="assets/dist/img/icon612x612.png" alt="Reset Password" width="72" height="72"></a>
</div>
<!-- /.login-logo -->
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

  if (isset($_GET['status']) && $_GET['status'] == 'reset') : ?>
				<div class="alert alert-success alert-dismissable">
					<button type="button" class="close" data-dismiss="alert"
						aria-hidden="true">&times;</button>
				 password has been <strong><?= safe_html($_GET['status']); ?> </strong>.
					check your e-mail !
				</div>

<?php 
  else :
?>

<p class="login-box-msg">Enter your email address. You will receive a link to create a new password via email.</p>  
<form name="formlogin" action="reset-password.php" method="post" onSubmit="return validasi(this)" role="form" autocomplete="off">
      
<div class="form-group has-feedback">
<label>Email Address</label>
  <input type="email" class="form-control" name="user_email" placeholder="Email" autofocus required>
  <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
</div>

<div class="form-group has-feedback">
  <input type="text" name="scriptpot_name" class="form-control scriptpot" autocomplete="off">
</div>

<div class="form-group has-feedback">
  <input type="email" name="scriptpot_email" class="form-control scriptpot" autocomplete="off">
</div>

<div class="form-group has-feedback">
  <label>Enter captcha code</label>
  <input type="text" class="form-control" placeholder="Please type a captcha code here" name="captcha_code">
<span class="glyphicon glyphicon-hand-down form-control-feedback"></span>
<img src="<?=app_url().'/admin/captcha-forgot-pwd.php'; ?>" alt="image_captcha">
</div>
      
<div class="row">
  <div class="col-xs-8">
  
<?php 
  $block_csrf = generate_form_token('reset_pwd', 24); // prevent csrf
?>
        
<input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
<input type="submit" class="btn btn-primary btn-block btn-flat" name="Reset" value="Get New Password">
</div>
    <!-- /.col -->
</div>
</form>

<div class="social-auth-links text-center"></div>
<a href="login.php" class="text-center">Log In</a>
    
<?php 
  endif;
?>

</div>
  <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

<script src="assets/components/jquery/dist/jquery.min.js"></script>
<script src="assets/dist/js/checklogin.js"></script>
<script src="assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="assets/components/iCheck/icheck.min.js"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' /* optional */
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