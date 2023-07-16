<?php if (!defined('SCRIPTLOG')) {
  exit();
} ?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      <?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>Control Panel</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
      <li><a href="index.php?load=users">Users </a></li>
      <li class="active"><?= (isset($pageTitle)) ? $pageTitle : ""; ?></li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-6">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h2 class="box-title"><?= (isset($userData['ID']) && $userData != '') ? "Personal Detail" : "Create a brand new user and add them to this site"; ?></h2>
          </div>
          <!-- /.box-header -->
          <?php
          if (isset($errors)) :
          ?>
            <div class="alert alert-danger alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <h3><i class="icon fa fa-warning" aria-hidden="true"></i> Invalid form data!</h3>
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
          $action = (isset($formAction)) ? $formAction : null;
          $user_id = (isset($userData['ID'])) ? safe_html((int)$userData['ID']) : 0;
          $session_id = (isset($userData['user_session'])) ? safe_html($userData['user_session']) : sha1(app_key());
          ?>

          <form method="post" action="<?= generate_request('index.php', 'post', ['users', $action, $user_id, $session_id])['link']; ?>">
            <input type="hidden" name="session_id" value="<?= $session_id; ?>">
            <input type="hidden" name="user_id" value="<?= $user_id; ?>">
            <div class="box-body">

              <?php
              if (isset($userData['user_registered'])) :
              ?>
                <div class="form-group">
                  <label>Registered</label>
                  <?= read_datetime(safe_html($userData['user_registered'])); ?>
                </div>
              <?php
              endif;
              ?>

              <div class="form-group">
                <label for="username">Username <?= (isset($userData['user_login'])) ? "" : "(required)" ?></label>
                <input type="text" class="form-control" id="username" name="user_login" placeholder="Enter username" value="
<?= (isset($formData['user_login'])) ? safe_html($formData['user_login']) : ""; ?>
<?= (isset($userData['user_login'])) ? safe_html($userData['user_login']) : ""; ?>" required <?= (isset($userData['user_login']) && $userData['user_login'] !== '') ? "disabled" : ""; ?>>
                <p class="help-block">This username can not be changed.</p>
              </div>

              <div class="form-group">
                <label for="fullname">Fullname</label>
                <input type="text" class="form-control" id="fullname" name="user_fullname" placeholder="Enter real name" value="
<?= (isset($userData['user_fullname'])) ? safe_html($userData['user_fullname']) : ""; ?>
<?= (isset($formData['user_fullname'])) ? safe_html($formData['user_fullname']) : "";  ?>">
              </div>

              <div class="form-group">
                <label for="email">Email (required)</label>
                <input type="email" class="form-control" id="email" name="user_email" placeholder="Enter email" value="
<?= (isset($userData['user_email'])) ? safe_html($userData['user_email']) : ""; ?>
<?= (isset($formData['user_email'])) ? safe_html($formData['user_email']) : ""; ?>" required>
              </div>

              <div class="form-group">
                <label for="passwd"><?= (!empty($userData['user_email'])) ? "New password" : "Password (required)"; ?></label>
                <input type="password" class="form-control" id="passwd" name="user_pass" placeholder="Enter password" maxlength="50" autocomplete="off">
              </div>

              <?php if (!empty($userData['user_email'])) : ?>

                <div class="form-group">
                  <label for="confirm_pwd"><?= (!empty($userData['user_email'])) ? "Confirm new password (required)" : ""; ?></label>
                  <input type="password" class="form-control" id="confirm_pwd" name="user_pass2" placeholder="Confirm new password" maxlength="50" autocomplete="off">
                </div>

                <div class="form-group">
                  <label for="current_pwd"><?= (!empty($userData['user_email'])) ? "Current password (required)" : ""; ?></label>
                  <input type="password" class="form-control" id="current_pwd" name="current_pwd" placeholder="Your current password" maxlength="50" autocomplete="off">
                </div>

              <?php endif; ?>

              <div class="form-group">
                <label for="website">Website</label>
                <input type="text" class="form-control" id="website" name="user_url" placeholder="Enter url" value="
<?= (isset($formData['user_url'])) ? safe_html($formData['user_url']) : ""; ?>
<?= (isset($userData['user_url'])) ? safe_html($userData['user_url']) : ""; ?>">
              </div>

              <div class="form-group">
                <label for="select2">Role</label>
                <?= (isset($userRole)) ? $userRole : ""; ?>
              </div>

              <?php
              if (empty($userData['user_email'])) :
              ?>
                <div class="checkbox">
                  <label for="1">
                    <input id="1" type="checkbox" name="send_user_notification" value="1"> Send the new user an email about their account
                  </label>
                </div>

              <?php
              endif;
              ?>

              <?php
              if (!empty($userData['user_level']) && $userData['user_level'] != 'administrator') :
              ?>
                <div class="checkbox">
                  <label for="user_banned">
                    <input id="user_banned" type="checkbox" name="user_banned" value="1" <?= (isset($userData['user_banned']) && $userData['user_banned'] == 1) ? "checked='checked'" : ""; ?>>
                    banned user
                  </label>
                </div>
              <?php
              endif;
              ?>

            </div>
            <!-- /.box-body -->

            <div class="box-footer">
              <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
              <input type="submit" class="btn btn-primary" name="userFormSubmit" value="<?= (($user_id) && ($user_id != '')) ? "Update Profile" : "Add New User"; ?>">
            </div>
          </form>

        </div>
        <!-- /.box -->
      </div>
      <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
  </section>

</div>