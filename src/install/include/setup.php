<?php

/**
 * File setup.php
 * 
 * These collections of functions to setup installation and write config.php file
 * 
 * @category Installation file
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
function current_url() // returning current url
{

  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") ? "https" : "http";

  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

  return $scheme . "://" . $host . dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR;
}

/**
 * make_connection()
 * 
 * @param string $host
 * @param string $username
 * @param string $passwd
 * @return object
 * 
 */
function make_connection($host, $username, $passwd, $dbname)
{

  $connect = new mysqli($host, $username, $passwd, $dbname);

  if ($connect->connect_errno) {

    printf("Failed to connect to MySQL: (" . $connect->connect_errno . ") " . $connect->connect_error, E_USER_ERROR);
    exit();
  }

  $driver = new mysqli_driver();

  $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

  return $connect;
}

/**
 * close_connection()
 * 
 * closing database connection
 * 
 * @param object $link
 * 
 */
function close_connection($link)
{
  $link->close();
}

/**
 * is_table_exists
 * checking whether table exists or not
 * 
 * @category installation functionality
 * @param object $link
 * @param string $table
 * @param numeric $counter
 * 
 */
function is_table_exists($link, $table, $counter = 0)
{

  if ($link instanceof mysqli) {

    $counter++;

    $check = $link->query("SHOW TABLES LIKE '" . $table . "'");

    return (($check) && ($check->num_rows > 0) ? true : false);
  }
}

/**
 * function check_dbtable
 * 
 * @param string $link
 * @param string $table
 * 
 */
function check_dbtable($link, $table)
{
  if (isset($link) && isset($table)) {
    return (!is_table_exists($link, $table)) ? true : false;
  }
}

/**
 * Install Database Table Function
 * 
 * @param object $link
 * @param string $user_login
 * @param string $user_pass
 * @param string $user_email
 * @param string $key
 * 
 */
