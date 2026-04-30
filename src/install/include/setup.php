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

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'check-engine.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dbtable.php';

function current_url() // returning current url
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") ? "https" : "http";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    
    $phpSelf = $_SERVER['PHP_SELF'];
    
    $path = dirname(rtrim($phpSelf, '/'));
    
    if ($path === '/') {
        $path = '';
    }
    
    return $scheme . "://" . $host . $path . '/';
}

/**
 * make_connection()
 *
 * @param string $host
 * @param string $username
 * @param string $passwd
 * @param string $dbname
 * @param int $port
 * @return object
 *
 */
function make_connection($host, $username, $passwd, $dbname, $dbport)
{

    $driver = class_exists('mysqli_driver') ? new mysqli_driver() : "";

    $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

    $connect = new mysqli($host, $username, $passwd, $dbname, $dbport);

    if ($connect->connect_errno) {
        printf("Failed to connect to MySQL: (" . $connect->connect_errno . ") " . $connect->connect_error, E_USER_ERROR);

        close_connection($connect);

        exit();
    }

    $connect->set_charset('utf8mb4');

    return $connect;
}

/**
 * make_secure_connection()
 *
 * @param string $host
 * @param string $username
 * @param string $passwd
 * @param string $dbname
 * @param int $dbport
 * @param string $ca
 * @return object
 *
 */
function make_secure_connection($host, $username, $passwd, $dbname, $dbport, $ca)
{

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connect = mysqli_init();
    $connect->ssl_set(null, null, $ca, null, null);
    $connect->real_connect($host, $username, $passwd, $dbname, $dbport);

    if ($connect->connect_errno) {
        printf("Failed to connect to MySQL: (" . $connect->connect_errno . ") " . $connect->connect_error, E_USER_ERROR);

        close_connection($connect);

        exit();
    }

    $connect->set_charset('utf8mb4');

    return $connect;
}

/**
 * generate_license()
 * to create serial generation of license key with php
 *
 * @link https://stackoverflow.com/questions/3687878/serial-generation-with-php
 * @param string $suffix
 * @return string
 *
 */
function generate_license($suffix = null)
{
    $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No ambiguous characters
    $token_length = strlen($tokens); // Store token length for efficiency

    // Determine the number of segments and characters per segment
    $num_segments = isset($suffix) ? 3 : 4;
    $segment_chars = isset($suffix) ? 6 : 5;

    // Build the license string using CSPRNG
    $segments = [];
    for ($i = 0; $i < $num_segments; $i++) {
        $segment = '';
        for ($j = 0; $j < $segment_chars; $j++) {
            $segment .= $tokens[random_int(0, $token_length - 1)];
        }
        $segments[] = $segment;
    }

    $license_string = implode('-', $segments);

    // Append the suffix if provided
    if (isset($suffix)) {
        if (is_numeric($suffix)) {
            // User ID provided
            $license_string .= '-' . strtoupper(base_convert($suffix, 10, 36));
        } elseif (filter_var($suffix, FILTER_VALIDATE_IP)) {
            // Valid IP address
            $long = ip2long($suffix);
            $license_string .= '-' . strtoupper(base_convert($long, 10, 36));
        } else {
            // General string suffix
            $license_string .= '-' . strtoupper(str_replace(' ', '-', $suffix));
        }
    }

    return $license_string;
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

        return ($check) && ($check->num_rows > 0) ? true : false;
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
 * install_database_table()
 *
 * @category installation setup function for database installation
 * @param object $link
 * @param string $user_login
 * @param string $user_pass
 * @param string $user_email
 * @param string $key
 * @param string $prefix
 * @param string $site_language
 * @param string $defuse_key_path
 *
 */
function install_database_table($link, $protocol, $server_host, $user_login, $user_pass, $user_email, $key, $prefix = '', $site_language = 'en', $defuse_key_path = '')
{

    $tables = get_table_definitions($prefix);

    extract($tables);

    // Users
    $date_registered = date('Y-m-d H:i:s');
    $user_session    = substr(hash('sha256', $key), 0, 32);
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

    // setting memberships
    $membership_key = "membership_setting";
    $membership_value = array('user_can_register' => 0, 'default_role' => 'subscriber');
    $store_membership_value = json_encode($membership_value);

    // Setting table prefix
    $setting_prefix_key = "tbl_prefix";
    $setting_prefix_value = $prefix;

    // SMTP Settings
    $smtp_host = "smtp_host";
    $smtp_port = "smtp_port";
    $smtp_encryption = "smtp_encryption";
    $smtp_username = "smtp_username";
    $smtp_password = "smtp_password";
    $smtp_from_email = "smtp_from_email";
    $smtp_from_name = "smtp_from_name";

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
            $link->query($tblConsents);
            $link->query($tblDataRequests);
            $link->query($tblPrivacyLogs);
            $link->query($tblLanguage);
            $link->query($tblTranslation);
            $link->query($tblPrivacyPolicy);
            $link->query($tblDownloadLog);

            // insert configuration - app_key
            $recordAppKey = $link->prepare($saveSettings);
            $recordAppKey->bind_param('ss', $setting_name_key, $key);
            $recordAppKey->execute();

            // insert configuration - app_url
            $recordAppURL = $link->prepare($saveSettings);
            $recordAppURL->bind_param('ss', $setting_name_url, $setting_value_url);
            $recordAppURL->execute();

            // insert configuration - site_name
            $recordAppSiteName = $link->prepare($saveSettings);
            $recordAppSiteName->bind_param('ss', $site_name, $site_name_value);
            $recordAppSiteName->execute();

            // insert configuration - site_tagline
            $recordAppSiteTagline = $link->prepare($saveSettings);
            $recordAppSiteTagline->bind_param('ss', $site_tagline, $site_tagline_value);
            $recordAppSiteTagline->execute();

            // insert configuration - site_description
            $recordAppSiteDescription = $link->prepare($saveSettings);
            $recordAppSiteDescription->bind_param('ss', $site_description, $site_description_value);
            $recordAppSiteDescription->execute();

            // insert configuration - site_keywords
            $recordAppSiteKeywords = $link->prepare($saveSettings);
            $recordAppSiteKeywords->bind_param('ss', $site_keywords, $site_keywords_value);
            $recordAppSiteKeywords->execute();

            // insert configuration - site_email
            $recordAppSiteEmail = $link->prepare($saveSettings);
            $recordAppSiteEmail->bind_param('ss', $site_email, $user_email);
            $recordAppSiteEmail->execute();

            // insert configuration - posts per page
            $recordPostPerPage = $link->prepare($saveSettings);
            $recordPostPerPage->bind_param('ss', $post_per_page, $post_per_page_value);
            $recordPostPerPage->execute();

            // insert configuration post per rss
            $recordPostPerRSS = $link->prepare($saveSettings);
            $recordPostPerRSS->bind_param('ss', $post_per_rss, $post_per_rss_value);
            $recordPostPerRSS->execute();

            // insert configuration post per archive
            $recordPostPerArchive = $link->prepare($saveSettings);
            $recordPostPerArchive->bind_param('ss', $post_per_archive, $post_per_archive_value);
            $recordPostPerArchive->execute();

            // insert configuration comment per post
            $recordCommentPerPost = $link->prepare($saveSettings);
            $recordCommentPerPost->bind_param('ss', $comment_per_post, $comment_per_post_value);
            $recordCommentPerPost->execute();

            // insert configuration - permalinks
            $recordPermalinks = $link->prepare($saveSettings);
            $recordPermalinks->bind_param('ss', $permalink_key, $store_permalink_value);
            $recordPermalinks->execute();

            // insert configuration - timezone
            $recordTimezone = $link->prepare($saveSettings);
            $recordTimezone->bind_param('ss', $timezone_key, $store_timezone_value);
            $recordTimezone->execute();

            // insert configuration - memberships
            $recordMemberships = $link->prepare($saveSettings);
            $recordMemberships->bind_param('ss', $membership_key, $store_membership_value);
            $recordMemberships->execute();

            // insert configuration - table prefix
            $recordTblPrefix = $link->prepare($saveSettings);
            $recordTblPrefix->bind_param('ss', $setting_prefix_key, $setting_prefix_value);
            $recordTblPrefix->execute();

            // insert configuration - defuse key path (system-generated secure path outside web root)
            $defuse_key_setting = "defuse_key_path";
            $recordDefuseKey = $link->prepare($saveSettings);
            $recordDefuseKey->bind_param('ss', $defuse_key_setting, $defuse_key_path);
            $recordDefuseKey->execute();

            // insert SMTP settings
            $smtp_port_val = "587";
            $smtp_enc_val = "tls";
            $smtp_name_val = "Blogware";

            $recordSmtpHost = $link->prepare($saveSettings);
            $recordSmtpHost->bind_param('ss', $smtp_host, $dbhost); // default to localhost/dbhost or empty
            $recordSmtpHost->execute();

            $recordSmtpPort = $link->prepare($saveSettings);
            $recordSmtpPort->bind_param('ss', $smtp_port, $smtp_port_val);
            $recordSmtpPort->execute();

            $recordSmtpEnc = $link->prepare($saveSettings);
            $recordSmtpEnc->bind_param('ss', $smtp_encryption, $smtp_enc_val);
            $recordSmtpEnc->execute();

            $recordSmtpUser = $link->prepare($saveSettings);
            $recordSmtpUser->bind_param('ss', $smtp_username, $user_email);
            $recordSmtpUser->execute();

            $recordSmtpPass = $link->prepare($saveSettings);
            $empty_pass = "";
            $recordSmtpPass->bind_param('ss', $smtp_password, $empty_pass);
            $recordSmtpPass->execute();

            $recordSmtpFromEmail = $link->prepare($saveSettings);
            $recordSmtpFromEmail->bind_param('ss', $smtp_from_email, $user_email);
            $recordSmtpFromEmail->execute();

            $recordSmtpFromName = $link->prepare($saveSettings);
            $recordSmtpFromName->bind_param('ss', $smtp_from_name, $smtp_name_val);
            $recordSmtpFromName->execute();

            // insert API rate limiting settings
            $api_rate_limit_enabled = "api_rate_limit_enabled";
            $api_rate_limit_enabled_value = "1"; // enabled by default
            $api_rate_limit_read = "api_rate_limit_read";
            $api_rate_limit_read_value = "60"; // 60 requests per minute
            $api_rate_limit_write = "api_rate_limit_write";
            $api_rate_limit_write_value = "20"; // 20 requests per minute

            $recordApiRateEnabled = $link->prepare($saveSettings);
            $recordApiRateEnabled->bind_param('ss', $api_rate_limit_enabled, $api_rate_limit_enabled_value);
            $recordApiRateEnabled->execute();

            $recordApiRateRead = $link->prepare($saveSettings);
            $recordApiRateRead->bind_param('ss', $api_rate_limit_read, $api_rate_limit_read_value);
            $recordApiRateRead->execute();

            $recordApiRateWrite = $link->prepare($saveSettings);
            $recordApiRateWrite->bind_param('ss', $api_rate_limit_write, $api_rate_limit_write_value);
            $recordApiRateWrite->execute();
            
            // insert download settings
            $download_mime_types = "download_allowed_mime_types";
            $download_mime_types_value = json_encode([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/rtf',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
                'image/png',
                'image/jpeg',
                'image/gif',
                'image/webp',
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'video/mp4',
                'video/webm',
                'application/json',
            ]);
            $recordDownloadMimeTypes = $link->prepare($saveSettings);
            $recordDownloadMimeTypes->bind_param('ss', $download_mime_types, $download_mime_types_value);
            $recordDownloadMimeTypes->execute();
            
            $download_expiry = "download_expiry_hours";
            $download_expiry_value = "8";
            $recordDownloadExpiry = $link->prepare($saveSettings);
            $recordDownloadExpiry->bind_param('ss', $download_expiry, $download_expiry_value);
            $recordDownloadExpiry->execute();
            
            $download_hotlink = "download_hotlink_protection";
            $download_hotlink_value = "no";
            $recordDownloadHotlink = $link->prepare($saveSettings);
            $recordDownloadHotlink->bind_param('ss', $download_hotlink, $download_hotlink_value);
            $recordDownloadHotlink->execute();
            
            $download_domains = "download_allowed_domains";
            $download_domains_value = json_encode([]);
            $recordDownloadDomains = $link->prepare($saveSettings);
            $recordDownloadDomains->bind_param('ss', $download_domains, $download_domains_value);
            $recordDownloadDomains->execute();
            
            $download_support_url = "download_support_url";
            $download_support_url_value = "";
            $recordDownloadSupportUrl = $link->prepare($saveSettings);
            $recordDownloadSupportUrl->bind_param('ss', $download_support_url, $download_support_url_value);
            $recordDownloadSupportUrl->execute();
            
            $download_support_label = "download_support_label";
            $download_support_label_value = "Support";
            $recordDownloadSupportLabel = $link->prepare($saveSettings);
            $recordDownloadSupportLabel->bind_param('ss', $download_support_label, $download_support_label_value);
            $recordDownloadSupportLabel->execute();
            
            // insert language settings
            $link->query($insertLangSettings);

            // insert default theme
            $recordTheme = $link->prepare($saveTheme);
            $recordTheme->bind_param("sssss", $theme_title, $theme_desc, $theme_designer, $theme_directory, $theme_status);
            $recordTheme->execute();

            // insert default plugin - Hello World
            $plugin_name = "Hello World";
            $plugin_link = "#";
            $plugin_directory = "hello-world";
            $plugin_desc = "A simple Hello World plugin to demonstrate the plugin system";
            $plugin_status = "N"; // disabled by default
            $plugin_level = "administrator";
            $plugin_sort = 1;

            $recordPlugin = $link->prepare($savePlugin);
            $recordPlugin->bind_param("ssssssi", $plugin_name, $plugin_link, $plugin_directory, $plugin_desc, $plugin_status, $plugin_level, $plugin_sort);
            $recordPlugin->execute();

            if ($recordAppKey->affected_rows > 0) {
                install_i18n_data($link, $prefix, $site_language);

                $link->close();
            }
        }
    }
}

