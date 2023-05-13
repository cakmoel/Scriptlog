<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<div class="content-wrapper">
 <section class="content-header">
   <h1><?=(isset($pageTitle)) ? $pageTitle : ""; ?><small>Control Panel</small></h1>
   <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li class="active">Remove profile</li>
    </ol>
 </section>

<!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-4">
<div class="box box-primary">
<div class="box-header with-border">
<h3 class="box-title">This will permanently delete your profile </h3>
</div>
<!-- .box-header -->

<?php
if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h2><i class="icon fa fa-warning"></i> Invalid Form Data!</h2>
<?php 
foreach ($errors as $e) :
echo '<p>' . $e . '</p>';
endforeach;
?>
</div>
<?php 
endif;
?>

<?php 
$action = isset($formAction) ? $formAction : null;
$user_id = (isset($userData['ID'])) ? safe_html((int)$userData['ID']) : 0;
$session_id = (isset($userData['user_session'])) ? safe_html($userData['user_session']) : sha1(app_key());
?>

<form method="post" action="<?= generate_request('index.php', 'post', ['users', $action, $user_id, $session_id])['link'] ?>" >
<input type="hidden" name="user_id" value="<?= $user_id; ?>">
<input type="hidden" name="session_id" value="<?= $session_id; ?>">
  
 <div class="box-body">
  <div class="form-group">
    <label for="current_password">Password</label>
    <input type="password" class="form-control" id="current_password" name="current_pwd" placeholder="Type your current password" required>
  </div>

  <div class="form-group">
    <label for="confirm_password">Confirm password</label>
    <input type="password" class="form-control" id="confirm_password" name="confirm_pwd" placeholder="confirm your current password" maxlength="50" autocomplete="off" required>
  </div>
 </div>
 <!-- .box-body-->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
<button type="button" class="btn btn-default" onclick="history.back()">Cancel</button>
<input type="submit" name="userFormSubmit" class="btn btn-danger pull-right" value="<?= (($user_id) && ($user_id != '')) ? "Delete" : " "; ?>">
</div>

</form>
</div>
</div>
</div>
<!-- .row -->
</section>
<!-- .content -->
</div>