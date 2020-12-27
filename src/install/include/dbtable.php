<?php
/**
 * File dbtable.php
 * 
 */
$tblUser = "CREATE TABLE IF NOT EXISTS tbl_users (
ID BIGINT(20) unsigned NOT NULL auto_increment,
user_login VARCHAR(60) NOT NULL UNIQUE,
user_email VARCHAR(100) NOT NULL UNIQUE,
user_pass VARCHAR(255) NOT NULL,
user_level VARCHAR(20) NOT NULL,
user_fullname VARCHAR(120) DEFAULT NULL,
user_url VARCHAR(100) DEFAULT NULL,
user_registered datetime NOT NULL DEFAULT '1987-11-20 12:00:00',
user_activation_key varchar(255) NOT NULL DEFAULT '',
user_reset_key varchar(255) DEFAULT NULL,
user_reset_complete VARCHAR(3) DEFAULT 'No',
user_session VARCHAR(255) NOT NULL,
user_banned TINYINT NOT NULL DEFAULT 0,
user_signin_count INT NOT NULL DEFAULT 0,
user_locked_until DATETIME NULL,
login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblUserToken = "CREATE TABLE IF NOT EXISTS tbl_user_token (
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
user_login VARCHAR(60) NOT NULL,
pwd_hash VARCHAR(255) NOT NULL,
selector_hash VARCHAR(255) NOT NULL,
is_expired INT(11) NOT NULL DEFAULT '0',
expired_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblLoginAttempt = "CREATE TABLE IF NOT EXISTS tbl_login_attempt (
ip_address VARCHAR(255) NOT NULL,
login_date datetime NOT NULL DEFAULT '1987-11-20 12:00:00'
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblPost = "CREATE TABLE IF NOT EXISTS tbl_posts (
ID BIGINT(20) unsigned NOT NULL auto_increment,
media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
post_author BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
post_date datetime NOT NULL DEFAULT '1987-11-20 12:00:00',
post_modified datetime NOT NULL DEFAULT '1987-11-20 12:00:00',
post_title text NOT NULL,
post_slug tinytext NOT NULL,
post_content longtext NOT NULL,    
post_summary tinytext DEFAULT NULL,    
post_keyword text DEFAULT NULL,  
post_tags varchar(255) DEFAULT NULL,  
post_status varchar(20) NOT NULL DEFAULT 'publish',
post_sticky TINYINT DEFAULT '0',    
post_type varchar(120) NOT NULL DEFAULT 'blog',   
comment_status varchar(20) NOT NULL DEFAULT 'open',
PRIMARY KEY (ID),    
FOREIGN KEY (post_author) REFERENCES tbl_users(ID),    
KEY (media_id)    
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblMedia = "CREATE TABLE IF NOT EXISTS tbl_media (    
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
    
$tblMediaMeta = "CREATE TABLE IF NOT EXISTS tbl_mediameta (
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,    
media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0', 
meta_key VARCHAR(255) NOT NULL,    
meta_value LONGTEXT DEFAULT NULL,    
PRIMARY KEY (ID),
KEY media_id(media_id),
KEY meta_key(meta_key(191))
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblMediaDownload = "CREATE TABLE IF NOT EXISTS tbl_media_download (
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
media_id BIGINT(20) UNSIGNED NOT NULL,
media_identifier VARCHAR(64) NOT NULL,
before_expired VARCHAR(50) NOT NULL,
ip_address VARCHAR(50) NOT NULL,
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (ID),
UNIQUE KEY media_identifier(media_identifier),
FOREIGN KEY (media_id) REFERENCES tbl_media(ID) ON DELETE CASCADE ON UPDATE CASCADE
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tblTopic = "CREATE TABLE IF NOT EXISTS tbl_topics (
ID BIGINT(20) unsigned NOT NULL auto_increment,  
topic_title varchar(255) NOT NULL,    
topic_slug varchar(255) NOT NULL,    
topic_status enum('Y','N') NOT NULL DEFAULT 'Y',
PRIMARY KEY (ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tblPostTopic = "CREATE TABLE IF NOT EXISTS tbl_post_topic (
ID BIGINT(20) unsigned NOT NULL auto_increment,    
post_id BIGINT(20) unsigned DEFAULT NULL,    
topic_id BIGINT(20) unsigned DEFAULT NULL,
PRIMARY KEY(ID),
FOREIGN KEY (post_id) REFERENCES tbl_posts(ID) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (topic_id) REFERENCES tbl_topics(ID) ON DELETE CASCADE ON UPDATE CASCADE
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tblComment = "CREATE TABLE IF NOT EXISTS tbl_comments (
ID BIGINT(20) unsigned NOT NULL auto_increment,
comment_post_id BIGINT(20) unsigned NOT NULL,
comment_author_name VARCHAR(60) NOT NULL,
comment_author_ip VARCHAR(100) NOT NULL,
comment_content text NOT NULL,
comment_status VARCHAR(20) NOT NULL DEFAULT 'approved',
comment_date datetime NOT NULL DEFAULT '1987-11-20 12:00:00',
PRIMARY KEY (ID),
FOREIGN KEY (comment_post_id) REFERENCES tbl_posts(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
    
$tblReply = "CREATE TABLE IF NOT EXISTS tbl_comment_reply (
ID BIGINT(20) unsigned NOT NULL auto_increment,    
comment_id BIGINT(20)unsigned NOT NULL,
user_id BIGINT(20) unsigned NOT NULL,
reply_content text NOT NULL,
reply_status VARCHAR(20) NOT NULL DEFAULT 'activated',
reply_date datetime NOT NULL DEFAULT '1987-11-20 12:00:00',
PRIMARY KEY (ID, comment_id),
FOREIGN KEY (comment_id) REFERENCES tbl_comments(ID),
FOREIGN KEY (user_id) REFERENCES tbl_users(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tblMenu = "CREATE TABLE IF NOT EXISTS tbl_menu (
ID INT(5) unsigned NOT NULL auto_increment,
parent_id INT(5) unsigned NOT NULL DEFAULT '0',
menu_label VARCHAR(200) NOT NULL,
menu_link VARCHAR(255) DEFAULT NULL,
menu_sort INT(5) NOT NULL,
menu_status enum('Y','N') NOT NULL DEFAULT 'Y',
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
                
$tblPlugin = "CREATE TABLE IF NOT EXISTS tbl_plugin (
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
        
$tblSetting = "CREATE TABLE IF NOT EXISTS tbl_settings (
ID INT(5) unsigned NOT NULL AUTO_INCREMENT,
setting_name VARCHAR(100) NOT NULL,
setting_value VARCHAR(255) NOT NULL,
PRIMARY KEY(ID),
KEY (setting_name),
KEY (setting_value)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tblTheme = "CREATE TABLE IF NOT EXISTS tbl_themes (
ID INT(11) unsigned NOT NULL auto_increment,
theme_title VARCHAR(100) NOT NULL,
theme_desc tinytext,
theme_designer VARCHAR(90) NOT NULL,
theme_directory VARCHAR(100) NOT NULL,
theme_status enum('Y','N') NOT NULL DEFAULT 'N',
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$saveAdmin    = "INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_registered, `user_session`) VALUES (?, ?, ?, ?, ?, ?)";
$saveAppKey   = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveAppURL   = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveSiteName = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveSiteTagline = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveSiteDescription = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveSiteKeywords = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveSiteEmail = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$savePermalinks = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveTheme   = "INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status) VALUES (?, ?, ?, ?, ?)";