/**
 * install_i18n_data()
 *
 * Populate languages and translations tables with initial data
 *
 * @param object $link
 * @param string $prefix
 * @param string $default_lang (language code to set as default)
 *
 * @return void
 */
function install_i18n_data($link, $prefix = '', $default_lang = 'en')
{
    $languages = [
      'en' => ['name' => 'English', 'native' => 'English', 'locale' => 'en_US', 'direction' => 'ltr', 'sort' => 1, 'is_default' => 1],
      'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'locale' => 'ar_SA', 'direction' => 'rtl', 'sort' => 2, 'is_default' => 0],
      'zh' => ['name' => 'Chinese', 'native' => '中文', 'locale' => 'zh_CN', 'direction' => 'ltr', 'sort' => 3, 'is_default' => 0],
      'fr' => ['name' => 'French', 'native' => 'Français', 'locale' => 'fr_FR', 'direction' => 'ltr', 'sort' => 4, 'is_default' => 0],
      'ru' => ['name' => 'Russian', 'native' => 'Русский', 'locale' => 'ru_RU', 'direction' => 'ltr', 'sort' => 5, 'is_default' => 0],
      'es' => ['name' => 'Spanish', 'native' => 'Español', 'locale' => 'es_ES', 'direction' => 'ltr', 'sort' => 6, 'is_default' => 0],
      'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'locale' => 'id_ID', 'direction' => 'ltr', 'sort' => 7, 'is_default' => 0],
    ];

    $translations = [
      'navigation' => [
        'nav.navigation' => ['Navigation', 'التنقل', '导航', 'Navigation', 'Навигация', 'Navegación', 'Navigasi'],
        'nav.dashboard' => ['Dashboard', 'لوحة القيادة', '仪表盘', 'Tableau de bord', 'Панель управления', 'Panel de control', 'Dasbor'],
        'nav.posts' => ['Posts', 'المقالات', '文章', 'Articles', 'Записи', 'Entradas', 'Postingan'],
        'nav.all_posts' => ['All Posts', 'جميع المقالات', '所有文章', 'Tous les articles', 'Все записи', 'Todas las entradas', 'Semua Postingan'],
        'nav.add_new' => ['Add New', 'إضافة جديد', '新建', 'Ajouter', 'Добавить', 'Añadir nuevo', 'Tambah Baru'],
        'nav.new_post' => ['New Post', 'مشاركة جديدة', '新文章', 'Nouveau post', 'Новая запись', 'Nueva entrada', 'Postingan Baru'],
        'nav.edit_post' => ['Edit Post', 'تعديل المشاركة', '编辑文章', 'Modifier le post', 'Редактировать запись', 'Editar entrada', 'Edit Postingan'],
        'nav.categories' => ['Categories', 'الفئات', '分类', 'Catégories', 'Категории', 'Categorías', 'Kategori'],
        'nav.media' => ['Media', 'الوسائط', '媒体', 'Médias', 'Медиа', 'Medios', 'Media'],
        'nav.library' => ['Library', 'المكتبة', '媒体库', 'Médiothèque', 'Медиатека', 'Biblioteca de medios', 'Pustaka Media'],
        'nav.add_media' => ['Add New', 'إضافة جديد', '新建', 'Ajouter', 'Добавить', 'Añadir nuevo', 'Tambah Baru'],
        'nav.downloads' => ['Downloads', 'التنزيلات', '下载', 'Téléchargements', 'Загрузки', 'Descargas', 'Unduhan'],
        'nav.pages' => ['Pages', 'الصفحات', '页面', 'Pages', 'Страницы', 'Páginas', 'Halaman'],
        'nav.all_pages' => ['All Pages', 'جميع الصفحات', '所有页面', 'Toutes les pages', 'Все страницы', 'Todas las páginas', 'Semua Halaman'],
        'nav.new_page' => ['New Page', 'صفحة جديدة', '新页面', 'Nouvelle page', 'Новая страница', 'Nueva página', 'Halaman Baru'],
        'nav.edit_page' => ['Edit Page', 'تعديل الصفحة', '编辑页面', 'Modifier la page', 'Редактировать страницу', 'Editar página', 'Edit Halaman'],
        'nav.comments' => ['Comments', 'التعليقات', '评论', 'Commentaires', 'Комментарии', 'Comentarios', 'Komentar'],
        'nav.pending_comments' => ['Pending Comments', 'التعليقات المعلقة', '待审核评论', 'Commentaires en attente', 'Ожидающие комментарии', 'Comentarios pendientes', 'Komentar Tertunda'],
        'nav.approved_comments' => ['Approved Comments', 'التعليقات المعتمدة', '已批准评论', 'Commentaires approuvés', 'Одобренные комментарии', 'Comentarios aprobados', 'Komentar Disetujui'],
        'nav.spam_comments' => ['Spam', 'البريد المزعج', '垃圾评论', 'Spam', 'Спам', 'Spam', 'Spam'],
        'nav.trash_comments' => ['Trash', 'سلة المهملات', '回收站', 'Corbeille', 'Корзина', 'Papelera', 'Sampah'],
        'nav.tools' => ['Tools', 'الأدوات', '工具', 'Outils', 'Инструменты', 'Herramientas', 'Alat'],
        'nav.import' => ['Import', 'استيراد', '导入', 'Importer', 'Импорт', 'Importar', 'Impor'],
        'nav.export' => ['Export', 'تصدير', '导出', 'Exporter', 'Экспорт', 'Exportar', 'Ekspor'],
        'nav.backup' => ['Backup', 'نسخ احتياطي', '备份', 'Sauvegarde', 'Резервное копирование', 'Copia de seguridad', 'Cadangan'],
        'nav.restore' => ['Restore', 'استعادة', '恢复', 'Restaurer', 'Восстановить', 'Restaurar', 'Pulihkan'],
        'nav.users' => ['Users', 'المستخدمون', '用户', 'Utilisateurs', 'Пользователи', 'Usuarios', 'Pengguna'],
        'nav.all_users' => ['All Users', 'جميع المستخدمين', '所有用户', 'Tous les utilisateurs', 'Все пользователи', 'Todos los usuarios', 'Semua Pengguna'],
        'nav.add_user' => ['Add New', 'إضافة جديد', '新建', 'Ajouter', 'Добавить', 'Añadir nuevo', 'Tambah Baru'],
        'nav.your_profile' => ['Your Profile', 'ملفك الشخصي', '您的个人资料', 'Votre profil', 'Ваш профиль', 'Tu perfil', 'Profil Anda'],
        'nav.appearance' => ['Appearance', 'المظهر', '外观', 'Apparence', 'Внешний вид', 'Apariencia', 'Tampilan'],
        'nav.themes' => ['Themes', 'القوالب', '主题', 'Thèmes', 'Темы', 'Temas', 'Tema'],
        'nav.menus' => ['Menus', 'القوائم', '菜单', 'Menus', 'Меню', 'Menús', 'Menu'],
        'nav.widgets' => ['Widgets', 'الودجات', '小部件', 'Widgets', 'Виджеты', 'Widgets', 'Widget'],
        'nav.settings' => ['Settings', 'الإعدادات', '设置', 'Paramètres', 'Настройки', 'Configuración', 'Pengaturan'],
        'nav.general' => ['General', 'عام', '常规', 'Général', 'Общие', 'General', 'Umum'],
        'nav.reading' => ['Reading', 'القراءة', '阅读', 'Lecture', 'Чтение', 'Lectura', 'Membaca'],
        'nav.permalink' => ['Permalink', 'الرابط الدائم', '固定链接', 'Permalien', 'Постоянная ссылка', 'Enlace permanente', 'Permalink'],
        'nav.timezone' => ['Timezone', 'المنطقة الزمنية', '时区', 'Fuseau horaire', 'Часовой пояс', 'Zona horaria', 'Zona Waktu'],
        'nav.membership' => ['Membership', 'العضوية', '会员资格', 'Adhésion', 'Членство', 'Membresía', 'Kanggotaan'],
        'nav.download_settings' => ['Download Settings', 'إعدادات التنزيل', '下载设置', 'Paramètres de téléchargement', 'Настройки загрузки', 'Configuración de descarga', 'Pengaturan Unduhan'],
        'nav.mail_settings' => ['Mail Settings', 'إعدادات البريد', '邮件设置', 'Paramètres de messagerie', 'Настройки почты', 'Configuración de correo', 'Pengaturan Surel'],
        'nav.api' => ['API', 'API', 'API', 'API', 'API', 'API', 'API'],
        'nav.api_settings' => ['API Settings', 'إعدادات API', 'API设置', 'Paramètres API', 'Настройки API', 'Configuración API', 'Pengaturan API'],
        'nav.plugins' => ['Plugins', 'الإضافات', '插件', 'Extensions', 'Плагины', 'Complementos', 'Plugin'],
        'nav.privacy' => ['Privacy', 'الخصوصية', '隐私', 'Confidentialité', 'Конфиденциальность', 'Privacidad', 'Privasi'],
        'nav.privacy_settings' => ['Privacy Settings', 'إعدادات الخصوصية', '隐私设置', 'Paramètres de confidentialité', 'Настройки конфиденциальности', 'Configuración de privacidad', 'Pengaturan Privasi'],
        'nav.data_requests' => ['Data Requests', 'طلبات البيانات', '数据请求', 'Demandes de données', 'Запросы данных', 'Solicitudes de datos', 'Permintaan Data'],
        'nav.audit_logs' => ['Audit Logs', 'سجلات التدقيق', '审计日志', 'Journaux d\'audit', 'Журналы аудита', 'Registros de auditoría', 'Log Audit'],
        'nav.privacy_policy' => ['Privacy Policy', 'سياسة الخصوصية', '隐私政策', 'Politique de confidentialité', 'Политика конфиденциальности', 'Política de Privacidad', 'Kebijakan Privasi'],
        'nav.languages' => ['Languages', 'اللغات', '语言', 'Langues', 'Языки', 'Idiomas', 'Bahasa'],
        'nav.all_languages' => ['All Languages', 'جميع اللغات', '所有语言', 'Toutes les langues', 'Все языки', 'Todos los idiomas', 'Semua Bahasa'],
        'nav.translations' => ['Translations', 'الترجمات', '翻译', 'Traductions', 'Переводы', 'Traducciones', 'Terjemahan'],
        'nav.language_settings' => ['Choose your language', 'اختر لغتك', '选择您的语言', 'Choisissez votre langue', 'Выберите язык', 'Elige tu idioma', 'Pilih bahasa Anda'],
        'nav.language_config' => ['Language Configuration', 'تكوين اللغة', '语言配置', 'Configuration linguistique', 'Конфигурация языка', 'Configuración de idioma', 'Konfigurasi Bahasa'],
        'nav.add_language' => ['Add Language', 'إضافة لغة', '添加语言', 'Ajouter une langue', 'Добавить язык', 'Añadir idioma', 'Tambah Bahasa'],
        'nav.edit_language' => ['Edit Language', 'تعديل اللغة', '编辑语言', 'Modifier la langue', 'Редактировать язык', 'Editar idioma', 'Edit Bahasa'],
        'nav.delete_language' => ['Delete Language', 'حذف اللغة', '删除语言', 'Supprimer la langue', 'Удалить язык', 'Eliminar idioma', 'Hapus Bahasa'],
        'nav.set_default' => ['Set Default', 'تعيين افتراضي', '设为默认', 'Définir par défaut', 'Установить по умолчанию', 'Establecer por defecto', 'Jadikan Default'],
        'nav.no_languages' => ['No languages found', 'لا توجد لغات', '没有语言', 'Pas de langues', 'Нет языков', 'Sin idiomas', 'Tidak Ada Bahasa'],
        'nav.media_library' => ['Media Library', 'مكتبة الوسائط', '媒体库', 'Médiothèque', 'Медиатека', 'Biblioteca de medios', 'Pustaka Media'],
      ],
      'form' => [
        'form.save' => ['Save', 'حفظ', '保存', 'Enregistrer', 'Сохранить', 'Guardar', 'Simpan'],
        'form.cancel' => ['Cancel', 'إلغاء', '取消', 'Annuler', 'Отмена', 'Cancelar', 'Batal'],
        'form.delete' => ['Delete', 'حذف', '删除', 'Supprimer', 'Удалить', 'Eliminar', 'Hapus'],
        'form.edit' => ['Edit', 'تعديل', '编辑', 'Modifier', 'Редактировать', 'Editar', 'Edit'],
        'form.submit' => ['Submit', 'إرسال', '提交', 'Soumettre', 'Отправить', 'Enviar', 'Kirim'],
        'form.search' => ['Search', 'بحث', '搜索', 'Rechercher', 'Поиск', 'Buscar', 'Cari'],
        'form.email' => ['Email', 'البريد الإلكتروني', '邮箱', 'Email', 'Email', 'Email', 'Email'],
        'form.name' => ['Name', 'الاسم', '姓名', 'Nom', 'Имя', 'Nombre', 'Nama'],
        'form.password' => ['Password', 'كلمة المرور', '密码', 'Mot de passe', 'Пароль', 'Contraseña', 'Kata sandi'],
        'form.username' => ['Username', 'اسم المستخدم', '用户名', 'Nom d\'utilisateur', 'Имя пользователя', 'Nombre de usuario', 'Nama Pengguna'],
        'form.title' => ['Title', 'العنوان', '标题', 'Titre', 'Заголовок', 'Título', 'Judul'],
        'form.content' => ['Content', 'المحتوى', '内容', 'Contenu', 'Содержание', 'Contenido', 'Konten'],
        'form.slug' => ['Slug', 'الرابط', '别名', 'Slug', 'Ярлык', 'Slug', 'Slug'],
        'form.status' => ['Status', 'الحالة', '状态', 'Statut', 'Статус', 'Estado', 'Status'],
        'form.visibility' => ['Visibility', 'الظهور', '可见性', 'Visibilité', 'Видимость', 'Visibilidad', 'Visibilitas'],
        'form.author' => ['Author', 'المؤلف', '作者', 'Auteur', 'Автор', 'Autor', 'Penulis'],
        'form.date' => ['Date', 'التاريخ', '日期', 'Date', 'Дата', 'Fecha', 'Tanggal'],
        'form.category' => ['Category', 'الفئة', '分类', 'Catégorie', 'Категория', 'Categoría', 'Kategori'],
        'form.tags' => ['Tags', 'الوسوم', '标签', 'Tags', 'Метки', 'Etiquetas', 'Tag'],
        'form.featured_image' => ['Featured Image', 'صورة مميزة', '特色图片', 'Image mise en avant', 'Изображение', 'Imagen destacada', 'Gambar Unggulan'],
        'form.excerpt' => ['Excerpt', 'مقتطف', '摘要', 'Extrait', 'Выдержка', 'Extracto', 'Kutipan'],
        'form.order' => ['Order', 'الترتيب', '排序', 'Ordre', 'Порядок', 'Orden', 'Urutan'],
        'form.parent' => ['Parent', 'الأصل', '父级', 'Parent', 'Родитель', 'Padre', 'Induk'],
        'form.link' => ['Link', 'الرابط', '链接', 'Lien', 'Ссылка', 'Enlace', 'Tautan'],
        'form.target' => ['Target', 'الهدف', '目标', 'Cible', 'Цель', 'Objetivo', 'Target'],
      ],
      'button' => [
        'button.add' => ['Add New', 'إضافة جديد', '新建', 'Ajouter', 'Добавить', 'Añadir nuevo', 'Tambah Baru'],
        'button.read_more' => ['Read More', 'اقرأ المزيد', '阅读更多', 'Lire la suite', 'Читать далее', 'Leer más', 'Baca selengkapnya'],
        'button.subscribe' => ['Subscribe', 'اشترك', '订阅', 'S\'abonner', 'Подписаться', 'Suscribirse', 'Berlangganan'],
        'button.update' => ['Update', 'تحديث', '更新', 'Mettre à jour', 'Обновить', 'Actualizar', 'Perbarui'],
        'button.publish' => ['Publish', 'نشر', '发布', 'Publier', 'Опубликовать', 'Publicar', 'Terbitkan'],
        'button.draft' => ['Save Draft', 'حفظ كمسودة', '保存草稿', 'Enregistrer le brouillon', 'Сохранить черновик', 'Guardar borrador', 'Simpan Draft'],
        'button.preview' => ['Preview', 'معاينة', '预览', 'Aperçu', 'Предпросмотр', 'Vista previa', 'Pratinjau'],
        'button.restore' => ['Restore', 'استعادة', '恢复', 'Restaurer', 'Восстановить', 'Restaurar', 'Pulihkan'],
        'button.approve' => ['Approve', 'موافقة', '批准', 'Approuver', 'Утвердить', 'Aprobar', 'Setuju'],
        'button.spam' => ['Mark as Spam', 'وضع علامة كبريد مزعج', '标记为垃圾邮件', 'Marquer comme spam', 'Отметить как спам', 'Marcar como spam', 'Tandai sebagai Spam'],
        'button.trash' => ['Move to Trash', 'نقل إلى سلة المهملات', '移到回收站', 'Mettre à la corbeille', 'Переместить в корзину', 'Mover a la papelera', 'Pindahkan ke Sampah'],
      ],
      'error' => [
        'error.not_found' => ['Page not found', 'الصفحة غير موجودة', '页面未找到', 'Page non trouvée', 'Страница не найдена', 'Página no encontrada', 'Halaman Tidak Ditemukan'],
        'error.forbidden' => ['Access denied', 'الوصول مرفوض', '访问被拒绝', 'Accès refusé', 'Доступ запрещен', 'Acceso denegado', 'Akses Ditolak'],
        'error.server_error' => ['Server error', 'خطأ في الخادم', '服务器错误', 'Erreur serveur', 'Ошибка сервера', 'Error del servidor', 'Kesalahan Server'],
        'error.invalid_input' => ['Invalid input', 'إدخال غير صالح', '输入无效', 'Entrée invalide', 'Неверный ввод', 'Entrada inválida', 'Input Tidak Valid'],
        'error.required' => ['This field is required', 'هذا الحقل مطلوب', '此字段为必填项', 'Ce champ est requis', 'Это поле обязательно', 'Este campo es obligatorio', 'Kolom ini wajib diisi'],
      ],
      'footer' => [
        'footer.copyright' => ['All rights reserved', 'جميع الحقوق محفوظة', '版权所有', 'Tous droits réservés', 'Все права защищены', 'Todos los derechos reservados', 'Semua Hak Dilindungi'],
      ],
      'status' => [
        'status.publish' => ['Published', 'منشور', '已发布', 'Publié', 'Опубликовано', 'Publicado', 'Diterbitkan'],
        'status.draft' => ['Draft', 'مسودة', '草稿', 'Brouillon', 'Черновик', 'Borrador', 'Draft'],
        'status.pending' => ['Pending Review', 'في انتظار المراجعة', '待审核', 'En attente de révision', 'На рассмотрении', 'Pendiente de revisión', 'Menunggu Tinjauan'],
        'status.private' => ['Private', 'خاص', '私有', 'Privé', 'Частный', 'Privado', 'Pribadi'],
        'status.trash' => ['Trash', 'سلة المهملات', '回收站', 'Corbeille', 'Корзина', 'Papelera', 'Sampah'],
      ],
      'visibility' => [
        'visibility.public' => ['Public', 'عام', '公开', 'Public', 'Публичный', 'Público', 'Publik'],
        'visibility.private' => ['Private', 'خاص', '私有', 'Privé', 'Частный', 'Privado', 'Pribadi'],
        'visibility.password' => ['Password Protected', 'محمي بكلمة المرور', '密码保护', 'Protégé par mot de passe', 'Защищено паролем', 'Protegido con contraseña', 'Dilindungi Kata Sandi'],
        'protected.post.description' => ['This post is password protected. Enter the password to view its content.', 'هذا المنشور محمي بكلمة مرور. أدخل كلمة المرور لعرض المحتوى.', '这篇文章受密码保护。请输入密码查看内容。', 'Ce message est protégé par un mot de passe. Entrez le mot de passe pour afficher le contenu.', 'Эта запись защищена паролем. Введите пароль для просмотра contenido.', 'Esta publicación está protegida con contraseña. Ingrese la contraseña para ver el contenido.', 'Postingan ini dilindungi kata sandi. Masukkan kata sandi untuk melihat kontennya.'],
        'button.unlock' => ['Unlock', 'فتح', '解锁', 'Déverrouiller', 'Разблокировать', 'Desbloquear', 'Buka Kunci'],
        'error.wrong_password' => ['Incorrect password. Please try again.', 'كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى.', '密码错误，请重试。', 'Mot de passe incorrect. Veuillez réessayer.', 'Неверный пароль. Пожалуйста, попробуйте снова.', 'Contraseña incorrecta. Por favor, inténtelo de nuevo.', 'Kata sandi salah. Silakan coba lagi.'],
      ],
      'admin' => [
        'admin.all_languages' => ['All Languages', 'جميع اللغات', '所有语言', 'Toutes les langues', 'Все языки', 'Todos los idiomas', 'Semua Bahasa'],
        'admin.translations' => ['Translations', 'الترجمات', '翻译', 'Traductions', 'Переводы', 'Traducciones', 'Terjemahan'],
        'admin.add_language' => ['Add Language', 'إضافة لغة', '添加语言', 'Ajouter une langue', 'Добавить язык', 'Añadir idioma', 'Tambah Bahasa'],
        'admin.edit_language' => ['Edit Language', 'تعديل اللغة', '编辑语言', 'Modifier la langue', 'Редактировать язык', 'Editar idioma', 'Edit Bahasa'],
        'admin.delete_language' => ['Delete Language', 'حذف اللغة', '删除语言', 'Supprimer la langue', 'Удалить язык', 'Eliminar idioma', 'Hapus Bahasa'],
      ],
      'header' => [
        'header.nav.home' => ['Home', 'الرئيسية', '首页', 'Accueil', 'Главная', 'Inicio', 'Beranda'],
        'header.nav.blog' => ['Blog', 'المدونة', '博客', 'Blog', 'Блог', 'Blog', 'Blog'],
        'header.nav.about' => ['About', 'من نحن', '关于', 'À propos', 'О нас', 'Acerca de', 'Tentang'],
        'header.nav.contact' => ['Contact', 'اتصل بنا', '联系', 'Contact', 'Контакты', 'Contacto', 'Hubungi'],
        'header.nav.search' => ['Search', 'بحث', '搜索', 'Recherche', 'Поиск', 'Buscar', 'Cari'],
      ],
      'sidebar' => [
        'sidebar.search.title' => ['Search', 'بحث', '搜索', 'Recherche', 'Поиск', 'Buscar', 'Cari'],
        'sidebar.search.placeholder' => ['What are you looking for?', 'ما الذي تبحث عنه؟', '你在找什么?', 'Que recherchez-vous?', 'Что вы ищете?', '¿Qué estás buscando?', 'Apa yang Anda cari?'],
        'sidebar.latest_posts.title' => ['Latest Posts', 'أحدث المشاركات', '最新文章', 'Derniers articles', 'Последние записи', 'Últimas entradas', 'Postingan Terbaru'],
        'sidebar.categories.title' => ['Categories', 'التصنيفات', '分类', 'Catégories', 'Категории', 'Categorías', 'Kategori'],
        'sidebar.archives.title' => ['Archives', 'الأرشيف', '归档', 'Archives', 'Архивы', 'Archivos', 'Arsip'],
        'sidebar.tags.title' => ['Tags', 'الوسوم', '标签', 'Tags', 'Метки', 'Etiquetas', 'Tag'],
      ],
      'home' => [
        'home.hero.discover_more' => ['Discover More', 'اكتشف المزيد', '发现更多', 'Découvrir', 'Узнать больше', 'Descubre más', 'Jelajahi'],
        'home.hero.admin_panel' => ['Go to administrator panel', 'انتقل إلى لوحة الإدارة', '转到管理面板', 'Aller au panneau admin', 'Перейти в панель админа', 'Ir al panel de admin', 'Ke panel admin'],
        'home.hero.scroll_down' => ['Scroll Down', 'تمرير للأسفل', '向下滚动', 'Défiler', 'Прокрутите вниз', 'Desplázate往下滚动', 'Gulir ke bawah'],
        'home.intro.welcome' => ['Welcome to ScriptLog', 'مرحباً بك في ScriptLog', '欢迎使用ScriptLog', 'Bienvenue sur ScriptLog', 'Добро пожаловать в ScriptLog', 'Bienvenido a ScriptLog', 'Selamat datang di ScriptLog'],
        'home.intro.description' => ['Your entryway to a personal blog that lets you easily express, create, and share your ideas. Whether you are a creative writer, hobbyist, tech enthusiast, personal blogger, or someone who values digital independence, ScriptLog empowers you to craft your online presence.', 'بوابتك الشخصية لمدونة تتيح لك التعبير عن أفكارك ومشاركتها بسهولة. سواء كنت كاتباً مبدعاً، أو هاوياً، أو متحمساً للتكنولوجيا، أو مدوناً شخصياً، أو شخصاً يقدر الاستقلالية الرقمية، يمكّنك ScriptLog من تشكيل حضورك عبر الإنترنت.', '您的博客入口，可以轻松表达、创建和分享您的想法。无论您是创意作家、爱好者、技术爱好者、个人博主，还是重视数字独立性的人，ScriptLog 都能帮助您打造在线形象。', 'Votre passerelle vers un blog personnel qui vous permet dexprimer, créer et partager vos idées facilement. Que vous soyez un écrivain créatif, un amateur, un passionné de technologie, un blogger personnel ou quelquun qui valorise lindépendance numérique, ScriptLog vous permet de créer votre présence en ligne.', 'Ваш личный блог, который позволяет легко выражать, создавать и делиться своими идеями. Являетесь ли вы творческим писателем, любителем, энтузиастом технологий, личным блогером или человеком, который ценит цифровую независимость, ScriptLog позволяет вам создать своё присутствие в интернете.', 'Tu portal hacia un blog personal que te permite expresar, crear y compartir fácilmente tus ideas. Ya seas un escritor creativo, un entusiasta de la tecnología, un blogger personal o alguien que valora la independencia digital, ScriptLog te permite construir tu presencia en línea.', 'Gerbang menuju blog pribadi yang memungkinkan Anda dengan mudah mengungkapkan, membuat, dan membagikan ide Anda. Baik Anda seorang penulis kreatif, pemula, penggemar teknologi, blogger pribadi, atau seseorang yang menghargai independensi digital, ScriptLog memberday Anda untuk membangun kehadiran online Anda.'],
        'home.latest_posts.title' => ['Latest from the blog', 'أحدث من المدونة', '博客最新动态', 'Dernier du blog', 'Последнее из блога', 'Último del blog', ' Terbaru dari blog'],
        'home.divider.view_more' => ['View More', 'المزيد', '查看更多', 'Voir plus', 'Узнать больше', 'Ver más', 'Lihat lainnya'],
      ],
      'single' => [
        'single.comment.leave_reply' => ['Leave a comment', 'اترك تعليقاً', '发表评论', 'Laisser un commentaire', 'Оставить комментарий', 'Déjar un comentario', 'Tulis komentar'],
        'single.comment.label' => ['Type your comment', 'اكتب تعليقك', '输入您的评论', 'Tapez votre commentaire', 'Введите комментарий', 'Escribe tu comentario', 'Ketik komentar Anda'],
        'single.comment.placeholder' => ['Enter your comment', 'أدخل تعليقك', '输入您的评论', 'Entrez votre commentaire', 'Введите комментарий', 'Ingresa tu comentario', 'Masukkan komentar Anda'],
        'single.comment.submit' => ['Submit Comment', 'إرسال التعليق', '提交评论', 'Soumettre le commentaire', 'Отправить комментарий', 'Enviar comentario', 'Kirim Komentar'],
      ],
      'form' => [
        'form.name.label' => ['Name', 'الاسم', '姓名', 'Nom', 'Имя', 'Nombre', 'Nama'],
        'form.name.placeholder' => ['Enter name', 'أدخل الاسم', '输入姓名', 'Entrez le nom', 'Введите имя', 'Ingrese el nombre', 'Masukkan nama'],
        'form.email.label' => ['Email (will not be published)', 'البريد الإلكتروني (لن ينشر)', '邮箱（不会发布）', 'Email (ne sera pas publié)', 'Email (не будет опубликован)', 'Email (no será publicado)', 'Email (tidak akan dipublikasikan)'],
        'form.email.placeholder' => ['Enter email', 'أدخل البريد الإلكتروني', '输入邮箱', 'Entrez lemail', 'Введите email', 'Ingrese el email', 'Masukkan email'],
      ],
      'cookie_consent' => [
        'cookie_consent.banner.title' => ['We value your privacy', 'نقدر خصوصيتك', '我们重视您的隐私', 'Nous respectons votre vie privée', 'Мы ценим вашу конфиденциальность', 'Valoramos su privacidad', 'Kami menghargai privasi Anda'],
        'cookie_consent.banner.description' => ['We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking Accept All, you consent to our use of cookies. Read our Privacy Policy to learn more.', 'نستخدم الكوكيز لتحسين تجربة التصفح وخدمة محتوى شخصي وتحليل حركة المرور. بالنقر فوق قبول الكل، فإنك توافق على استخدامنا للكوكيز. اقرأ سياسة الخصوصية لمعرفة المزيد.', '我们使用cookie来增强您的浏览体验，提供个性化内容并分析流量。点击"全部接受"即表示您同意我们使用cookie。请阅读我们的隐私政策以了解更多。', 'Nous utilisons des cookies pour améliorer votre expérience de navigation, diffuser du contenu personnalisé et analyser notre trafic. En cliquant sur Accepter tout, vous consentez à notre utilisation des cookies. Lisez notre politique de confidentialité pour en savoir plus.', 'Мы используем файлы cookie для улучшения вашего просмотра, персонализации контента и анализа трафика. Нажимая «Принять все», вы соглашаетесь с использованием нами файлов cookie. Прочитайте нашу Политику конфиденциальности, чтобы узнать больше.', 'Utilizamos cookies para mejorar tu experiencia de navegación, ofrecer contenido personalizado y analizar nuestro tráfico. Al hacer clic en Aceptar todo, aceptas nuestro uso de cookies. Lee nuestra Política de Privacidad para obtener más información.', 'Kami menggunakan cookie untuk meningkatkan pengalaman menjelajah Anda, menyajikan konten yang dipersonalisasi, dan menganalisis traffic kami. Dengan mengklik Terima Semua, Anda menyetujui penggunaan kami atas cookie. Baca Kebijakan Privasi kami untuk mempelajari lebih lanjut.'],
        'cookie_consent.buttons.accept' => ['Accept All', 'قبول الكل', '全部接受', 'Accepter tout', 'Принять все', 'Aceptar todo', 'Terima Semua'],
        'cookie_consent.buttons.reject' => ['Reject All', 'رفض الكل', '全部拒绝', 'Tout rejeter', 'Отклонить все', 'Rechazar todo', 'Tolak Semua'],
        'cookie_consent.buttons.learn_more' => ['Learn More', 'معرفة المزيد', '了解更多', 'En savoir plus', 'Узнать больше', 'Saber más', 'Pelajari Lebih Lanjut'],
        'cookie_consent.privacy.link' => ['Privacy Policy', 'سياسة الخصوصية', '隐私政策', 'Politique de confidentialité', 'Политика конфиденциальности', 'Política de Privacidad', 'Kebijakan Privasi'],
      ],
      '404' => [
        '404.title' => ['404', '404', '404', '404', '404', '404', '404'],
        '404.message' => ['The page you are looking for was not found.', 'الصفحة التي تبحث عنها غير موجودة.', '找不到您要查找的页面。', 'La page que vous recherchez na pas été trouvée.', 'Страница, которую вы ищете, не найдена.', 'La página que buscas no fue encontrada.', 'Halaman yang Anda cari tidak ditemukan.'],
        '404.back_home' => ['Back to Home', 'العودة إلى الصفحة الرئيسية', '返回首页', 'Retour à la page daccueil', 'Вернуться на главную', 'Volver al inicio', 'Kembali ke Beranda'],
      ],
      'privacy' => [
        'privacy.page_title' => ['Privacy Policy', 'سياسة الخصوصية', '隐私政策', 'Politique de confidentialité', 'Политика конфиденциальности', 'Política de Privacidad', 'Kebijakan Privasi'],
        'privacy.last_updated' => ['Last updated', 'آخر تحديث', '最后更新', 'Dernière mise à jour', 'Последнее обновление', 'Última actualización', 'Terakhir diperbarui'],
        'privacy.information_we_collect' => ['Information We Collect', 'المعلومات التي نجمعها', '我们收集的信息', 'Informations que nous collectons', 'Собираемая информация', 'Información que recopilamos', 'Informasi yang Kami Kumpulkan'],
        'privacy.how_we_use' => ['How We Use Your Information', 'كيف نستخدم معلوماتك', '我们如何使用您的信息', 'Comment nous utilisons vos informations', 'Как мы используем вашу информацию', 'Cómo usamos su información', 'Cara Kami Menggunakan Informasi Anda'],
        'privacy.data_security' => ['Data Security', 'أمان البيانات', '数据安全', 'Sécurité des données', 'Безопасность данных', 'Seguridad de los datos', 'Keamanan Data'],
        'privacy.your_rights' => ['Your Rights', 'حقوقك', '您的权利', 'Vos droits', 'Ваши права', 'Sus derechos', 'Hak Anda'],
        'privacy.contact_us' => ['Contact Us', 'اتصل بنا', '联系我们', 'Contactez-nous', 'Связаться с нами', 'Contáctenos', 'Hubungi Kami'],
      ],
      'page' => [
        'page.static_page' => ['Static Page', 'صفحة ثابتة', '静态页面', 'Page statique', 'Статическая страница', 'Página estática', 'Halaman Statis'],
      ],
    ];

    $langOrder = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
    $langIds = [];

    foreach ($langOrder as $index => $code) {
        $lang = $languages[$code];
        $isDefault = ($code === $default_lang) ? 1 : 0;
        $stmt = $link->prepare("INSERT INTO {$prefix}tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default, lang_is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssssii", $code, $lang['name'], $lang['native'], $lang['locale'], $lang['direction'], $lang['sort'], $isDefault);
        $stmt->execute();
        $langIds[$code] = $link->insert_id;
    }

    $insertTrans = $link->prepare("INSERT INTO {$prefix}tbl_translations (lang_id, translation_key, translation_value, translation_context) VALUES (?, ?, ?, ?)");

    foreach ($translations as $context => $keys) {
        foreach ($keys as $key => $values) {
            foreach ($values as $index => $value) {
                $code = $langOrder[$index];
                $insertTrans->bind_param("isss", $langIds[$code], $key, $value, $context);
                $insertTrans->execute();
            }
        }
    }

    $langCodes = implode(',', $langOrder);
    $updateSettings = $link->prepare("UPDATE {$prefix}tbl_settings SET setting_value = ? WHERE setting_name = 'lang_available'");
    $updateSettings->bind_param("s", $langCodes);
    $updateSettings->execute();

    $updateDefaultLang = $link->prepare("UPDATE {$prefix}tbl_settings SET setting_value = ? WHERE setting_name = 'lang_default'");
    $updateDefaultLang->bind_param("s", $default_lang);
    $updateDefaultLang->execute();

    $updateAutoDetect = $link->prepare("UPDATE {$prefix}tbl_settings SET setting_value = ? WHERE setting_name = 'lang_auto_detect'");
    $autoDetectValue = '1';
    $updateAutoDetect->bind_param("s", $autoDetectValue);
    $updateAutoDetect->execute();

    $updatePrefixRequired = $link->prepare("UPDATE {$prefix}tbl_settings SET setting_value = ? WHERE setting_name = 'lang_prefix_required'");
    $prefixRequiredValue = '1';
    $updatePrefixRequired->bind_param("s", $prefixRequiredValue);
    $updatePrefixRequired->execute();

    install_privacy_policy_data($link, $prefix);
}

