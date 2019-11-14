<?php
/**
 * file reset-password.php
 * 
 * @category resetting user password
 * @package  SCRIPTLOG
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

$errors = [];
$resetFormSubmitted = isset($_POST['Reset']);
  
if (empty($resetFormSubmitted) == false) {

  $user_email = filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_EMAIL);
  $badCSRF = true;

  if (!isset($_POST['csrf']) || !isset($_SESSION['CSRF']) || empty($_POST['csrf'])
  || $_POST['csrf'] !== $_SESSION['CSRF']) {
      
    $errors['errorMessage'] = "Sorry, there was a security issue";
  
    $badCSRF = true;
  
  } elseif (empty($user_email)) {

     $errors['errorMessage'] = "Please enter email address";

  } elseif (email_validation($user_email) == 0) {

     $errors['errorMessage'] = "Please enter a valid email address";

  } elseif ($authenticator -> checkEmailExists($user_email) === false) {

     $errors['errorMessage'] = "Your email address is not registered";

  } else {

    $badCSRF = false;
    unset($_SESSION['CSRF']);
    $authenticator -> resetUserPassword($user_email);
    direct_page('reset-password.php?status=reset', 200);

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

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
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
    <button type="button" class="close" data-dismiss="alert"
      aria-hidden="true">&times;</button>
           <?= $errors['errorMessage']; ?>
    </div>
  
    <?php 
      endif; 
    ?>

  <?php 
    if (isset($_GET['status']) && $_GET['status'] == 'reset') : ?>
				<div class="alert alert-success alert-dismissable">
					<button type="button" class="close" data-dismiss="alert"
						aria-hidden="true">&times;</button>
				 password has been <strong><?= htmlspecialchars($_GET['status']); ?> </strong>.
					check your e-mail !
				</div>
				
  <?php 
   else :
  ?>

  <p class="login-box-msg">Please enter your email address. You will receive a link to create a new password via email.</p>
  
    <form name="formlogin" action="reset-password.php" method="post" onSubmit="return validasi(this)" role="form" autocomplete="off">
      <div class="form-group has-feedback">
        <input type="email" class="form-control" name="user_email" placeholder="Email" autofocus required>
        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      </div>
      
      <div class="row">
        <div class="col-xs-8">
      <?php 
      // prevent CSRF
      $key= random_generator(13);
      $CSRF = bin2hex(openssl_random_pseudo_bytes(32).$key);
      $_SESSION['CSRF'] = $CSRF;
      ?>
        <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
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