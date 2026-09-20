<?php
/**
 * Test Database Setup
 * 
 * Creates test database tables for integration testing
 */

require_once __DIR__ . '/../lib/vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=blogware_test', 'blogwareuser', 'userblogware');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sqls = [
        'CREATE TABLE IF NOT EXISTS tbl_users (
          ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
          user_login VARCHAR(60) NOT NULL UNIQUE,
          user_email VARCHAR(100) NOT NULL UNIQUE,
          user_pass VARCHAR(255) NOT NULL,
          user_level VARCHAR(20) NOT NULL,
          user_fullname VARCHAR(120) DEFAULT NULL,
          user_url VARCHAR(100) DEFAULT NULL,
          user_registered datetime NOT NULL DEFAULT "1988-07-01 08:00:00",
          user_activation_key varchar(255) NOT NULL DEFAULT "",
          user_reset_key varchar(255) DEFAULT NULL,
          user_reset_complete VARCHAR(3) DEFAULT "No",
          user_session VARCHAR(255) NOT NULL,
          user_banned TINYINT NOT NULL DEFAULT "0",
          user_signin_count INT NOT NULL DEFAULT "0",
          user_locked_until DATETIME NULL,
          login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY(ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_posts (
          ID BIGINT(20) unsigned NOT NULL auto_increment,
          media_id BIGINT(20) UNSIGNED NOT NULL DEFAULT "0",
          post_author BIGINT(20) UNSIGNED NOT NULL DEFAULT "0",
          post_date datetime NOT NULL DEFAULT "1989-06-12 12:00:00",
          post_modified datetime DEFAULT NULL,
          post_title tinytext NOT NULL,
          post_slug text NOT NULL,
          post_content longtext NOT NULL,    
          post_summary mediumtext DEFAULT NULL,
          post_status varchar(20) NOT NULL DEFAULT "publish",
          post_visibility varchar(20) NOT NULL DEFAULT "public",
          post_password varchar(255) DEFAULT NULL,
          post_tags text DEFAULT NULL, 
          post_headlines INT(5) NOT NULL DEFAULT "0",
          post_sticky INT(5) NOT NULL DEFAULT "0",   
          post_type varchar(120) NOT NULL DEFAULT "blog",   
          comment_status varchar(20) NOT NULL DEFAULT "open",
          post_locale VARCHAR(10) NOT NULL DEFAULT "en",
          passphrase varchar(255) DEFAULT NULL,
          PRIMARY KEY (ID),
          KEY author_id(post_author),
          KEY post_media(media_id)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_topics (
          ID BIGINT(20) unsigned NOT NULL auto_increment,  
          topic_title varchar(255) NOT NULL,    
          topic_slug varchar(255) NOT NULL,    
          topic_status enum("Y","N") NOT NULL DEFAULT "Y",
          topic_locale VARCHAR(10) NOT NULL DEFAULT "en",
          PRIMARY KEY (ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_post_topic ( 
          post_id BIGINT(20) unsigned NOT NULL,    
          topic_id BIGINT(20) unsigned NOT NULL,
          PRIMARY KEY(post_id, topic_id)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_comments (
          ID BIGINT(20) unsigned NOT NULL auto_increment,
          comment_post_id BIGINT(20) unsigned NOT NULL,
          comment_parent_id BIGINT(20) NOT NULL DEFAULT "0",
          comment_author_name VARCHAR(60) NOT NULL,
          comment_author_ip VARCHAR(100) NOT NULL,
          comment_author_email VARCHAR(100) DEFAULT NULL,
          comment_content text NOT NULL,
          comment_status VARCHAR(20) NOT NULL DEFAULT "pending",
          comment_date datetime NOT NULL DEFAULT "1988-07-01 08:00:00",
          PRIMARY KEY (ID),
          KEY idx_post(comment_post_id),
          KEY idx_status(comment_status)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_media (    
          ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,   
          media_filename VARCHAR(200) DEFAULT NULL,    
          media_caption VARCHAR(200) DEFAULT NULL,    
          media_type VARCHAR(90) NOT NULL,    
          media_target VARCHAR(20) NOT NULL DEFAULT "blog",    
          media_user VARCHAR(20) NOT NULL,    
          media_access VARCHAR(10) NOT NULL DEFAULT "public",    
          media_status INT(11) NOT NULL DEFAULT "0",
          PRIMARY KEY (ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_mediameta (
          ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          media_id BIGINT(20) UNSIGNED NOT NULL,
          meta_key VARCHAR(255) NOT NULL,
          meta_value LONGTEXT,
          PRIMARY KEY (ID),
          KEY idx_media_id (media_id),
          KEY idx_meta_key (meta_key(191))
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_settings (
          ID INT(11) unsigned NOT NULL AUTO_INCREMENT,
          setting_name VARCHAR(255) NOT NULL,
          setting_value TEXT DEFAULT NULL,
          PRIMARY KEY(ID),
          KEY setting_name(setting_name(191))
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_menu (
           ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
           menu_label VARCHAR(200) NOT NULL,
           menu_link VARCHAR(255) DEFAULT NULL,
           menu_status ENUM("Y", "N") NOT NULL DEFAULT "N",
           menu_visibility VARCHAR(20) NOT NULL DEFAULT "public",
           menu_locale VARCHAR(10) NOT NULL DEFAULT "en",
           parent_id INT(11) UNSIGNED NOT NULL DEFAULT "0",
           menu_sort INT(11) UNSIGNED NOT NULL DEFAULT "0",
           PRIMARY KEY (ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_plugin (
          ID BIGINT(20) unsigned NOT NULL auto_increment,
          plugin_name VARCHAR(100) NOT NULL,
          plugin_link VARCHAR(255) NOT NULL DEFAULT "#",
          plugin_directory VARCHAR(100) NOT NULL,
          plugin_desc tinytext,
          plugin_status enum("Y","N") NOT NULL DEFAULT "N",
          plugin_level VARCHAR(20) NOT NULL,
          plugin_sort INT(5) DEFAULT NULL,
          PRIMARY KEY(ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_themes (
          ID INT(11) unsigned NOT NULL auto_increment,
          theme_title VARCHAR(100) NOT NULL,
          theme_desc tinytext,
          theme_designer VARCHAR(90) NOT NULL,
          theme_directory VARCHAR(100) NOT NULL,
          theme_status enum("Y","N") NOT NULL DEFAULT "N",
          PRIMARY KEY(ID)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_languages (
          ID INT(11) unsigned NOT NULL AUTO_INCREMENT,
          lang_code VARCHAR(10) NOT NULL,
          lang_name VARCHAR(50) NOT NULL,
          lang_native VARCHAR(50) NOT NULL,
          lang_locale VARCHAR(10) DEFAULT NULL,
          lang_direction ENUM("ltr","rtl") DEFAULT "ltr",
          lang_sort INT(11) DEFAULT 0,
          lang_is_default TINYINT(1) DEFAULT 0,
          lang_is_active TINYINT(1) DEFAULT 1,
          lang_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (ID),
          UNIQUE KEY lang_code (lang_code)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_translations (
          ID BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
          lang_id INT(11) unsigned NOT NULL,
          translation_key VARCHAR(255) NOT NULL,
          translation_value TEXT NOT NULL,
          translation_context VARCHAR(100) DEFAULT NULL,
          translation_plurals VARCHAR(255) DEFAULT NULL,
          is_html TINYINT(1) DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (ID),
          UNIQUE KEY lang_key (lang_id, translation_key),
          KEY lang_id (lang_id),
          KEY translation_key (translation_key(191))
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4',

        'CREATE TABLE IF NOT EXISTS tbl_privacy_policies (
          ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          locale VARCHAR(10) NOT NULL DEFAULT "en",
          policy_title VARCHAR(255) NOT NULL,
          policy_content LONGTEXT NOT NULL,
          is_default TINYINT(1) NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (ID),
          UNIQUE KEY locale (locale)
        )Engine=InnoDB DEFAULT CHARSET=utf8mb4'
    ];
    
    foreach ($sqls as $sql) {
        $pdo->exec($sql);
    }
    
    echo "Test database tables created successfully!\n";
    
    // Insert test admin user
    $stmt = $pdo->prepare("INSERT IGNORE INTO tbl_users (user_login, user_email, user_pass, user_level, user_session) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'admin',
        'admin@test.com',
        password_hash('admin123', PASSWORD_DEFAULT),
        'administrator',
        ''
    ]);
    
    echo "Test admin user created!\n";
    
    // ===== Language Switcher Test Data =====
    
    // Insert default languages
    $langStmt = $pdo->prepare("INSERT IGNORE INTO tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default, lang_is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // English (default)
    $langStmt->execute(['en', 'English', 'English', 'en_US', 'ltr', 1, 1, 1]);
    // Arabic (RTL)
    $langStmt->execute(['ar', 'Arabic', 'العربية', 'ar_SA', 'rtl', 2, 0, 1]);
    // Chinese
    $langStmt->execute(['zh', 'Chinese', '中文', 'zh_CN', 'ltr', 3, 0, 1]);
    // French
    $langStmt->execute(['fr', 'French', 'Français', 'fr_FR', 'ltr', 4, 0, 1]);
    // Spanish
    $langStmt->execute(['es', 'Spanish', 'Español', 'es_ES', 'ltr', 5, 0, 1]);
    // Indonesian
    $langStmt->execute(['id', 'Indonesian', 'Bahasa Indonesia', 'id_ID', 'ltr', 6, 0, 1]);
    
    echo "Languages seeded!\n";
    
    // Insert test posts with different locales
    $postStmt = $pdo->prepare("INSERT IGNORE INTO tbl_posts (post_title, post_slug, post_content, post_status, post_type, post_locale, post_author, post_date) VALUES (?, ?, ?, 'publish', 'blog', ?, 1, '2026-01-15 10:00:00')");
    
    $postStmt->execute(['English Post', 'english-post', 'This is an English test post content', 'en']);
    $postStmt->execute(['Arabic Post', 'arabic-post', 'هذا هو محتوى مشاركة اختبار باللغة العربية', 'ar']);
    $postStmt->execute(['Chinese Post', 'chinese-post', '这是一篇中文测试文章内容', 'zh']);
    $postStmt->execute(['French Post', 'french-post', 'Ceci est un contenu de test en français', 'fr']);
    
    echo "Test posts seeded!\n";
    
    // Insert test topics with different locales
    $topicStmt = $pdo->prepare("INSERT IGNORE INTO tbl_topics (topic_title, topic_slug, topic_status, topic_locale) VALUES (?, ?, 'Y', ?)");
    
    $topicStmt->execute(['English Category', 'english-category', 'en']);
    $topicStmt->execute(['Arabic Category', 'arabic-category', 'ar']);
    $topicStmt->execute(['Chinese Category', 'chinese-category', 'zh']);
    
    echo "Test topics seeded!\n";
    
    // Insert test menu items with different locales
    $menuStmt = $pdo->prepare("INSERT IGNORE INTO tbl_menu (menu_label, menu_link, menu_status, menu_locale, menu_sort) VALUES (?, ?, 'Y', ?, ?)");
    
    $menuStmt->execute(['Home', '/', 'en', 1]);
    $menuStmt->execute(['الرئيسية', '/', 'ar', 1]);
    $menuStmt->execute(['首页', '/', 'zh', 1]);
    $menuStmt->execute(['Accueil', '/', 'fr', 1]);
    $menuStmt->execute(['Blog', '/blog', 'en', 2]);
    $menuStmt->execute(['مدونة', '/blog', 'ar', 2]);
    $menuStmt->execute(['博客', '/blog', 'zh', 2]);
    
    echo "Test menu items seeded!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
