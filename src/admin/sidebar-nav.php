<?php 
function sidebar_navigation($module, $url, $level = null)
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
          <a href="<?= $url.'index.php?load=dashboard'; ?>"><i class="fa fa-dashboard"></i> 
          <span>Dashboard</span></a>
          </li>
        
<?php 
if ($level == 'administrator' || $level == 'manager' || $level == 'editor' 
    || $level == 'author' || $level == 'contributor') : ?>

        <li <?=($module == 'posts' || $module == 'topics') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="<?= $url.'index.php?load=posts'; ?>"><i class="fa fa-thumb-tack"></i> 
          <span>Posts</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'index.php?load=posts'; ?>">All Posts</a></li>
            <li><a href="<?= $url.'index.php?load=posts&action=newPost&Id=0'; ?>">Add New</a></li>
            <?php 
             if ($level == 'administrator' || $level == 'manager' || $level == 'editor') :
            ?>
            
             <li><a href="<?= $url.'index.php?load=topics'; ?>">Topics</a></li>

            <?php 
              endif;
            ?>

          </ul>
        </li>

<?php 
endif; 
?>

<?php 
if ($level == 'administrator' || $level == 'manager' || $level == 'editor' 
    || $level == 'author' || $level == 'contributor') : ?>

        <li <?=($module == 'medialib') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="<?= $url.'index.php?load=medialib'; ?>"><i class="fa fa-image"></i> 
          <span>Media</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          
          <ul class="treeview-menu">

            <li><a href="<?= $url.'index.php?load=medialib'; ?>">Library</a></li>
            <li><a href="<?= $url.'index.php?load=medialib&action=newMedia&Id=0'; ?>">Add New</a></li>
            
          </ul>

        </li>

<?php 
endif; 
?>

<?php 
if ($level == 'administrator' || $level == 'manager' || $level == 'editor' || $level == 'author') :
?>
        <li <?=($module == 'comments') ? 'class="active"' : 'class=""'; ?>>
        <a href="<?= $url.'index.php?load=comments'; ?>"><i class="fa fa-comments"></i> 
        <span>Comments</span></a>
        </li>

<?php 
endif; 
?>

<?php 
if ($level == 'administrator' || $level == 'manager') :
?>

         <li <?=($module == 'pages') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="<?= $url.'index.php?load=pages'; ?>"><i class="fa fa-clone"></i> 
          <span>Pages</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'index.php?load=pages'; ?>">All Pages</a></li>
            <li><a href="<?= $url.'index.php?load=pages&action=newPage&Id=0'; ?>">Add New</a></li>
          </ul>
        </li>
        
<?php endif; ?>


<?php  
if($level == 'administrator' || $level == 'manager') :
?>
       <li <?=($module == 'users') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="<?= $url.'index.php?load=users'; ?>"><i class="fa fa-user"></i> 
          <span>Users</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'index.php?load=users'; ?>">All Users</a></li>
            <li><a href="<?= $url.'index.php?load=users&action=newUser&Id=0'; ?>">Add New</a></li>
          </ul>
        </li>
<?php 
else :
?>
<li <?=($module == 'users') ? 'class="active"' : 'class=""'; ?>>
<a href="<?= $url.'index.php?load=users'; ?>"><i class="fa fa-user"></i> 
<span>My Profile</span></a>
</li>
<?php 
endif;
?>
        
<?php 
if($level == 'administrator' || $level == 'manager') :
?>
        <li <?=($module == 'templates' || $module == 'menu') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="#"><i class="fa fa-paint-brush"></i> 
          <span>Appearance</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'index.php?load=templates'; ?>">Themes</a></li>
            <li><a href="<?= $url.'index.php?load=menu'; ?>">Menu</a></li>
            <li><a href="<?= $url.'index.php?load=menu-child'; ?>">Sub Menu</a></li>
          </ul>
        </li>
<?php 
endif;
?>

<?php 
if($level == 'administrator') :
?>
        <li <?=($module == 'plugins') ? 'class="treeview active"' : 'class="treeview"'; ?>>
          <a href="<?= $url.'index.php?load=plugins'; ?>"><i class="fa fa-plug"></i> 
          <span>Plugins</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="<?= $url.'index.php?load=plugins'; ?>">Installed Plugins</a></li>
            <li><a href="<?= $url.'index.php?load=plugins&action=newPlugin&Id=0'; ?>">Add New</a></li>
          </ul>
        </li>

        <li <?=($module == 'settings') ? 'class="active"' : 'class=""'; ?>>
        <a href="<?= $url.'index.php?load=settings'; ?>"><i class="fa fa-sliders"></i> 
        <span>Settings</span></a>
        </li>
        
        <li class="header">PLUGIN NAVIGATION</li>
        <?=isset($plugin_navigation) ? $plugin_navigation : ""; ?>
<?php 
endif;
?>

      </ul>
      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
  </aside>
<?php 
}
?>