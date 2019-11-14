<?php

$tableUser = "CREATE TABLE IF NOT EXISTS tbl_users (
ID BIGINT(20) unsigned NOT NULL auto_increment,
user_login VARCHAR(60) NOT NULL,
user_email VARCHAR(100) NOT NULL,
user_pass VARCHAR(255) NOT NULL,
user_level VARCHAR(20) NOT NULL,
user_fullname VARCHAR(120) DEFAULT NULL,
user_url VARCHAR(100) DEFAULT '#',
user_registered datetime NOT NULL DEFAULT '2018-04-01 12:00:00',
user_activation_key varchar(255) NOT NULL DEFAULT '',
user_reset_key varchar(255) DEFAULT NULL,
user_reset_complete VARCHAR(3) DEFAULT 'No',
user_session VARCHAR(255) NOT NULL,
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tableUserToken = "CREATE TABLE IF NOT EXISTS tbl_user_token (
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
user_id BIGINT(20) UNSIGNED NOT NULL,
pwd_hash VARCHAR(255) NOT NULL,
selector_hash VARCHAR(255) NOT NULL,
is_expired INT(11) NOT NULL DEFAULT '0',
expired_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (ID),
FOREIGN KEY (user_id) REFERENCES tbl_users(ID) ON DELETE CASCADE ON UPDATE CASCADE
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tablePost = "CREATE TABLE IF NOT EXISTS tbl_posts (
ID bigint(20) unsigned NOT NULL auto_increment,
media_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
post_author bigint(20) UNSIGNED NOT NULL DEFAULT '0',
post_date datetime NOT NULL DEFAULT '2018-04-01 12:00:00',
post_modified datetime NOT NULL DEFAULT '2018-04-01 12:00:00',
post_title text NOT NULL,
post_slug tinytext NOT NULL,
post_content longtext NOT NULL,    
post_summary tinytext DEFAULT NULL,    
post_keyword text DEFAULT NULL,    
post_status varchar(20) NOT NULL DEFAULT 'publish',    
post_type varchar(120) NOT NULL DEFAULT 'blog',   
comment_status varchar(20) NOT NULL DEFAULT 'open',
PRIMARY KEY (ID),    
FOREIGN KEY (post_author) REFERENCES tbl_users(ID),    
KEY (media_id)    
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";


