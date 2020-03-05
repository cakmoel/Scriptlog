<?php
/**
 * Route Request Function
 * this function will be called in index.php file
 * on top of our site directory
 * 
 * @category Function 
 * @return mixed
 * 
 */
function route_request()
{
  
  $dispatcher = new Dispatcher();
  
  if ((check_table('tbl_users') == false) || (check_table('tbl_user_token') == false)
      || (check_table('tbl_topics') == false) || (check_table('tbl_themes') == false)
      || (check_table('tbl_settings') == false) || (check_table('tbl_posts') == false)
      || (check_table('tbl_post_topic') == false) || (check_table('tbl_plugin') == false)
      || (check_table('tbl_menu_child') == false) || (check_table('tbl_menu') == false)
      || (check_table('tbl_mediameta') == false) || (check_table('tbl_media') == false)
      || (check_table('tbl_comments') == false) || (check_table('tbl_comment_reply') == false)
      || (check_table('tbl_login_attempt') == false)) {

      return $dispatcher -> dispatch();
    
  } else {
    
    if ((is_dir(__DIR__ . '/../../install/')) && (file_exists(__DIR__ . '/../../install/install.php'))) {

      header($_SERVER['SERVER_PROTOCOL']." 200 Found");
      header("Status: 200 Found");
      header("Location: ".APP_PROTOCOL . '://' . APP_HOSTNAME . dirname($_SERVER['PHP_SELF']) . DS . 'install/install.php');
      exit();

    } else {

     header($_SERVER['SERVER_PROTOCOL']." 410 Gone");
     header("Status: 410 Gone");
     header("Retry-After: 300");
     exit("The content has been permanently deleted from server");
   
    }

  }

}