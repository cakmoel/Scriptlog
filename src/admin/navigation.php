<?php defined('SCRIPTLOG') || die("Direct access not permitted");?>
  
<header class="main-header">
    <a href="<?= app_url() . DS . APP_ADMIN . '/index.php?load=dashboard'; ?>" class="logo">
      <span class="logo-mini"><strong>S</strong></span>
      <span class="logo-lg"><strong>Script</strong>Log</span>
    </a>

    <nav class="navbar navbar-static-top" role="navigation">
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          
          <!-- Language Switcher -->
          <li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Language">
              <i class="fa fa-globe"></i>
              <span class="hidden-xs"><?= strtoupper(admin_get_locale()); ?></span>
            </a>
            <ul class="dropdown-menu">
              <li class="header"><?= admin_translate('nav.language_settings'); ?></li>
              <li>
                <ul class="menu">
                  <?php foreach (admin_get_available_locales() as $code => $name) : ?>
                  <li>
                    <a href="?lang=<?= $code; ?>">
                      <?php if (admin_get_locale() === $code) : ?>
                      <i class="fa fa-check text-success"></i>
                      <?php else : ?>
                      <i class="fa fa-globe text-muted"></i>
                      <?php endif; ?>
                      &nbsp;<?= safe_html($name); ?>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </li>
            </ul>
          </li>
          
          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-user-o fa-fw"></i>&nbsp;
              <span class="hidden-xs">
                <?= ((isset($user_level) && $user_level == 'administrator') && (isset($user_login)) ? $user_level : $user_login);?>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li class="user-header">
                <img src="<?= app_url() . DS . APP_ADMIN . DS . 'assets/dist/img/profilepict.png' ?>" class="img-circle" alt="User">
                <p id="checkOnline"></p>
              </li>
              <li class="user-body">
                <div class="row">
                  <div class="col-xs-4 text-center"></div>
                  <div class="col-xs-4 text-center"></div>
                  <div class="col-xs-4 text-center"></div>
                </div>
              </li>
              <li class="user-footer">
                <div class="pull-left">
                  <a href="<?=generate_request('index.php', 'get', ['users', ActionConst::EDITUSER, $user_id, $user_session])['link']; ?>" class="btn btn-default btn-flat" rel="noopener"><i class="fa fa-user fa-fw"></i>&nbsp; Profile</a>
                </div>
                <div class="pull-right">
                  <a href="<?=generate_request('index.php', 'get', ['logout', ActionConst::LOGOUT, do_logout_id()])['link'];?>" class="btn btn-default btn-flat" rel="noopener"><i class="fa fa-sign-out fa-fw"></i>&nbsp; Log Out</a>
                </div>
              </li>
            </ul>
          </li>
          
          <li>
            <a href="<?= app_url(); ?>" target="_blank" rel="noopener noreferrer" title="Visit Site"><i class="fa fa-home"></i></a>
          </li>
        </ul>
      </div>
    </nav>
</header>