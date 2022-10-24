<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      <?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>Control Panel</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="index.php?load=users">User</a></li>
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

            <img class="profile-user-img img-responsive img-circle" src="<?= app_url() . DS . APP_ADMIN . DS . 'assets/dist/img/profilepict.png' ?>" alt="User profile picture">

            <h2 class="profile-username text-center"><?= (isset($userData['user_login'])) ? safe_html($userData['user_login']) : ""; ?></h2>

            <p class="text-muted text-center"><?= (isset($userData['user_fullname'])) ? safe_html($userData['user_fullname']) : ""; ?></p>

            <ul class="list-group list-group-unbordered">

              <li class="list-group-item">

                <?php
                if (isset($userData['user_url']) && !empty($userData['user_url']) && $userData['user_url'] != '#') :


                ?>
                  <b> <?= safe_html($userData['user_url']); ?></b>
                  <a class="pull-right" href="<?= safe_html($userData['user_url']); ?>" target="_blank" rel="noopener noreferrer" title="site_address"><i class="fa fa-external-link"></i> </a>

                <?php
                endif;
                ?>

              </li>

            </ul>

          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->

        <!-- About Me Box -->
        <div class="box box-primary">
          <div class="box-header with-border">
            <h2 class="box-title">My profile</h2>
          </div>
          <!-- /.box-header -->
          <div class="box-body">

            <strong><i class="fa fa-user margin-r-5"></i> Role</strong>

            <p class="text-muted">
              <?= (isset($userData['user_level'])) ? safe_html($userData['user_level']) : ""; ?>
            </p>
            <hr>

            <strong><i class="fa fa-envelope margin-r-5"></i> Email Address</strong>

            <p class="text-muted"><?= (isset($userData['user_email'])) ? safe_html($userData['user_email']) : ""; ?></p>

            <hr>

            <strong><i class="fa fa-calendar-check-o margin-r-5"></i> Registered</strong>

            <p>
              <?= read_datetime(safe_html($userData['user_registered'])); ?>
            </p>


          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- /.col md-3-->
      <div class="col-md-9">
        <div class="box box-primary">
          <div class="box-header with-border"></div>
          <!-- /.box-header -->

          <?php
          if (isset($errors)) :
          ?>
            <div class="alert alert-danger alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <h4><i class="icon fa fa-warning"></i> Invalid form data!</h4>
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
              <h4><i class="icon fa fa-check"></i>
                <?php
                foreach ($status as $s) :

                  echo $s;

                endforeach;

                ?>
              </h4>
            </div>

          <?php
          endif;

          $action = isset($formAction) ? $formAction : null;
          $user_id = isset($userData['ID']) ? safe_html((int)$userData['ID']) : 0;
          $session_id = isset($userData['user_session']) ? safe_html($userData['user_session']) : null;
          ?>

          <form name="scriptlogForm" method="post" action="<?= generate_request('index.php', 'post', ['users', $action, $user_id, $session_id])['link']; ?>" role="form" autocomplete="off">
            <input type="hidden" name="user_id" value="<?= $user_id; ?>" />
            <input type="hidden" name="session_id" value="<?= $session_id; ?>" />

            <div class="box-body">
              <?php
              if ((isset($userData['user_login'])) && ($userData['user_login'] != '')) :
              ?>
                <div class="form-group">
                  <label for="username">Username</label>
                  <input type="text" class="form-control" id="username" name="user_login" value="
<?= ($userData['user_login']) ? safe_html($userData['user_login']) : ""; ?>" <?= (!empty($userData['user_login'])) ? "disabled" : ""; ?>>
                  <p class="help-block">This username can not be changed.</p>
                </div>
              <?php endif;  ?>

              <div class="form-group">
                <label for="fullname">Fullname</label>
                <input type="text" class="form-control" id="fullname" name="user_fullname" placeholder="Enter real name" value="
<?= (!empty($userData['user_fullname'])) ? safe_html($userData['user_fullname']) : ""; ?>
<?= (isset($formData['user_fullname'])) ? safe_html($formData['user_fullname']) : ""; ?>">
              </div>

              <div class="form-group">
                <label for="email">Email (required)</label>
                <input type="email" class="form-control" id="email" name="user_email" placeholder="something@example.com" value="
<?= (isset($userData['user_email']) && $userData['user_email'] != '#') ? safe_html($userData['user_email']) : ""; ?>
<?= (isset($formData['user_email'])) ? safe_html($formData['user_email']) : ""; ?>" required>
              </div>

              <div class="form-group">
                <label for="passwd"><?= (!empty($userData['user_email'])) ? "New password" : "Password(required)"; ?></label>
                <input type="password" class="form-control" id="passwd" name="user_pass" placeholder="Enter password" maxlength="50" autocomplete="off">
              </div>

              <?php if (!empty($userData['user_email'])) : ?>

                <div class="form-group">
                  <label for="confirm_pwd"><?= (!empty($userData['user_email'])) ? "Confirm new passowrd (required)" : ""; ?></label>
                  <input type="password" class="form-control" id="confirm_pwd" name="user_pass2" placeholder="Confirm password" maxlength="50" autocomplete="off">
                </div>

                <div class="form-group">
                  <label for="current_pwd"><?= (!empty($userData['user_email'])) ? "Current password (required)" : ""; ?></label>
                  <input type="password" class="form-control" id="current_pwd" name="current_pwd" placeholder="Your current password" maxlength="50" autocomplete="off">
                </div>

              <?php endif; ?>

              <div class="form-group">
                <label for="website">Website</label>
                <input type="text" class="form-control" id="website" name="user_url" placeholder="Enter url" value="<?= (isset($formData['user_url'])) ? safe_html($formData['user_url']) : ""; ?>
<?= (isset($userData['user_url'])) ? safe_html($userData['user_url']) : ""; ?>">
              </div>

            </div>
            <!-- /.box-body -->

            <div class="box-footer">
              <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
              <input type="submit" name="userFormSubmit" class="btn btn-primary" value="<?= (($user_id) && ($user_id != '')) ? "Update Profile" : "Save Changes"; ?>">
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