/**
 * install_privacy_policy_data()
 *
 * Populate privacy policies table with default content per locale
 *
 * @param object $link
 * @param string $prefix
 * @return void
 */
function install_privacy_policy_data($link, $prefix = '')
{
    $defaultPolicies = [
      'en' => [
        'title' => 'Privacy Policy',
        'content' => '<h2>Information We Collect</h2>
<p>We may collect the following types of information:</p>
<ul>
<li><strong>Account Information:</strong> When you create an account, we may collect your username, email address, and a hashed version of your password.</li>
<li><strong>User-Generated Content:</strong> Any content you create, such as blog posts, comments, and uploaded media, is stored on our servers.</li>
<li><strong>Technical Information:</strong> We may automatically collect information about your device and how you interact with our software, including your IP address, browser type, and operating system.</li>
</ul>
<h2>How We Use Your Information</h2>
<p>We use the information we collect for the following purposes:</p>
<ul>
<li>To provide, operate, and maintain our services.</li>
<li>To manage your account and provide you with customer support.</li>
<li>To improve the security and performance of our software.</li>
<li>To monitor usage and analyze trends to enhance user experience.</li>
</ul>
<h2>Data Security</h2>
<p>We take the security of your data seriously and implement a variety of measures to protect it. These include password hashing, data encryption, XSS and CSRF protection, and prepared statements for all database queries.</p>
<h2>Your Rights</h2>
<p>You have the right to access, update, or delete the information we have on you.</p>
<h2>Contact Us</h2>
<p>If you have any questions about this Privacy Policy, please contact us.</p>'
      ],
      'ar' => [
        'title' => 'سياسة الخصوصية',
        'content' => '<h2>المعلومات التي نجمعها</h2>
<p>قد نجمع الأنواع التالية من المعلومات:</p>
<ul>
<li><strong>معلومات الحساب:</strong> عند إنشاء حساب، قد نجمع اسم المستخدم والبريد الإلكتروني وكلمة المرور.</li>
<li><strong>المحتوى المُنشأ:</strong> يتم تخزين أي محتوى تنشئه على خوادمنا.</li>
<li><strong>المعلومات التقنية:</strong> قد نجمع معلومات حول جهازك.</li>
</ul>
<h2>كيف نستخدم معلوماتك</h2>
<p>نستخدم المعلومات للآتي:</p>
<ul>
<li>لتقديم خدماتنا وصيانتها.</li>
<li>لإدارة حسابك.</li>
<li>لتحسين الأمان والأداء.</li>
</ul>
<h2>أمان البيانات</h2>
<p>نأخذ أمان بياناتك بجدية وننفذ تدابير متنوعة للحماية.</p>
<h2>حقوقك</h2>
<p>لديك الحق في الوصول إلى معلوماتك وتحديثها أو حذفها.</p>
<h2>اتصل بنا</h2>
<p>إذا كانت لديك أسئلة حول سياسة الخصوصية هذه، يرجى التواصل معنا.</p>'
      ],
      'zh' => [
        'title' => '隐私政策',
        'content' => '<h2>我们收集的信息</h2>
<p>我们可能收集以下类型的信息：</p>
<ul>
<li><strong>账户信息：</strong>创建账户时，我们可能会收集您的用户名、电子邮件地址和密码的哈希版本。</li>
<li><strong>用户生成的内容：</strong>您在博客上创建的任何内容都会保存在我们的服务器上。</li>
<li><strong>技术信息：</strong>我们可能自动收集有关您设备的信息。</li>
</ul>
<h2>我们如何使用您的信息</h2>
<p>我们使用收集的信息用于以下目的：</p>
<ul>
<li>提供、维护和改进我们的服务。</li>
<li>管理您的账户。</li>
<li>增强用户体验。</li>
</ul>
<h2>数据安全</h2>
<p>我们非常重视数据安全，并实施各种保护措施。</p>
<h2>您的权利</h2>
<p>您有权访问、更新或删除您的信息。</p>
<h2>联系我们</h2>
<p>如果您有任何问题，请联系我们。</p>'
      ],
      'fr' => [
        'title' => 'Politique de Confidentialité',
        'content' => '<h2>Informations que nous collectons</h2>
<p>Nous pouvons collecter les types d\'informations suivants :</p>
<ul>
<li><strong>Informations de compte :</strong> Lorsque vous créez un compte, nous collectons votre nom d\'utilisateur, adresse e-mail et un hash de votre mot de passe.</li>
<li><strong>Contenu généré par l\'utilisateur :</strong> Tout contenu que vous créez est stocké sur nos serveurs.</li>
<li><strong>Informations techniques :</strong> Nous pouvons collecter automatiquement des informations sur votre appareil.</li>
</ul>
<h2>Comment nous utilisons vos informations</h2>
<p>Nous utilisons les informations collectées pour :</p>
<ul>
<li>Fournir et maintenir nos services.</li>
<li>Gérer votre compte.</li>
<li>Améliorer l\'expérience utilisateur.</li>
</ul>
<h2>Sécurité des données</h2>
<p>Nous prenons la sécurité des données au sérieux et mettons en œuvre diverses mesures de protection.</p>
<h2>Vos droits</h2>
<p>Vous avez le droit d\'accéder, de mettre à jour ou de supprimer vos informations.</p>
<h2>Contactez-nous</h2>
<p>Si vous avez des questions, veuillez nous contacter.</p>'
      ],
      'ru' => [
        'title' => 'Политика конфиденциальности',
        'content' => '<h2>Информация, которую мы собираем</h2>
<p>Мы можем собирать следующие типы информации:</p>
<ul>
<li><strong>Информация об учетной записи:</strong> При создании учетной записи мы можем собирать ваше имя пользователя, адрес электронной почты и хэшированный пароль.</li>
<li><strong>Контент пользователя:</strong> Любой контент, который вы создаете, хранится на наших серверах.</li>
<li><strong>Техническая информация:</strong> Мы можем автоматически собирать информацию о вашем устройстве.</li>
</ul>
<h2>Как мы используем вашу информацию</h2>
<p>Мы используем собранную информацию для:</p>
<ul>
<li>Предоставления и поддержки наших услуг.</li>
<li>Управления вашей учетной записью.</li>
<li>Улучшения пользовательского опыта.</li>
</ul>
<h2>Безопасность данных</h2>
<p>Мы серьезно относимся к безопасности данных и внедряем различные меры защиты.</p>
<h2>Ваши права</h2>
<p>Вы имеете право на доступ, обновление или удаление вашей информации.</p>
<h2>Свяжитесь с нами</h2>
<p>Если у вас есть вопросы, свяжитесь с нами.</p>'
      ],
      'es' => [
        'title' => 'Política de Privacidad',
        'content' => '<h2>Información que recopilamos</h2>
<p>Podemos recopilar los siguientes tipos de información:</p>
<ul>
<li><strong>Información de cuenta:</strong> Al crear una cuenta, podemos recopilar su nombre de usuario, correo electrónico y una versión hash de su contraseña.</li>
<li><strong>Contenido generado por el usuario:</strong> Cualquier contenido que cree se almacena en nuestros servidores.</li>
<li><strong>Información técnica:</strong> Podemos recopilar automáticamente información sobre su dispositivo.</li>
</ul>
<h2>Cómo usamos su información</h2>
<p>Usamos la información recopilada para:</p>
<ul>
<li>Proporcionar y mantener nuestros servicios.</li>
<li>Gestionar su cuenta.</li>
<li>Mejorar la experiencia del usuario.</li>
</ul>
<h2>Seguridad de los datos</h2>
<p>Nos tomamos en serio la seguridad de los datos e implementamos diversas medidas de protección.</p>
<h2>Tus derechos</h2>
<p>Tienes derecho a acceder, actualizar o eliminar tu información.</p>
<h2>Contáctenos</h2>
<p>Si tienes preguntas, contáctenos.</p>'
      ],
      'id' => [
        'title' => 'Kebijakan Privasi',
        'content' => '<h2>Informasi yang Kami Kumpulkan</h2>
<p>Kami dapat mengumpulkan jenis informasi berikut:</p>
<ul>
<li><strong>Informasi Akun:</strong> Saat Anda membuat akun, kami dapat mengumpulkan nama pengguna, surel, dan versi hash kata sandi Anda.</li>
<li><strong>Konten yang Dihasilkan Pengguna:</strong> Setiap konten yang Anda buat disimpan di server kami.</li>
<li><strong>Informasi Teknis:</strong> Kami dapat secara otomatis mengumpulkan informasi tentang perangkat Anda.</li>
</ul>
<h2>Cara Kami Menggunakan Informasi Anda</h2>
<p>Kami menggunakan informasi yang dikumpulkan untuk:</p>
<ul>
<li>Menyediakan dan mempertahankan layanan kami.</li>
<li>Mengelola akun Anda.</li>
<li>Meningkatkan pengalaman pengguna.</li>
</ul>
<h2>Keamanan Data</h2>
<p>Kami mengambil keamanan data secara serius dan menerapkan berbagai tindakan perlindungan.</p>
<h2>Hak Anda</h2>
<p>Anda berhak untuk mengakses, memperbarui, atau menghapus informasi Anda.</p>
<h2>Hubungi Kami</h2>
<p>Jika Anda memiliki pertanyaan, hubungi kami.</p>'
      ],
    ];

    $insertPolicy = $link->prepare("INSERT INTO {$prefix}tbl_privacy_policies (locale, policy_title, policy_content, is_default) VALUES (?, ?, ?, ?)");

    $defaultLang = 'en';
    foreach ($defaultPolicies as $locale => $policy) {
        $isDefault = ($locale === $defaultLang) ? 1 : 0;
        $insertPolicy->bind_param("sssi", $locale, $policy['title'], $policy['content'], $isDefault);
        $insertPolicy->execute();
    }
}

