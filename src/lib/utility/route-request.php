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
function route_request(array $config)
{
  
  $dispatcher = new Dispatcher();
  
  $connection = db_connect($config['db']['host'], $config['db']['user'], $config['db']['pass'], $config['db']['name']);

  if ((check_table($connection, 'tbl_users') == true) || (check_table($connection, 'tbl_user_token') == true)
  || (check_table($connection, 'tbl_topics') == true) || (check_table($connection, 'tbl_themes') == true)
  || (check_table($connection, 'tbl_settings') == true) || (check_table($connection, 'tbl_posts') == true)
  || (check_table($connection, 'tbl_post_topic') == true) || (check_table($connection, 'tbl_plugin') == true)
  || (check_table($connection, 'tbl_menu_child') == true) || (check_table($connection, 'tbl_menu') == true)
  || (check_table($connection, 'tbl_mediameta') == true) || (check_table($connection, 'tbl_media') == true)
  || (check_table($connection, 'tbl_comments') == true) || (check_table($connection, 'tbl_comment_reply') == true)) {

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
    
  } else {
    
    return $dispatcher -> dispatch();

  }

}