<?php if (!defined('SCRIPTLOG')) exit(); ?>

<div class="content-wrapper">
<!-- Content Header (Page header) -->
<section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">User</a></li>
        <li class="active">Profile</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">

      <div class="row">
        <div class="col-md-3">

          <!-- Profile Image -->
          <div class="box box-primary">
            <div class="box-body box-profile">

              <img class="profile-user-img img-responsive img-circle" src="<?=app_url().APP_ADMIN.DS.'assets/dist/img/profilepict.png'?>" alt="User profile picture">
              
              <h3 class="profile-username text-center"><?=(isset($userData['user_login'])) ? htmlspecialchars($userData['user_login']) : ""; ?></h3>

              <p class="text-muted text-center"><?=(isset($userData['user_fullname'])) ? htmlspecialchars($userData['user_fullname']) : ""; ?></p>

              <ul class="list-group list-group-unbordered">

                <li class="list-group-item">

                  <b><?=(!empty($userData['user_url'])) ? htmlspecialchars($userData['user_url']) : "Site Address(URL):"; ?></b>  <a class="pull-right" href="<?=($userData['user_url'] == '#') ? "#" : htmlspecialchars($userData['user_url']); ?>"></a>
                
                </li>
             
              </ul>

            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->

          <!-- About Me Box -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">My profile</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              
              <strong><i class="fa fa-user margin-r-5"></i> Role</strong>

              <p class="text-muted">
                <?=(isset($userData['user_level'])) ? $userData['user_level'] : ""; ?>
              </p>

              <hr>

              <strong><i class="fa fa-envelope margin-r-5"></i> Email Address</strong>

              <p class="text-muted"><?=(isset($userData['user_email'])) ? $userData['user_email'] : ""; ?></p>

              <hr>

              <strong><i class="fa fa-calendar-check-o margin-r-5"></i> Registered</strong>

<p>
  <span class="label label-success"><?= read_datetime($userData['user_registered']); ?></span>
</p>


            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
        <div class="box box-primary">
<div class="box-header with-border"></div>
<!-- /.box-header -->

<?php
if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-warning"></i> Invalid Form Data!</h4>
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
if (isset($status)) :
?>
<div class="alert alert-success alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-check"></i> Success!</h4>
<?php 
    foreach ($status as $s) :
                
      echo $s;
              
    endforeach;
           
?>          
</div>
<?php 
  endif;
?>

<?php
$action = isset($formAction) ? $formAction : null;
$user_id = isset($userData['ID']) ? $userData['ID'] : 0;
$session_id = isset($userData['user_session']) ? $userData['user_session'] : null;
?>
<form name="scriptlogForm" method="post" action="<?=generate_request('index.php', 'post', ['users', $action, $user_id, $session_id])['link']; ?>" role="form" autocomplete="off">
<input type="hidden" name="user_id" value="<?= $user_id; ?>" />
<input type="hidden" name="session_id" value="<?= $session_id; ?>" />

<div class="box-body">
<?php 
if ((isset($userData['user_login'])) && ($userData['user_login'] != '')) : 
?>
<div class="form-group">
<label>Username</label>
<input type="text" class="form-control" name="user_login" value="
<?=($userData['user_login']) ? htmlspecialchars($userData['user_login']) : ""; ?>"
<?=(!empty($userData['user_login'])) ? "disabled" : ""; ?>>
<p class="help-block">This username can not be changed.</p>
</div>
<?php endif;  ?>

<div class="form-group">
<label>Fullname</label>
<input type="text" class="form-control" name="user_fullname" placeholder="Enter real name" value="
<?=(!empty($userData['user_fullname'])) ? htmlspecialchars($userData['user_fullname']) : ""; ?>
<?=(isset($formData['user_fullname'])) ? htmlspecialchars($formData['user_fullname'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>">
</div>

<div class="form-group">
<label>Email (required)</label>
<input type="email" class="form-control" name="user_email" placeholder="something@example.com" value="
<?=(isset($userData['user_email']) && $userData['user_email'] != '#') ? htmlspecialchars($userData['user_email']) : ""; ?>
<?=(isset($formData['user_email'])) ? htmlspecialchars($formData['user_email'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>

<div class="form-group">
<label>Password (required)</label>
<input type="password" class="form-control" name="user_pass" placeholder="Enter password" maxlength="50" autocomplete="off">
</div>

<?php if(!empty($userData['user_email'])) :?>
<div class="form-group">
<label>confirm Password (required)</label>
<input type="password" class="form-control" name="user_pass" placeholder="Confirm password" maxlength="50" autocomplete="off">
</div>
<?php  endif; ?>

<div class="form-group">
<label>Website</label>
<input type="text" class="form-control" name="user_url" placeholder="Enter url" value="<?=(isset($formData['user_url'])) ? htmlspecialchars($formData['user_url'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
<?=(isset($userData['user_url'])) ? $userData['user_url'] : ""; ?>" >
</div>


</div>
<!-- /.box-body -->

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="userFormSubmit" class="btn btn-primary" value="<?=(isset($userData['ID']) && $userData['ID'] != '') ? "Update Profile" : "Save Changes"; ?>">
</div>

</form>
            
</div>
<!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

    </section>
    <!-- /.content -->
</div>