/**
 * grab_app_key()
 *
 * @param object $mysqli
 * @param string $key
 * @param string $prefix
 * @return string
 *
 */
function grab_app_key($mysqli, $key, $prefix = '')
{
    $sql = "SELECT ID, setting_name, setting_value 
          FROM {$prefix}tbl_settings USE INDEX(setting_value) WHERE setting_value = '$key'";

    $row = mysqli_fetch_assoc($mysqli->query($sql));

    $app_key = isset($row['setting_value']) ? htmlspecialchars($row['setting_value']) : "";
    $id = isset($row['ID']) ? abs((int)$row['ID']) : "";

    return array('app_key' => generate_license(substr($app_key, 0, 6)), 'ID' => $id);
}

/**
 * update_app_key()
 *
 * @param object $mysqli
 * @param string $app_key
 * @param int|num $id
 * @param string $prefix
 * @return void
 *
 */
function update_app_key($mysqli, $app_key, $id, $prefix = '')
{
    $sql = "UPDATE {$prefix}tbl_settings SET setting_value = '$app_key'
          WHERE setting_name = 'app_key' AND ID = {$id} LIMIT 1";
    return $mysqli->query($sql);
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
 * @param int $dbport
 * @param string $email
 * @param string $key
 * @param string $ca
 * @param string $prefix
 * @throws Exception
 *
 */
function write_config_file($protocol, $server_name, $dbhost, $dbpassword, $dbuser, $dbname, $dbport, $email, $key, $ca, $prefix = '')
{
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $link = ($ca !== false) ? make_secure_connection($dbhost, $dbuser, $dbpassword, $dbname, $dbport, $ca) : make_connection($dbhost, $dbuser, $dbpassword, $dbname, $dbport);

    $configuration = false;

    if (isset($_SESSION['install']) && $_SESSION['install'] === true) {
        $distro = isset(get_linux_distro()['NAME']) ? get_linux_distro()['NAME'] : "";

        // Generate Defuse encryption key for authentication - system generates random path outside web root
        try {
            $defuse_key_full_path = generate_defuse_key();
        } catch (Exception $e) {
            error_log("Failed to generate defuse key: " . $e->getMessage());
            $appRoot = dirname(__DIR__, 2);
            $defuse_key_full_path = $appRoot . '/lib/utility/.lts/lts.php';
        }
        
        // Use absolute path for config.php and .env
        $defuseKeyPath = $defuse_key_full_path;

        $grabbedKey = grab_app_key($link, $key, $prefix);
        $app_key = isset($grabbedKey['app_key']) ? $grabbedKey['app_key'] : "";
        $app_id = isset($grabbedKey['ID']) ? $grabbedKey['ID'] : "";

        update_app_key($link, $app_key, $app_id, $prefix);

        mysqli_close($link);

        $prefixLine = (!empty($prefix)) ? "'prefix' => '" . addslashes($prefix) . "'," : "";

        // Build config.php using $_ENV pattern to support both .env and config.php
        $configFile = '<?php' . PHP_EOL . PHP_EOL;
        $configFile .= 'return [' . PHP_EOL;
        $configFile .= '    \'db\' => [' . PHP_EOL;
        $configFile .= '        \'host\' => $_ENV[\'DB_HOST\'] ?? \'' . addslashes($dbhost) . '\',' . PHP_EOL;
        $configFile .= '        \'user\' => $_ENV[\'DB_USER\'] ?? \'' . addslashes($dbuser) . '\',' . PHP_EOL;
        $configFile .= '        \'pass\' => $_ENV[\'DB_PASS\'] ?? \'' . addslashes($dbpassword) . '\',' . PHP_EOL;
        $configFile .= '        \'name\' => $_ENV[\'DB_NAME\'] ?? \'' . addslashes($dbname) . '\',' . PHP_EOL;
        $configFile .= '        \'port\' => $_ENV[\'DB_PORT\'] ?? \'' . addslashes($dbport) . '\',' . PHP_EOL;
        $configFile .= '        \'prefix\' => $_ENV[\'DB_PREFIX\'] ?? \'' . addslashes($prefix) . '\',' . PHP_EOL;
        $configFile .= '    ],' . PHP_EOL;
        $configFile .= PHP_EOL;
        $configFile .= '    \'app\' => [' . PHP_EOL;
        $configFile .= '        \'url\'   => $_ENV[\'APP_URL\'] ?? \'' . addslashes(setup_base_url($protocol, $server_name)) . '\',' . PHP_EOL;
        $configFile .= '        \'email\' => $_ENV[\'APP_EMAIL\'] ?? \'' . addslashes($email) . '\',' . PHP_EOL;
        $configFile .= '        \'key\'   => $_ENV[\'APP_KEY\'] ?? \'' . addslashes($app_key) . '\',' . PHP_EOL;
        // Store the path to encryption key file - uses $_ENV so .env can override with path outside document root
        $configFile .= '        \'defuse_key\' => $_ENV[\'DEFUSE_KEY_PATH\'] ?? \'' . addslashes($defuseKeyPath) . '\',' . PHP_EOL;
        $configFile .= '    ],' . PHP_EOL;
        $configFile .= PHP_EOL;
        $configFile .= '    \'mail\' => [' . PHP_EOL;
        $configFile .= '        \'smtp\' => [' . PHP_EOL;
        $configFile .= '            \'host\' => $_ENV[\'SMTP_HOST\'] ?? \'\',' . PHP_EOL;
        $configFile .= '            \'port\' => $_ENV[\'SMTP_PORT\'] ?? 587,' . PHP_EOL;
        $configFile .= '            \'encryption\' => $_ENV[\'SMTP_ENCRYPTION\'] ?? \'tls\',' . PHP_EOL;
        $configFile .= '            \'username\' => $_ENV[\'SMTP_USER\'] ?? \'\',' . PHP_EOL;
        $configFile .= '            \'password\' => $_ENV[\'SMTP_PASS\'] ?? \'\',' . PHP_EOL;
        $configFile .= '        ],' . PHP_EOL;
        $configFile .= '        \'from\' => [' . PHP_EOL;
        $configFile .= '            \'email\' => $_ENV[\'MAIL_FROM_ADDRESS\'] ?? \'' . addslashes($email) . '\',' . PHP_EOL;
        $configFile .= '            \'name\' => $_ENV[\'MAIL_FROM_NAME\'] ?? \'Blogware\'' . PHP_EOL;
        $configFile .= '        ]' . PHP_EOL;
        $configFile .= '    ],' . PHP_EOL;
        $configFile .= PHP_EOL;
        $configFile .= '    \'os\' => [' . PHP_EOL;
        $configFile .= '        \'system_software\' => $_ENV[\'SYSTEM_OS\'] ?? \'' . addslashes(check_os()['Operating_system']) . '\',' . PHP_EOL;
        $configFile .= '        \'distrib_name\'    => $_ENV[\'DISTRIB_NAME\'] ?? \'' . trim($distro) . '\'' . PHP_EOL;
        $configFile .= '    ],' . PHP_EOL;
        $configFile .= PHP_EOL;
        $configFile .= '    \'api\' => [' . PHP_EOL;
        $configFile .= '        \'allowed_origins\' => $_ENV[\'CORS_ALLOWED_ORIGINS\'] ?? \'' . addslashes(setup_base_url($protocol, $server_name)) . '\'' . PHP_EOL;
        $configFile .= '    ],' . PHP_EOL;
        $configFile .= '];' . PHP_EOL;

        if (isset($_SESSION['token'])) {
            // Write config.php - calculate correct path
            $rootDir = dirname(dirname(__DIR__)); // From install/include to root
            $configPath = $rootDir . '/config.php';
            
            // Debug: Log the path
            error_log("DEBUG: Attempting to write config.php to: " . $configPath);
            
            $result = file_put_contents($configPath, $configFile, LOCK_EX);
            
            // If failed, try alternate path
            if ($result === false) {
                $configPath = dirname(__DIR__, 2) . '/config.php';
                $result = file_put_contents($configPath, $configFile, LOCK_EX);
                error_log("DEBUG: Tried alternate path: " . $configPath . " result: " . ($result !== false ? "SUCCESS" : "FAILED"));
            }

            if ($result !== false) {
                // Also generate .env file for environment variables (pass full key path)
                $envPath = $rootDir . '/.env';
                write_env_file($protocol, $server_name, $dbhost, $dbuser, $dbpassword, $dbname, $dbport, $prefix, $email, $app_key, $distro, $defuseKeyPath);
                
                $configuration = true;
                error_log("DEBUG: Configuration completed successfully");
            } else {
                error_log("ERROR: Failed to write config.php");
            }
        } else {
            error_log("DEBUG: Session token not set - config will not be written");
        }
    }  // <-- This was missing

    return $configuration;
}

/**
 * Write .env file with environment variables
 *
 * @param string $protocol
 * @param string $server_name
 * @param string $dbhost
 * @param string $dbuser
 * @param string $dbpass
 * @param string $dbname
 * @param string $dbport
 * @param string $prefix
 * @param string $email
 * @param string $app_key
 * @param string $distro
 * @param string $defuse_key_path
 */
function write_env_file($protocol, $server_name, $dbhost, $dbuser, $dbpass, $dbname, $dbport, $prefix, $email, $app_key, $distro, $defuse_key_path = '')
{
    $envContent = "# --- DATABASE CONFIGURATION ---" . PHP_EOL;
    $envContent .= "DB_HOST=" . addslashes($dbhost) . PHP_EOL;
    $envContent .= "DB_USER=" . addslashes($dbuser) . PHP_EOL;
    $envContent .= "DB_PASS=" . addslashes($dbpass) . PHP_EOL;
    $envContent .= "DB_NAME=" . addslashes($dbname) . PHP_EOL;
    $envContent .= "DB_PORT=" . addslashes($dbport) . PHP_EOL;
    $envContent .= "DB_PREFIX=" . addslashes($prefix) . PHP_EOL;
    $envContent .= PHP_EOL;
    $envContent .= "# --- APPLICATION CONFIGURATION ---" . PHP_EOL;
    $envContent .= "APP_URL=" . addslashes(setup_base_url($protocol, $server_name)) . PHP_EOL;
    $envContent .= "APP_EMAIL=" . addslashes($email) . PHP_EOL;
    $envContent .= "APP_KEY=" . addslashes($app_key) . PHP_EOL;
    $envContent .= "DEFUSE_KEY_PATH=" . addslashes($defuse_key_path) . PHP_EOL;
    $envContent .= PHP_EOL;
    $envContent .= "# --- MAIL / SMTP CONFIGURATION ---" . PHP_EOL;
    $envContent .= "SMTP_HOST=" . PHP_EOL;
    $envContent .= "SMTP_PORT=587" . PHP_EOL;
    $envContent .= "SMTP_USER=" . PHP_EOL;
    $envContent .= "SMTP_PASS=" . PHP_EOL;
    $envContent .= "SMTP_ENCRYPTION=tls" . PHP_EOL;
    $envContent .= "MAIL_FROM_ADDRESS=" . addslashes($email) . PHP_EOL;
    $envContent .= "MAIL_FROM_NAME=Blogware" . PHP_EOL;
    $envContent .= PHP_EOL;
    $envContent .= "# --- SYSTEM ---" . PHP_EOL;
    $envContent .= "SYSTEM_OS=" . addslashes(check_os()['Operating_system']) . PHP_EOL;
    $envContent .= "DISTRIB_NAME=\"" . trim($distro) . "\"" . PHP_EOL;
    $envContent .= PHP_EOL;
    $envContent .= "# --- API SECURITY ---" . PHP_EOL;
    $envContent .= "CORS_ALLOWED_ORIGINS=" . addslashes(setup_base_url($protocol, $server_name)) . PHP_EOL;

    file_put_contents(__DIR__ . '/../../.env', $envContent, LOCK_EX);
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
function remove_bad_characters($str_words, $host, $user, $password, $database, $port, $escape = false, $level = 'high')
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
        $link = mysqli_connect($host, $user, $password, $database, $port);
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
    return htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * installation_key
 *
 * @param int|num $length
 * @return void
 */
function installation_key($length)
{
    if (function_exists("random_bytes")) {
        $token = random_bytes(ceil($length / 2));
    } elseif (function_exists("openssl_random_pseudo_bytes")) {
        $token = openssl_random_pseudo_bytes(ceil($length / 2));
    } else {
        trigger_error("No cryptographically secure random function available", E_USER_ERROR);
    }

    return bin2hex($token);
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
            
            chmod(__DIR__ . '/../index.php', 0664);

            unset($_SESSION['token']);

            $_SESSION = array();

            session_destroy();
        }
    }
}

