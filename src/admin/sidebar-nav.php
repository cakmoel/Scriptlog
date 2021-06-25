<?php 
function sidebar_navigation($module, $url, $user_id = null, $user_session = null)
{  
?>

<!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

      <!-- Sidebar user panel (optional) -->
      <div class="user-panel"></div>

      <!-- Sidebar Menu -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
        <!-- Optionally, you can add icons to the links -->
        <li <?=($module == 'dashboard') ? 'class="active"' : 'class=""'; ?>>
          <a href="<?= $url.'/'.generate_request('index.php', 'get', ['dashboard'], false)['link']; ?>"><i class="fa fa-dashboard"></i> 
          <span>Dashboard</span></a>
          </li>
        
<?php if (access_control_list()) : ?>

    <li <?=($module == 'posts' || $module == 'topics') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-thumb-tack"></i> 
          <span>Posts</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['posts'], false)['link']; ?>">All Posts</a></li>
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['posts', ActionConst::NEWPOST, 0])['link']; ?>">Add New</a></li>
            
            <?php if (access_control_list(ActionConst::TOPICS)) : ?>
            
              <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['topics'], false)['link']; ?>">Topics</a></li>

            <?php endif; ?>

          </ul>
    </li>

<?php endif; if (access_control_list(ActionConst::MEDIALIB)) : ?>

        <li <?=($module == 'medialib') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-image"></i> <span>Media</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          
          <ul class="treeview-menu">

            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['medialib', ActionConst::MEDIALIB], false)['link']; ?>">Library</a></li>
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['medialib', ActionConst::NEWMEDIA, 0])['link']; ?>">Add New</a></li>
            
          </ul>

        </li>

<?php endif; if (access_control_list(ActionConst::PAGES)) : ?>

         <li <?=($module == 'pages') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-file"></i> 
          <span>Pages</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['pages', ActionConst::PAGES], false)['link']; ?>">All Pages</a></li>
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['pages', ActionConst::NEWPAGE, 0])['link']; ?>">Add New</a></li>
          </ul>
        </li>
        
<?php endif; if (access_control_list(ActionConst::COMMENTS)) : ?>

        <li <?=($module == 'comments') ? 'class="active"' : 'class=""'; ?>>
        <a href="<?= $url.'/'.generate_request('index.php', 'get', ['comments', ActionConst::COMMENTS], false)['link']; ?>"><i class="fa fa-comments"></i> 
        <span>Comments</span></a>
        </li>

<?php endif; if(access_control_list(ActionConst::USERS)) : ?>

       <li <?=($module == 'users') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-user"></i> 
          <span>Users</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['users'], false)['link']; ?>">All Users</a></li>
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['users', ActionConst::NEWUSER, 0, sha1(app_key())])['link']; ?>">Add New</a></li>
          </ul>
        </li>

<?php else : ?>

<li <?=($module == 'users') ? 'class="active"' : 'class=""'; ?>>
<a href="<?= generate_request('index.php', 'get', ['users', 'editUser', $user_id, $user_session])['link']; ?>"><i class="fa fa-user"></i> 
<span>My Profile</span></a>
</li>

<?php endif; if(access_control_list(ActionConst::THEMES)) : ?>

        <li <?=($module == 'templates' || $module == 'menu') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-paint-brush"></i> 
          <span>Appearance</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['templates'], false)['link']; ?>">Themes</a></li>
               <?php 
                  if (access_control_list(ActionConst::NAVIGATION)) :
               ?>
                    <li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['menu'], false)['link']; ?>">Menus</a></li>
                    
                <?php endif; ?>
          </ul>
        </li>

<?php endif; if(access_control_list(ActionConst::CONFIGURATION)): ?>

<li <?=($module == 'option-general' || $module == 'option-permalink' || $module == 'option-reading') ? 'class="treeview active"' : 'class="treeview"'; ?>>
<a href="#"><i class="fa fa-sliders"></i> <span>Settings</span>
<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
</a>
<ul class="treeview-menu">
<li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['option-general', ActionConst::GENERAL_CONFIG, 0])['link']; ?>">General</a></li>
<li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['option-reading', ActionConst::READING_CONFIG, 0])['link']; ?>">Reading</a></li>
<li><a href="<?= $url.'/'.generate_request('index.php', 'get', ['option-permalink', ActionConst::PERMALINK_CONFIG, 0])['link']; ?>">Permalink</a></li>
</ul>
</li>

<?php endif; if(access_control_list(ActionConst::PLUGINS)) : ?>

<li <?=($module == 'plugins') ? 'class="active"' : 'class=""'; ?>>
<a href="<?= $url.'/'.generate_request('index.php', 'get', ['plugins', ActionConst::PLUGINS], false)['link']; ?>">
<i class="fa fa-plug"></i> <span>Plugins</span>
</a>
</li>
        
<?php endif; ?>

</ul>
<!-- /.sidebar-menu -->
</section>
    <!-- /.sidebar -->
</aside>

<?php } // end of sidebar_navigation function ?>