<?php 
/**
 * file recover-password.php
 * 
 * @category  recovering user password
 * @package   SCRIPTLOG
 * @author    M.Noermoehammad
 * 
 */
if (file_exists(__DIR__ . '/../config.php')) {
    
    include(dirname(dirname(__FILE__)).'/lib/main.php');
  
} else {

  header("Location: ../install");
  exit();
  
}

$errors = [];
$stop = null;
$recoverFormSubmitted = isset($_POST['Change']);
$tempKey = isset($_GET['tempKey']) ? htmlentities(strip_tags($_GET['tempKey'])) : "";
$user = $userDao -> getUserByResetKey($tempKey);

if (!$user) {
    $stop = "Temporary key is not valid.";
}

if ($user['user_reset_complete'] == 'Yes') {
    $stop = "Your password has been changed";
}

if (empty($recoverFormSubmitted) == false) {
   
    $password = isset($_POST['pass1']) ? prevent_injection($_POST['pass1']) : "";
    $confirmPass = isset($_POST['pass2']) ? prevent_injection($_POST['pass2']) : "";

    $badCSRF = true;

    if (!isset($_POST['csrf']) || !isset($_SESSION['CSRF']) || empty($_POST['csrf'])
        || $_POST['csrf'] !== $_SESSION['CSRF']) {
      
        $errors['errorMessage'] = "Sorry, there was a security issue";
        $badCSRF = true;
  
    } elseif (empty($password) || empty($confirmPass)) {

       $errors['errorMessage'] = "All column must be filled";

    } elseif (!preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{8,50}$/', $password)) {

      $errors['errorMessage'] = "The password may contain letter and numbers, at least one number and one letter, any of these characters !@#$%";

   } elseif (strlen($password) < 8) {

      $errors['errorMessage'] = "The Password must consist of least 8 characters";

   } elseif ($password !== $confirmPass) {

     $errors['errorMessage'] = "The Password does not match";

   } else {

      $badCSRF = false;
      unset($_SESSION['CSRF']);
      $authenticator -> updateNewPassword($password, abs((int)$user['ID']));
      direct_page('login.php?status=changed', 200);

   }

}

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Change Password | Scriptlog</title>
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
    <a href="#"><img class="d-block mx-auto mb-4" src="assets/dist/img/icon612x612.png" alt="Recover Password" width="72" height="72"></a>
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
        <input type="password" class="form-control" name="pass1" placeholder="New password" autofocus required maxlength="50" autocomplete="off">
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      
      <div class="form-group has-feedback">
          <input type="password" class="form-control" name="pass2" placeholder="Confirm new password" required maxlength="50" autocomplete="off" >
          <span class="glyphicon glyphicon-lock form-control-feedback"></span>  
      </div>
        
      <div class="row">
        <div class="col-xs-8">
      <?php 
      // prevent CSRF
      $key= random_generator(13);
      $CSRF = bin2hex(openssl_random_pseudo_bytes(32).$key);
      $_SESSION['CSRF'] = $CSRF;
      ?>
        <input type="hidden" name="csrf" value="<?= $CSRF; ?>">
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