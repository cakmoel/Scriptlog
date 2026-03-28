<?php

/**
 * File dbtable.php
 * 
 * @category Installation file
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */

function get_table_definitions($prefix = '') 
{
  $tblUser = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_users (
  ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
  user_login VARCHAR(60) NOT NULL UNIQUE,
  user_email VARCHAR(100) NOT NULL UNIQUE,
  user_pass VARCHAR(255) NOT NULL,
  user_level VARCHAR(20) NOT NULL,
  user_fullname VARCHAR(120) DEFAULT NULL,
  user_url VARCHAR(100) DEFAULT NULL,
  user_registered datetime NOT NULL DEFAULT '1988-07-01 08:00:00',
  user_activation_key varchar(255) NOT NULL DEFAULT '',
  user_reset_key varchar(255) DEFAULT NULL,
  user_reset_complete VARCHAR(3) DEFAULT 'No',
  user_session VARCHAR(255) NOT NULL,
  user_banned TINYINT NOT NULL DEFAULT '0',
  user_signin_count INT NOT NULL DEFAULT '0',
  user_locked_until DATETIME NULL,
  login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblUserToken = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_user_token (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_login VARCHAR(60) NOT NULL,
  pwd_hash VARCHAR(255) NOT NULL,
  selector_hash VARCHAR(255) NOT NULL,
  is_expired INT(11) NOT NULL DEFAULT '0',
  expired_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblLoginAttempt = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_login_attempt (
  ip_address VARCHAR(255) NOT NULL,
  login_date datetime NOT NULL DEFAULT '1989-06-12 12:00:00'
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblPost = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_posts (
  ID BIGINT(20) unsigned NOT NULL auto_increment,
  media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
  post_author BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
  post_date datetime NOT NULL DEFAULT '1989-06-12 12:00:00',
  post_modified datetime DEFAULT NULL,
  post_title tinytext NOT NULL,
  post_slug VARCHAR(255) NOT NULL,
  post_content longtext NOT NULL,    
  post_summary mediumtext DEFAULT NULL,
  post_status varchar(20) NOT NULL DEFAULT 'publish',
  post_visibility varchar(20) NOT NULL DEFAULT 'public',
  post_password varchar(255) DEFAULT NULL,
  post_tags text DEFAULT NULL, 
  post_headlines INT(5) NOT NULL DEFAULT '0',
  post_sticky INT(5) NOT NULL DEFAULT '0',   
  post_type varchar(120) NOT NULL DEFAULT 'blog',   
  comment_status varchar(20) NOT NULL DEFAULT 'open',
  passphrase varchar(255) DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY author_id(post_author),
  KEY post_media(media_id),
  KEY idx_post_slug(post_slug),
  FULLTEXT KEY (post_tags, post_title, post_content)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblMedia = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_media (    
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,   
  media_filename VARCHAR(200) DEFAULT NULL,    
  media_caption VARCHAR(200) DEFAULT NULL,    
  media_type VARCHAR(90) NOT NULL,    
  media_target VARCHAR(20) NOT NULL DEFAULT 'blog',    
  media_user VARCHAR(20) NOT NULL,    
  media_access VARCHAR(10) NOT NULL DEFAULT 'public',    
  media_status INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblMediaMeta = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_mediameta (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,    
  media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0', 
  meta_key VARCHAR(255) NOT NULL,    
  meta_value LONGTEXT DEFAULT NULL,    
  PRIMARY KEY (ID),
  KEY media_id(media_id),
  KEY meta_key(meta_key(191))
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblMediaDownload = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_media_download (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  media_id BIGINT(20) UNSIGNED NOT NULL,
  media_identifier CHAR(36) NOT NULL,
  before_expired VARCHAR(50) NOT NULL,
  ip_address VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  KEY id_media(media_id),
  UNIQUE KEY media_identifier(media_identifier)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblTopic = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_topics (
  ID BIGINT(20) unsigned NOT NULL auto_increment,  
  topic_title varchar(255) NOT NULL,    
  topic_slug varchar(255) NOT NULL,    
  topic_status enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (ID),
  KEY idx_topic_slug(topic_slug)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblPostTopic = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_post_topic ( 
  post_id BIGINT(20) unsigned NOT NULL,    
  topic_id BIGINT(20) unsigned NOT NULL,
  PRIMARY KEY(post_id, topic_id)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblComment = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_comments (
  ID BIGINT(20) unsigned NOT NULL auto_increment,
  comment_post_id BIGINT(20) unsigned NOT NULL,
  comment_parent_id BIGINT(20) NOT NULL DEFAULT '0',
  comment_author_name VARCHAR(60) NOT NULL,
  comment_author_ip VARCHAR(100) NOT NULL,
  comment_author_email VARCHAR(100) DEFAULT NULL,
  comment_content text NOT NULL,
  comment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  comment_date datetime NOT NULL DEFAULT '1988-07-01 08:00:00',
  PRIMARY KEY (ID),
  KEY id_comment_post(comment_post_id)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblMenu = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_menu (
   ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
   menu_label VARCHAR(200) NOT NULL,
   menu_link VARCHAR(255) DEFAULT NULL,
   menu_status ENUM('Y', 'N') NOT NULL DEFAULT 'N',
   menu_visibility VARCHAR(20) NOT NULL DEFAULT 'public',
   parent_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
   menu_sort INT(11) UNSIGNED NOT NULL DEFAULT '0',
   PRIMARY KEY (ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblPlugin = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_plugin (
  ID BIGINT(20) unsigned NOT NULL auto_increment,
  plugin_name VARCHAR(100) NOT NULL,
  plugin_link VARCHAR(255) NOT NULL DEFAULT '#',
  plugin_directory VARCHAR(100) NOT NULL,
  plugin_desc tinytext,
  plugin_status enum('Y','N') NOT NULL DEFAULT 'N',
  plugin_level VARCHAR(20) NOT NULL,
  plugin_sort INT(5) DEFAULT NULL,
  PRIMARY KEY(ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblSetting = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_settings (
  ID INT(11) unsigned NOT NULL AUTO_INCREMENT,
  setting_name VARCHAR(255) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  PRIMARY KEY(ID),
  KEY setting_name(setting_name(191)),
  KEY setting_value(setting_value(191))
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblTheme = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_themes (
  ID INT(11) unsigned NOT NULL auto_increment,
  theme_title VARCHAR(100) NOT NULL,
  theme_desc tinytext,
  theme_designer VARCHAR(90) NOT NULL,
  theme_directory VARCHAR(100) NOT NULL,
  theme_status enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY(ID)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblConsents = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_consents (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  consent_type VARCHAR(50) NOT NULL,
  consent_status ENUM('accepted','rejected') NOT NULL,
  consent_ip VARCHAR(45) NOT NULL,
  consent_user_agent VARCHAR(255) DEFAULT NULL,
  consent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  consent_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  KEY consent_type(consent_type),
  KEY consent_date(consent_date)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblDataRequests = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_data_requests (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  request_type VARCHAR(50) NOT NULL,
  request_email VARCHAR(100) NOT NULL,
  request_status ENUM('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending',
  request_ip VARCHAR(45) NOT NULL,
  request_note TEXT DEFAULT NULL,
  request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  request_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  request_completed_date DATETIME DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY request_type(request_type),
  KEY request_status(request_status),
  KEY request_email(request_email)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblPrivacyLogs = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_privacy_logs (
  ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  log_action VARCHAR(50) NOT NULL,
  log_type VARCHAR(50) NOT NULL,
  log_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
  log_email VARCHAR(100) DEFAULT NULL,
  log_details TEXT DEFAULT NULL,
  log_ip VARCHAR(45) NOT NULL,
  log_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  KEY log_action(log_action),
  KEY log_type(log_type),
  KEY log_date(log_date)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblLanguage = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_languages (
  ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  lang_code VARCHAR(10) NOT NULL,
  lang_name VARCHAR(50) NOT NULL,
  lang_native VARCHAR(50) NOT NULL,
  lang_locale VARCHAR(10) DEFAULT NULL,
  lang_direction ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',
  lang_sort INT(11) NOT NULL DEFAULT 0,
  lang_is_default TINYINT(1) NOT NULL DEFAULT 0,
  lang_is_active TINYINT(1) NOT NULL DEFAULT 1,
  lang_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  UNIQUE KEY lang_code (lang_code)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblTranslation = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_translations (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    lang_id INT(11) UNSIGNED NOT NULL,
    translation_key VARCHAR(255) NOT NULL,
    translation_value TEXT NOT NULL,
    translation_context VARCHAR(100) DEFAULT NULL,
    translation_plurals VARCHAR(255) DEFAULT NULL,
    is_html TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    UNIQUE KEY lang_key (lang_id, translation_key),
    KEY lang_id (lang_id),
    KEY translation_key (translation_key(191))
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $tblDownloadLog = "CREATE TABLE IF NOT EXISTS {$prefix}tbl_download_log (
    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    media_id BIGINT(20) UNSIGNED NOT NULL,
    media_identifier CHAR(36) NOT NULL,
    ip_address VARCHAR(50) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    downloaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'success',
    PRIMARY KEY (ID),
    KEY idx_media_id(media_id),
    KEY idx_downloaded_at(downloaded_at),
    KEY idx_media_identifier(media_identifier)
  )Engine=InnoDB DEFAULT CHARSET=utf8mb4";

  $saveAdmin    = "INSERT INTO {$prefix}tbl_users (user_login, user_email, user_pass, user_level, user_registered, `user_session`) VALUES (?, ?, ?, ?, ?, ?)";
  $saveSettings = "INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) VALUES(?, ?)";
  $saveTheme   = "INSERT INTO {$prefix}tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status) VALUES (?, ?, ?, ?, ?)";

  $insertDefaultLanguage = "INSERT INTO {$prefix}tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default) VALUES ('en', 'English', 'English', 'en_US', 'ltr', 1, 1)";

  $insertLangSettings = "INSERT INTO {$prefix}tbl_settings (setting_name, setting_value) VALUES ('lang_default', 'en'), ('lang_available', 'en'), ('lang_auto_detect', '1'), ('lang_prefix_required', '1')";

  return [
    'tblUser' => $tblUser,
    'tblUserToken' => $tblUserToken,
    'tblLoginAttempt' => $tblLoginAttempt,
    'tblPost' => $tblPost,
    'tblMedia' => $tblMedia,
    'tblMediaMeta' => $tblMediaMeta,
    'tblMediaDownload' => $tblMediaDownload,
    'tblTopic' => $tblTopic,
    'tblPostTopic' => $tblPostTopic,
    'tblComment' => $tblComment,
    'tblMenu' => $tblMenu,
    'tblPlugin' => $tblPlugin,
    'tblSetting' => $tblSetting,
    'tblTheme' => $tblTheme,
    'tblConsents' => $tblConsents,
    'tblDataRequests' => $tblDataRequests,
    'tblPrivacyLogs' => $tblPrivacyLogs,
    'tblLanguage' => $tblLanguage,
    'tblTranslation' => $tblTranslation,
    'tblDownloadLog' => $tblDownloadLog,
    'saveAdmin' => $saveAdmin,
    'saveSettings' => $saveSettings,
    'saveTheme' => $saveTheme,
    'insertDefaultLanguage' => $insertDefaultLanguage,
    'insertLangSettings' => $insertLangSettings,
  ];
}