$tableMedia = "CREATE TABLE IF NOT EXISTS tbl_media (    
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,    
media_filename VARCHAR(200) DEFAULT NULL,    
media_caption VARCHAR(200) DEFAULT NULL,    
media_type VARCHAR(20) NOT NULL,    
media_target VARCHAR(20) NOT NULL DEFAULT 'blog',    
media_user VARCHAR(20) NOT NULL,    
media_access VARCHAR(10) NOT NULL DEFAULT 'public',    
media_status INT(11) NOT NULL DEFAULT '0',
PRIMARY KEY (ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
    
$tableMediaMeta = "CREATE TABLE IF NOT EXISTS tbl_mediameta (
ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,    
media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',    
meta_key VARCHAR(255) NOT NULL,    
meta_value LONGTEXT DEFAULT NULL,    
PRIMARY KEY (ID),
KEY media_id(media_id),
KEY meta_key(meta_key(191))
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$tableTopic = "CREATE TABLE IF NOT EXISTS tbl_topics (
ID bigint(20) unsigned NOT NULL auto_increment,    
topic_title varchar(255) NOT NULL,    
topic_slug varchar(255) NOT NULL,    
topic_status enum('Y','N') NOT NULL DEFAULT 'Y',
PRIMARY KEY (ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tablePostTopic = "CREATE TABLE IF NOT EXISTS tbl_post_topic (
ID BIGINT(20) unsigned NOT NULL auto_increment,    
post_id bigint(20) unsigned DEFAULT NULL,    
topic_id bigint(20) unsigned DEFAULT NULL,
PRIMARY KEY(ID),
FOREIGN KEY (post_id) REFERENCES tbl_posts(ID) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (topic_id) REFERENCES tbl_topics(ID) ON DELETE CASCADE ON UPDATE CASCADE
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tableComment = "CREATE TABLE IF NOT EXISTS tbl_comments (
ID BIGINT(20) unsigned NOT NULL auto_increment,
comment_post_id BIGINT(20) unsigned NOT NULL,
comment_author_name VARCHAR(60) NOT NULL,
comment_author_ip VARCHAR(100) NOT NULL,
comment_content text NOT NULL,
comment_status VARCHAR(20) NOT NULL DEFAULT 'approved',
comment_date datetime NOT NULL DEFAULT '2018-04-01 12:00:00',
PRIMARY KEY (ID),
FOREIGN KEY (comment_post_id) REFERENCES tbl_posts(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
    
$tableReply = "CREATE TABLE IF NOT EXISTS tbl_comment_reply (
ID BIGINT(20) unsigned NOT NULL auto_increment,    
comment_id BIGINT(20)unsigned NOT NULL,
user_id BIGINT(20) unsigned NOT NULL,
reply_content text NOT NULL,
reply_status enum('0','1') NOT NULL DEFAULT '1',
reply_date datetime NOT NULL DEFAULT '2018-04-01 12:00:00',
PRIMARY KEY (ID, comment_id),
FOREIGN KEY (comment_id) REFERENCES tbl_comments(ID),
FOREIGN KEY (user_id) REFERENCES tbl_users(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tableMenu = "CREATE TABLE IF NOT EXISTS tbl_menu (
ID BIGINT(20) unsigned NOT NULL auto_increment,
menu_label VARCHAR(200) NOT NULL,
menu_link VARCHAR(255) DEFAULT NULL,
menu_sort INT(5) NOT NULL DEFAULT '0',
menu_status enum('Y','N') NOT NULL DEFAULT 'Y',
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tableMenuChild = "CREATE TABLE IF NOT EXISTS tbl_menu_child (
ID BIGINT(20) unsigned NOT NULL auto_increment,
menu_child_label VARCHAR(200) NOT NULL,
menu_child_link VARCHAR(255) DEFAULT NULL,
menu_id BIGINT(20) unsigned NOT NULL,
menu_sub_child BIGINT(20) unsigned NOT NULL,
menu_child_sort INT(5) NOT NULL DEFAULT '0',
menu_child_status enum('Y','N') NOT NULL DEFAULT 'Y',
PRIMARY KEY(ID),
FOREIGN KEY(menu_id) REFERENCES tbl_menu(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tablePlugin = "CREATE TABLE IF NOT EXISTS tbl_plugin (
ID BIGINT(20) unsigned NOT NULL auto_increment,
plugin_name VARCHAR(100) NOT NULL,
plugin_link VARCHAR(100) NOT NULL DEFAULT '#',
plugin_desc tinytext,
plugin_status enum('Y','N') NOT NULL DEFAULT 'N',
plugin_level VARCHAR(20) NOT NULL,
plugin_sort INT(5) DEFAULT NULL,
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tableSetting = "CREATE TABLE IF NOT EXISTS tbl_settings (
ID SMALLINT(5) unsigned NOT NULL AUTO_INCREMENT,
setting_name VARCHAR(100) NOT NULL,
setting_value VARCHAR(255) NOT NULL,
setting_desc TINYTEXT DEFAULT NULL,
PRIMARY KEY(ID),
KEY (setting_name),
KEY (setting_value)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";
        
$tableTheme = "CREATE TABLE IF NOT EXISTS tbl_themes (
ID BIGINT(20) unsigned NOT NULL auto_increment,
theme_title VARCHAR(100) NOT NULL,
theme_desc tinytext,
theme_designer VARCHAR(90) NOT NULL,
theme_directory VARCHAR(100) NOT NULL,
theme_status enum('Y','N') NOT NULL DEFAULT 'N',
PRIMARY KEY(ID)
)Engine=InnoDB DEFAULT CHARSET=utf8mb4";

$saveAdmin   = "INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_registered, `user_session`) VALUES (?, ?, ?, ?, ?, ?)";
$saveAppKey  = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveAppURL  = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$savePermalinks = "INSERT INTO tbl_settings (setting_name, setting_value) VALUES(?, ?)";
$saveTheme   = "INSERT INTO tbl_themes (theme_title, theme_desc, theme_designer, theme_directory, theme_status) VALUES (?, ?, ?, ?, ?)";