function install_database_table($link, $protocol, $server_host, $user_login, $user_pass, $user_email, $key)
{

  require __DIR__ . '/dbtable.php';

  // Users  
  $date_registered = date('Y-m-d H:i:s');
  $user_session    = md5(uniqid());
  $shield_pass     = password_hash(base64_encode(hash('sha384', $user_pass, true)), PASSWORD_DEFAULT);
  $user_level      = 'administrator';

  // Theme 
  $theme_title     = "Bootstrap Blog";
  $theme_desc      = "Simple yet clean design for personal blog";
  $theme_designer  = "Ondrej - bootstrapious";
  $theme_directory = "blog";
  $theme_status    = "Y";

  // Setting App Key
  $setting_name_key = "app_key";

  // Setting App URL
  $setting_name_url  = "app_url";
  $setting_value_url = setup_base_url($protocol, $server_host);

  // Setting Site_Name
  $site_name  = "site_name";
  $site_name_value = "Scriptlog 1.0";

  // Setting Site_Tagline
  $site_tagline = "site_tagline";
  $site_tagline_value = "Just another personal weblog";

  // Setting Site_Description
  $site_description = "site_description";
  $site_description_value = "Scriptlog power your blog";

  // Setting Site_Keywords
  $site_keywords = "site_keywords";
  $site_keywords_value = "Weblog, Personal blog, Blogware";

  // Setting Site_Email
  $site_email = "site_email";

  // setting post per page
  $post_per_page = "post_per_page";
  $post_per_page_value = "10";

  // setting post per rss
  $post_per_rss = "post_per_rss";
  $post_per_rss_value = "10";

  // setting post per archive
  $post_per_archive = "post_per_archive";
  $post_per_archive_value = "10";

  // setting comment per post
  $comment_per_post = "comment_per_post";
  $comment_per_post_value = "10";

  // Setting Permalink
  $permalink_key = "permalink_setting";
  $permalink_value = array('rewrite' => 'no', 'server_software' => check_web_server()['WebServer']);
  $store_permalink_value = json_encode($permalink_value);

  // Setting Timezone
  $timezone_key = "timezone_setting";
  $timezone_value = array('timezone_identifier' => date_default_timezone_get());
  $store_timezone_value = json_encode($timezone_value);

  if ($link instanceof mysqli) {
    #create users table
    $link->query($tblUser);

    #save administrator
    $createAdmin = $link->prepare($saveAdmin);

    if (false !== $createAdmin) {

      $createAdmin->bind_param("ssssss", $user_login, $user_email, $shield_pass, $user_level, $date_registered, $user_session);
      $createAdmin->execute();
    }

    if ($link->insert_id && $createAdmin->affected_rows > 0) {

      // create other database tables
      $link->query($tblUserToken);
      $link->query($tblPost);
      $link->query($tblTopic);
      $link->query($tblPostTopic);
      $link->query($tblComment);
      $link->query($tblLoginAttempt);
      $link->query($tblMenu);
      $link->query($tblMedia);
      $link->query($tblMediaMeta);
      $link->query($tblMediaDownload);
      $link->query($tblPlugin);
      $link->query($tblSetting);
      $link->query($tblTheme);

      // insert configuration - app_key
      $recordAppKey = $link->prepare($saveAppKey);
      $recordAppKey->bind_param('ss', $setting_name_key, $key);
      $recordAppKey->execute();

      // insert configuration - app_url
      $recordAppURL = $link->prepare($saveAppURL);
      $recordAppURL->bind_param('ss', $setting_name_url, $setting_value_url);
      $recordAppURL->execute();

      // insert configuration - site_name
      $recordAppSiteName = $link->prepare($saveSiteName);
      $recordAppSiteName->bind_param('ss', $site_name, $site_name_value);
      $recordAppSiteName->execute();

      // insert configuration - site_tagline
      $recordAppSiteTagline = $link->prepare($saveSiteTagline);
      $recordAppSiteTagline->bind_param('ss', $site_tagline, $site_tagline_value);
      $recordAppSiteTagline->execute();

      // insert configuration - site_description
      $recordAppSiteDescription = $link->prepare($saveSiteDescription);
      $recordAppSiteDescription->bind_param('ss', $site_description, $site_description_value);
      $recordAppSiteDescription->execute();

      // insert configuration - site_keywords
      $recordAppSiteKeywords = $link->prepare($saveSiteKeywords);
      $recordAppSiteKeywords->bind_param('ss', $site_keywords, $site_keywords_value);
      $recordAppSiteKeywords->execute();

      // insert configuration - site_email
      $recordAppSiteEmail = $link->prepare($saveSiteEmail);
      $recordAppSiteEmail->bind_param('ss', $site_email, $user_email);
      $recordAppSiteEmail->execute();

      // insert configuration - posts per page 
      $recordPostPerPage = $link->prepare($savePostPerPage);
      $recordPostPerPage->bind_param('ss', $post_per_page, $post_per_page_value);
      $recordPostPerPage->execute();

      // insert configuration post per rss
      $recordPostPerRSS = $link->prepare($savePostPerRSS);
      $recordPostPerRSS->bind_param('ss', $post_per_rss, $post_per_rss_value);
      $recordPostPerRSS->execute();

      // insert configuration post per archive
      $recordPostPerArchive = $link->prepare($savePostPerArchive);
      $recordPostPerArchive->bind_param('ss', $post_per_archive, $post_per_archive_value);
      $recordPostPerArchive->execute();

      // insert configuration comment per post
      $recordCommentPerPost = $link->prepare($saveCommentPerPost);
      $recordCommentPerPost->bind_param('ss', $comment_per_post, $comment_per_post_value);
      $recordCommentPerPost->execute();

      // insert configuration - permalinks
      $recordPermalinks = $link->prepare($savePermalinks);
      $recordPermalinks->bind_param('ss', $permalink_key, $store_permalink_value);
      $recordPermalinks->execute();

      // insert configuration - timezone
      $recordTimezone = $link->prepare($saveTimezone);
      $recordTimezone->bind_param('ss', $timezone_key, $store_timezone_value);
      $recordTimezone->execute();

      // insert default theme
      $recordTheme = $link->prepare($saveTheme);
      $recordTheme->bind_param('sssss', $theme_title, $theme_desc, $theme_designer, $theme_directory, $theme_status);
      $recordTheme->execute();

      if ($recordAppKey->affected_rows > 0) {

        $link->close();
      }
    }
  }
}

