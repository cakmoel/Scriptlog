 <?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

 require __DIR__ . '/sidebar-nav.php';
 
 ?>
 
<header class="main-header">
    <!-- Logo -->
    <a href="<?= app_url().DS.APP_ADMIN.'/index.php?load=dashboard'?>" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>S</b></span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>Script</b>Log</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
      </a>
      <!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          
          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <!-- The user image in the navbar-->
              <i class="fa fa-user-o"></i>
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span class="hidden-xs"><?=(isset($user_level) && $user_level == 'administrator') ? $user_level : $user_login; ?></span>
            </a>
            <ul class="dropdown-menu">
             
              <!-- Menu Body -->
           <li class="user-body">
                <div class="row">
                  <div class="col-xs-4 text-center">
                    
                  </div>
                  <div class="col-xs-4 text-center">
                    
                  </div>
                  <div class="col-xs-4 text-center">
                    
                  </div>
                </div>
                <!-- /.row -->
              </li>
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a href="<?=generate_request('index.php', 'get', ['users', ActionConst::EDITUSER, $user_id, $user_session])['link']; ?>" class="btn btn-default btn-flat"><i class="fa fa-user fa-fw"></i>Profile</a>
                </div>
                <div class="pull-right">
                  <a href="<?=generate_request('index.php', 'get', ['logout', ActionConst::LOGOUT, do_logout_id()])['link'];?>" class="btn btn-default btn-flat"><i class="fa fa-sign-out fa-fw"></i>Sign out</a>
                </div>
              </li>
            </ul>
          </li>
          <!-- Control Sidebar Toggle Button -->
          <li>
            <a href="<?= app_url(); ?>" target="_blank" rel="noopener noreferrer" title="Visit Site" ><i class="fa fa-home"></i></a>
          </li>
        </ul>
      </div>
    </nav>

</header>  
<!-- .Main Header -->
  
<?php 
  echo sidebar_navigation($breadCrumbs, $currentURL, $user_id, $user_session);
?>