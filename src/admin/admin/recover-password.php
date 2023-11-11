<?php 
/**
 * file recover-password.php
 * 
 * @category  recovering user password
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * 
 */

if (file_exists(__DIR__ . '/../config.php')) {
    
  include dirname(dirname(__FILE__)).'/lib/main.php';
  
} else {

  header("Location: ../install");
  exit();
  
}

$stop = null;

$tempKey = isset($_GET['tempKey']) ? escape_html($_GET['tempKey']) : "";
$user = $userDao->getUserByResetKey($tempKey);

if (empty($user['user_reset_key'])) {
    
  $stop = "Temporary key is not valid.";

} elseif ($user['user_reset_complete'] == 'Yes') {
    $stop = "Your password has been changed";
}

if (isset($_POST['Change']) && $_POST['Change'] == 'Change Password') {

  $password = isset($_POST['pass1']) ? prevent_injection($_POST['pass1']) : "";
  $confirmPass = isset($_POST['pass2']) ? prevent_injection($_POST['pass2']) : "";
  $csrf = isset($_POST['csrf']) ? $_POST['csrf'] : '';
  $valid = !empty($csrf) && verify_form_token('recover_pwd', $csrf);

  if (!$valid) {

    $errors['errorMessage'] = "Sorry, there was a security issue";

  }
    
  if (empty($password) || empty($confirmPass)) {

    $errors['errorMessage'] = "All column must be filled";

  } elseif ($password !== $confirmPass) {

    $errors['errorMessage'] = "Password does not match";

  } elseif (check_common_password($password) === true ) {

    $errors['errorMessage'] = "Your password seems to be the most hacked password, please try another";

  } elseif (false === check_pwd_strength($password) ) { 

    $errors['errorMessage'] = "Password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters";

  } else {

    $authenticator->updateNewPassword($password, abs((int)$user['ID']), $user['user_email']);
    direct_page('login.php?status=changed', 302);

  }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="noindex,noarchive" />
  <meta name="referrer" content="strict-origin-when-cross-origin" />
  <title>Change Password | Scriptlog</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width" name="viewport">
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
  <!-- Icon -->
  <link href="favicon.ico" rel="Shortcut Icon">
  
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <h1>
    <a href="#"><img class="d-block mx-auto mb-4" src="assets/dist/img/icon612x612.png" alt="Recover Password" width="72" height="72"></a>
    </h1>
  </div>
  <!-- /.login-logo -->
  <div class="login-box-body">
  
  <?php 
       if (isset($errors['errorMessage'])) : 
    ?>
    
       <div class="alert alert-danger alert-dismissable">
    <button type="button" class="close" data-dismiss="alert"
      aria-hidden="true">&times;</button>
           <?= $errors['errorMessage']; ?>
    </div>
  
    <?php 
      endif; 
    ?>

  <?php 
    if (isset($stop)) :
  ?>
<div class="alert alert-danger">
<?= $stop; ?>
<script type="text/javascript">function leave() {  window.location = "<?= $config['app']['url']; ?>";} setTimeout("leave()", 3640);</script>
</div>
<?php 
else :
?>
  <p class="login-box-msg">Enter your new password</p>
  
    <form name="formlogin" action="recover-password.php" method="post" onSubmit="return validasi(this)" role="form" autocomplete="off">
      
      <div class="form-group has-feedback">
      <label for="inputNewPassword">New password</label>
        <input type="password" class="form-control" id="inputNewPassword" name="pass1" placeholder="New password" autofocus required maxlength="50" autocomplete="off">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      
      <div class="form-group has-feedback">
          <input type="password" class="form-control" name="pass2" placeholder="Confirm new password" required maxlength="50" autocomplete="off" >
          <span class="glyphicon glyphicon-lock form-control-feedback"></span>  
      </div>
        
      <div class="row">
        <div class="col-xs-8">
  <?php 
    $block_csrf = generate_form_token('recover_pwd', 64); 
  ?>
        <input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
        <input type="submit" class="btn btn-primary btn-block btn-flat" name="Change" value="Change Password">
        </div>
        <!-- /.col -->
      </div>
    </form>

    <div class="social-auth-links text-center"></div>
<?php 
endif; 
?>
  </div>
  <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

<!-- jQuery 3 -->
<script src="assets/components/jquery/dist/jquery.min.js"></script>
<script src="assets/dist/js/checklogin.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- iCheck -->
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
</body>
</html>