/**
 * write_config_file
 * 
 * Write Config File Function
 * 
 * @param string $protocol
 * @param string $server_name
 * @param string $dbhost
 * @param string $dbuser
 * @param string $dbpassword
 * @param string $dbname
 * @param string $email
 * @param string $key
 * @throws Exception
 * 
 */
function write_config_file($protocol, $server_name, $dbhost, $dbuser, $dbpassword, $dbname, $email, $key)
{

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  $link = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

  if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error(), E_USER_ERROR);
    exit();
  }

  $configuration = false;

  if (isset($_SESSION['install']) && $_SESSION['install'] === true) {

    $getAppKey = "SELECT ID, setting_name, setting_value 
                 FROM tbl_settings USE INDEX(setting_value) 
                 WHERE setting_value = '$key'";

    $row = mysqli_fetch_assoc(mysqli_query($link, $getAppKey));

    $app_key = generate_license(substr($row['setting_value'], 0, 6));

    $updateAppKey = "UPDATE tbl_settings SET setting_value = '$app_key'
                    WHERE setting_name = 'app_key' 
                    AND ID = {$row['ID']} LIMIT 1";

    mysqli_query($link, $updateAppKey);
    mysqli_close($link);

    $configFile = '<?php  
    
    return [' . "
                    
            'db' => [

                  'host' => '" . addslashes($dbhost) . "',
                  'user' => '" . addslashes($dbuser) . "',
                  'pass' => '" . addslashes($dbpassword) . "',
                  'name' => '" . addslashes($dbname) . "'
                  
                ],
        
            'app' => [

                   'url'   => '" . addslashes(setup_base_url($protocol, $server_name)) . "',
                   'email' => '" . addslashes($email) . "',
                   'key'   => '" . addslashes($app_key) . "'
                   
                ]

        ];";

    if (isset($_SESSION['token'])) {

      file_put_contents(__DIR__ . '/../../config.php', $configFile, FILE_APPEND | LOCK_EX);

      $configuration = true;
    }
  }

  return $configuration;
}

/**
 * remove_bad_characters()
 * 
 * Remove Bad Characters
 * 
 * @link https://stackoverflow.com/questions/14114411/remove-all-special-characters-from-a-string#14114419
 * @link https://stackoverflow.com/questions/19167432/strip-bad-characters-from-an-html-php-contact-form
 * @param string $str_words
 * @param boolean $escape
 * @param string $level
 * @return string
 * 
 */
function remove_bad_characters($str_words, $host, $user, $password, $database, $escape = false, $level = 'high')
{

  $str_words = escapeHTML(strip_tags($str_words));

  if ($level == 'low') {

    $bad_string = array('drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');
  } elseif ($level == 'medium') {

    $bad_string = array('select', 'drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');
  } else {

    $bad_string = array('select', 'drop', '--', 'insert', 'xp_', '%20union%20', '/*', '*/union/*', '+union+', 'load_file', 'outfile', 'document.cookie', 'onmouse', '<script', '<iframe', '<applet', '<meta', '<style', '<form', '<img', '<body', '<link', '_GLOBALS', '_REQUEST', '_GET', '_POST', 'include_path', 'prefix', 'http://', 'https://', 'ftp://', 'smb://', 'onmouseover=', 'onmouseout=');
  }

  for ($i = 0; $i < count($bad_string); $i++) {

    $str_words = str_replace($bad_string[$i], '', $str_words);
  }

  if ($escape) {

    $link = mysqli_connect($host, $user, $password, $database);
    $str_words = mysqli_real_escape_string($link, $str_words);
  }

  return $str_words;
}

