<?php

/**
 * signup.php
 * 
 * signup functionality
 * to access user registration for membership
 * 
 * @category signup.php file
 * @author Nirmala Khanza <nirmala.adiba.khanza@gmail.com>
 * @license MIT
 * @version 1.0
 * @since   Since Release 1.0
 * 
 */
if (file_exists(__DIR__ . '/../config.php')) {

  include __DIR__ . '/../lib/main.php';

  $ip = get_ip_address();

  include __DIR__ . '/register-layout.php';

  $stylePath =  preg_replace("/\/signup\.php.*$/i", "", current_load_url());
} else {

  header("Location: ../install");
  exit();
}

$action = isset($_GET['action']) ? safe_html($_GET['action']) : "";
$signupId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$uniqueKey = isset($_GET['uniqueKey']) ? safe_html($_GET['uniqueKey']) : null;

if (($action == 'SignUp') && (block_request_type(current_request_method(), ['POST']) === false)) {

  list($errors, $signup_success) = function_exists('processing_signup') ? processing_signup($ip, $signupId, $uniqueKey, $errors, $_POST) : "";
}

register_header($stylePath);

?>

<div class="register-logo">
  <h1>
    <a href="#">
      <img class="d-block mx-auto mb-4" src="<?= $stylePath; ?>/assets/dist/img/icon612x612.png" alt="scriptlog-logo" width="72" height="72">
    </a>
  </h1>
</div>

<div class="register-box-body">

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
   if (is_registration_unable() === false) :
  ?>

    <div class="alert alert-danger alert-dismissible">

      <h4><i class="icon fa fa-ban"></i> Alert!</h4>
      Error: User registration is currently not allowed.
    </div>
    <a href="<?= app_url() . '/admin/login.php'; ?>" class="text-center" aria-label="Log In">Log In </a>

  <?php
   elseif (isset($signup_success['successMessage'])) :
  ?>

    <div class="callout callout-success">
      <h4><?= $signup_success['successMessage']; ?></h4>

      <p>You have successfully registered, you can now login !.</p>
    </div>
    <a href="<?= app_url() . '/admin/login.php'; ?>" class="text-center" aria-label="Log In">Log In </a>
  
  <?php
    else :
  ?>

    <p class="login-box-msg">Register For This Blog</p>

    <form name="formsignup" action="<?= form_action('signup.php', ['SignUp', signup_id(), md5(app_key() . $ip)], 'signup')['doSignup']; ?>" method="post" onSubmit="return validasi(this)" autocomplete="off">
      <div class="form-group has-feedback">
        <label for="inputUsername">Username</label>
        <input type="text" class="form-control" name="user_login" id="inputUsername" placeholder="Username" maxlength="186" autocomplete="off" autocapitalize="off" autofocus required>
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <label for="inputEmail">Email</label>
        <input type="email" class="form-control" name="user_email" id="inputEmail" placeholder="Email" maxlength="186" autocomplete="off" autocapitalize="off" autofocus required>
        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <label for="Password">Password</label>
        <input type="password" class="form-control" name="user_pass" id="Password" placeholder="Password" maxlength="50" autocomplete="off" value="" required>
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <label for="RetypePassword">Retype password</label>
        <input type="password" class="form-control" name="user_pass2" id="RetypePassword" placeholder="Retype password" maxlength="50" autocomplete="off" value="" required>
        <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
      </div>

      <div class="form-group has-feedback">
        <input type="text" name="scriptpot_name" class="form-control scriptpot" autocomplete="off">
      </div>
      <div class="form-group has-feedback">
        <input type="email" name="scriptpot_email" class="form-control scriptpot" autocomplete="off">
      </div>

      <div class="row">
        <div class="col-xs-8">
          <div class="checkbox icheck">
            <label for="iagree-to-terms">
              <input type="checkbox" aria-checked="false" name="iagree" aria-selected="false" id="iagree-to-terms"> I agree to the <a href="terms-of-use.html" target="_blank" rel="noopener noreferrer" title="Terms of use" aria-label="Terms of Use">terms of use</a>
            </label>
          </div>
        </div>
        <!-- /.col -->
        <div class="col-xs-4">

          <?php
          $block_csrf = function_exists('generate_form_token') ? generate_form_token('signup_form', 40) : '';
          ?>
          <input type="hidden" name="csrf" value="<?= $block_csrf; ?>">
          <input type="submit" class="btn btn-primary btn-block btn-flat" name="SignUp" value="Register">
        </div>
        <!-- /.col -->
      </div>
    </form>

    <?php
    if (is_registration_unable() === true) :
    ?>
      <a href="<?= app_url() . '/admin/login.php'; ?>" class="text-center" aria-label="Log in">Log in |</a>
    <?php
    endif;
    ?>

    <a href="<?= app_url() . '/admin/reset-password.php'; ?>" class="text-center" aria-label="Lost your password">Lost your password?</a>
</div>

<?php
  endif;

  register_footer($stylePath);
?>