/**
 * Generate a random filename for encryption key
 *
 * @return string
 */
function generate_random_key_filename(): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $filename = '';
    for ($i = 0; $i < 16; $i++) {
        $filename .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $filename . '.php';
}

/**
 * Generate and save Defuse encryption key outside document root
 * 
 * The key is automatically placed in a secure location outside the web root
 * (parent directory of public_html) with a random directory and filename.
 * 
 * @return string The full path to the generated key file
 * @throws Exception
 */
function generate_defuse_key()
{
    $appRoot = dirname(__DIR__, 2);
    $parentDir = dirname($appRoot);
    
    $secureStorage = $parentDir . DIRECTORY_SEPARATOR . 'storage';
    $keySubDir = 'keys';
    $keyDir = $secureStorage . DIRECTORY_SEPARATOR . $keySubDir;
    
    if (!is_dir($keyDir)) {
        @mkdir($keyDir, 0755, true);
    }
    
    if (!is_dir($keyDir) || !is_writable($keyDir)) {
        $keyDir = $appRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'utility' . DIRECTORY_SEPARATOR . '.lts';
        if (!is_dir($keyDir)) {
            @mkdir($keyDir, 0755, true);
        }
    }
    
    if (strpos($keyDir, $appRoot) !== false && !file_exists($keyDir . '/.htaccess')) {
        $htaccessContent = "# Deny all public access to encryption keys\nOrder deny,allow\nDeny from all\n";
        @file_put_contents($keyDir . '/.htaccess', $htaccessContent);
    }
    
    $keyFilename = generate_random_key_filename();
    $keyFile = $keyDir . DIRECTORY_SEPARATOR . $keyFilename;
    
    $maxAttempts = 10;
    $attempt = 0;
    while (file_exists($keyFile) && $attempt < $maxAttempts) {
        $keyFilename = generate_random_key_filename();
        $keyFile = $keyDir . DIRECTORY_SEPARATOR . $keyFilename;
        $attempt++;
    }
    
    $key = Defuse\Crypto\Key::createNewRandomKey();
    $keyAscii = $key->saveToAsciiSafeString();
    
    $phpContent = "<?php\n// Encryption key generated on " . date('Y-m-d H:i:s') . "\n// Do not delete or modify this file\nreturn '$keyAscii';";
    file_put_contents($keyFile, $phpContent, LOCK_EX);
    
    chmod($keyFile, 0644);
    
    return $keyFile;
}