/** 
 * Escape HTML Function
 * 
 * @see https://www.taniarascia.com/create-a-simple-database-app-connecting-to-mysql-with-php/
 * @see https://ilovephp.jondh.me.uk/en/tutorial/make-your-own-blog
 * @param string $html
 * @return string
 * 
 */
function escapeHTML($html)
{
  return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

/**
 * Generate License Function
 * to create serial generation of license key with php
 * 
 * @link https://stackoverflow.com/questions/3687878/serial-generation-with-php
 * @param string $suffix
 * @return string
 * 
 */
function generate_license($suffix = null)
{

  // Default tokens contain no "ambiguous" characters: 1,i,0,o
  if (isset($suffix)) {
    // Fewer segments if appending suffix
    $num_segments = 3;
    $segment_chars = 6;
  } else {
    $num_segments = 4;
    $segment_chars = 5;
  }

  $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $license_string = '';

  // Build Default License String
  for ($i = 0; $i < $num_segments; $i++) {

    $segment = '';
    for ($j = 0; $j < $segment_chars; $j++) {
      $segment .= $tokens[rand(0, strlen($tokens) - 1)];
    }

    $license_string .= $segment;
    if ($i < ($num_segments - 1)) {
      $license_string .= '-';
    }
  }

  // If provided, convert Suffix
  if (isset($suffix)) {

    if (is_numeric($suffix)) {   // Userid provided

      $license_string .= '-' . strtoupper(base_convert($suffix, 10, 36));
    } else {

      $long = sprintf("%u\n", ip2long($suffix), true);

      if ($suffix === long2ip($long)) {

        $license_string .= '-' . strtoupper(base_convert($long, 10, 36));
      } else {

        $license_string .= '-' . strtoupper(str_ireplace(' ', '-', $suffix));
      }
    }
  }

  return $license_string;
}

/**
 * convert_memory_used()
 * 
 * Convert Memory Used Function
 * Format size memory usage onto b, kb, mb, gb, tb and pb
 * 
 * @param int|float $size
 * @return mixed
 * 
 */
function convert_memory_used($size)
{
  $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
  return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

/**
 * setup_base_url()
 * 
 * Setup base URL
 *
 * @param string $protocol
 * @param string $server_host
 * @return string
 * 
 */
function setup_base_url($protocol, $server_host)
{
  $base_url = $protocol . '://' . $server_host . dirname(dirname($_SERVER['PHP_SELF']));

  if (substr($base_url, -1) === DIRECTORY_SEPARATOR) {

    return rtrim($base_url, DIRECTORY_SEPARATOR);
  } else {

    return $base_url;
  }
}

/**
 * purge_installation()
 * 
 * Clean up installation procedure
 * 
 */
function purge_installation()
{

  $length = 16;

  if (is_readable(__DIR__ . '/../../config.php')) {

    if (function_exists("random_bytes")) {

      $bytes = random_bytes(ceil($length / 2));
    } elseif (function_exists("openssl_random_pseudo_bytes")) {

      $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
    } else {

      trigger_error("no cryptographically secure random function available", E_USER_NOTICE);
    }

    $disabled = APP_PATH . substr(bin2hex($bytes), 0, $length) . '.log';

    if ((is_writable(APP_PATH)) && (rename(__DIR__ . '/../index.php', $disabled))) {

      $clean_installation = '<?php ';

      file_put_contents(__DIR__ . '/../index.php', $clean_installation, LOCK_EX);

      session_start();
      session_unset();
      session_destroy();
      session_write_close();
      setcookie(session_name(), '', 0, '/');
      session_regenerate_id(true);
      
    }
  }
}