/**
 * genereate_table_prefix()
 *
 * @param integer $length
 * 
 */
function generate_table_prefix($length = 6)
{
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $prefix = '';

    if (function_exists("random_bytes")) {
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $prefix .= $chars[ord($bytes[$i]) % strlen($chars)];
        }
    } elseif (function_exists("openssl_random_pseudo_bytes")) {
        $bytes = openssl_random_pseudo_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $prefix .= $chars[ord($bytes[$i]) % strlen($chars)];
        }
    } else {
        for ($i = 0; $i < $length; $i++) {
            $prefix .= $chars[rand(0, strlen($chars) - 1)];
        }
    }

    return $prefix . '_';
}

/**
 * Generate server config file during installation
 *
 * This creates appropriate routing config based on web server:
 * - Apache/LiteSpeed: .htaccess
 * - Nginx: nginx-rewrites.conf + instructions
 *
 * @return array Result with 'config_file' and 'instructions'
 */
function generate_server_config()
{
    $web_server = check_web_server()['WebServer'];
    $root_path = dirname(__DIR__, 2);

    $result = [
      'config_file' => '',
      'instructions' => '',
      'web_server' => $web_server
    ];

    if (stripos($web_server, 'nginx') !== false) {
        # Generate Nginx config file with security rules
        $nginx_content = '# START ScriptLog Nginx Rewrites
# Auto-generated during installation
# Add this to your Nginx vhost config: include /path/to/nginx-rewrites.conf;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# SECURITY: Block access to sensitive files
location ~ /\.(htaccess|htpasswd|git|env|log|sql|sh|bak|old|tmp|swp|yaml|yml|ini|dist|example)$ {
    deny all;
}

location ~ \.(env|log|sql)$ {
    deny all;
}

# SECURITY: Block access to config.php and composer files
location ~ /(config\.php|composer\.json|composer\.lock|package\.json|package-lock\.json)$ {
    deny all;
}

# SECURITY: Block access to lib, install, and config directories
location ~ ^/(lib)/ {
    deny all;
}

# SECURITY: Block .git directory
location ~ /\.git/ {
    deny all;
}

# FINISH ScriptLog
';

        $nginx_path = $root_path . '/nginx-rewrites.conf';
        file_put_contents($nginx_path, $nginx_content);

        $result['config_file'] = 'nginx-rewrites.conf';
        $result['instructions'] = 'Nginx detected! Please add the following to your Nginx vhost config:
    
    include ' . $nginx_path . ';
    
    Then restart Nginx.';
    } else {
        # Generate .htaccess for Apache/LiteSpeed with security rules
        $htaccess_content = '# =============================================
# SECURITY RULES - Block access to sensitive files
# Auto-generated during installation
# =============================================

# Block access to all files starting with a dot (except .htaccess itself)
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to specific sensitive file extensions
<FilesMatch "\.(env|log|sql|sh|bak|old|tmp|swp|yaml|yml|ini|dist|example)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to config.php
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Block access to composer files
<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

# Block access to package files
<Files "package.json">
    Order allow,deny
    Deny from all
</Files>

<Files "package-lock.json">
    Order allow,deny
    Deny from all
</Files>

# Block access to .git directory
<IfModule mod_rewrite.c>
    RewriteRule ^\.git - [F,L]
</IfModule>

# Block access to sensitive directories
<IfModule mod_rewrite.c>
    RewriteRule ^(lib)/ - [F,L]
</IfModule>

# =============================================
# ScriptLog URL Rewriting
# =============================================
# START ScriptLog
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    # Ensure all front-end UI-UX files readable
    RewriteCond %{REQUEST_FILENAME} !\.(ico|css|png|jpg|jpeg|webp|gif|js|txt|htm|html|eot|svg|ttf|woff|woff2|webm|ogg|mp4|wav|mp3|pdf)$ [NC]
    RewriteRule ^public/.*$ index.php
    # API routes
    RewriteCond %{REQUEST_URI} ^/api [NC]
    RewriteRule ^api/(.*)$ api/index.php [QSA,L]
    RewriteCond %{REQUEST_FILENAME} !-d 
    RewriteCond %{REQUEST_FILENAME} !-f 
    RewriteCond %{REQUEST_FILENAME} !-l 
    # Only route known application prefixes to index.php
    RewriteRule ^(post|page|blog|category|archive|archives|tag|privacy|download|download_file)(/.*)?$ index.php [QSA,L]
    RewriteRule ^$ index.php [QSA,L]
    RewriteRule ^[a-z]{2}/(post|page|blog|category|archive|archives|tag|privacy|download|download_file)(/.*)?$ index.php [QSA,L]
    RewriteRule ^[a-z]{2}/?$ index.php [QSA,L]
</IfModule>
# FINISH ScriptLog

<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/rss+xml
  AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
  AddOutputFilterByType DEFLATE application/x-font
  AddOutputFilterByType DEFLATE application/x-font-opentype
  AddOutputFilterByType DEFLATE application/x-font-otf
  AddOutputFilterByType DEFLATE application/x-font-truetype
  AddOutputFilterByType DEFLATE application/x-font-ttf
  AddOutputFilterByType DEFLATE application/x-javascript
  AddOutputFilterByType DEFLATE application/xhtml+xml
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE font/opentype
  AddOutputFilterByType DEFLATE font/otf
  AddOutputFilterByType DEFLATE font/ttf
  AddOutputFilterByType DEFLATE image/svg+xml
  AddOutputFilterByType DEFLATE image/x-icon
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE text/xml
  BrowserMatch ^Mozilla/4 gzip-only-text/html
  BrowserMatch ^Mozilla/4\.0[678] no-gzip
  BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
  Header append Vary User-Agent
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresDefault "access plus 1 month"
  ExpiresByType image/x-icon "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType application/x-shockwave-flash "access plus 1 month"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType application/x-javascript "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
  ExpiresByType application/pdf "access plus 1 month"
  ExpiresByType application/x-font-woff "access plus 1 year"
  ExpiresByType application/x-font-woff2 "access plus 1 year"
  ExpiresByType font/woff "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
';

        $htaccess_path = $root_path . '/.htaccess';
        file_put_contents($htaccess_path, $htaccess_content);

        $result['config_file'] = '.htaccess';
        $result['instructions'] = '';
    }

    return $result;
}
