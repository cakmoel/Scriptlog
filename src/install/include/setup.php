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
 * 
 */
function install_database_table($link, $protocol, $server_host, $user_login, $user_pass, $user_email, $key, $prefix = '')
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

      // insert language settings
      $link->query($insertLangSettings);

      // insert default theme
      $recordTheme = $link->prepare($saveTheme);
      $recordTheme->bind_param("sssss", $theme_title, $theme_desc, $theme_designer, $theme_directory, $theme_status);
      $recordTheme->execute();

      if ($recordAppKey->affected_rows > 0) {
        
        install_i18n_data($link, $prefix);
        
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
 * @return void
 */
function install_i18n_data($link, $prefix = '')
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
      'nav.dashboard' => ['Dashboard', 'لوحة القيادة', '仪表盘', 'Tableau de bord', 'Панель управления', 'Panel de control', 'Dasbor'],
      'nav.posts' => ['Posts', 'المقالات', '文章', 'Articles', 'Записи', 'Entradas', 'Postingan'],
      'nav.media' => ['Media', 'الوسائط', '媒体', 'Médias', 'Медиа', 'Medios', 'Media'],
      'nav.pages' => ['Pages', 'الصفحات', '页面', 'Pages', 'Страницы', 'Páginas', 'Halaman'],
      'nav.comments' => ['Comments', 'التعليقات', '评论', 'Commentaires', 'Комментарии', 'Comentarios', 'Komentar'],
      'nav.users' => ['Users', 'المستخدمون', '用户', 'Utilisateurs', 'Пользователи', 'Usuarios', 'Pengguna'],
      'nav.settings' => ['Settings', 'الإعدادات', '设置', 'Paramètres', 'Настройки', 'Configuración', 'Pengaturan'],
      'nav.plugins' => ['Plugins', 'الإضافات', '插件', 'Extensions', 'Плагины', 'Complementos', 'Plugin'],
      'nav.privacy' => ['Privacy', 'الخصوصية', '隐私', 'Confidentialité', 'Конфиденциальность', 'Privacidad', 'Privasi'],
      'nav.languages' => ['Languages', 'اللغات', '语言', 'Langues', 'Языки', 'Idiomas', 'Bahasa'],
    ],
    'form' => [
      'form.save' => ['Save', 'حفظ', '保存', 'Enregistrer', 'Сохранить', 'Guardar', 'Simpan'],
      'form.cancel' => ['Cancel', 'إلغاء', '取消', 'Annuler', 'Отмена', 'Cancelar', 'Batal'],
      'form.delete' => ['Delete', 'حذف', '删除', 'Supprimer', 'Удалить', 'Eliminar', 'Hapus'],
      'form.edit' => ['Edit', 'تعديل', '编辑', 'Modifier', 'Редактировать', 'Editar', 'Edit'],
      'form.submit' => ['Submit', 'إرسال', '提交', 'Soumettre', 'Отправить', 'Enviar', 'Kirim'],
      'form.search' => ['Search', 'بحث', '搜索', 'Rechercher', 'Поиск', 'Buscar', 'Cari'],
      'form.name' => ['Name', 'الاسم', '姓名', 'Nom', 'Имя', 'Nombre', 'Nama'],
      'form.email' => ['Email', 'البريد الإلكتروني', '邮箱', 'Email', 'Email', 'Email', 'Email'],
      'form.password' => ['Password', 'كلمة المرور', '密码', 'Mot de passe', 'Пароль', 'Contraseña', 'Kata sandi'],
    ],
    'button' => [
      'button.add' => ['Add New', 'إضافة جديد', '新建', 'Ajouter', 'Добавить', 'Añadir nuevo', 'Tambah baru'],
      'button.read_more' => ['Read More', 'اقرأ المزيد', '阅读更多', 'Lire la suite', 'Читать далее', 'Leer más', 'Baca selengkapnya'],
      'button.subscribe' => ['Subscribe', 'اشترك', '订阅', "S'abonner", 'Подписаться', 'Suscribirse', 'Berlangganan'],
    ],
    'error' => [
      'error.not_found' => ['Page not found', 'الصفحة غير موجودة', '页面未找到', 'Page non trouvée', 'Страница не найдена', 'Página no encontrada', 'Halaman tidak ditemukan'],
    ],
    'footer' => [
      'footer.copyright' => ['All rights reserved', 'جميع الحقوق محفوظة', '版权所有', 'Tous droits réservés', 'Все права защищены', 'Todos los droits réservés', 'Semua hak dilindungi'],
    ],
    'admin' => [
      'admin.all_languages' => ['All Languages', 'جميع اللغات', '所有语言', 'Toutes les langues', 'Все языки', 'Todos los idiomas', 'Semua Bahasa'],
      'admin.translations' => ['Translations', 'الترجمات', '翻译', 'Traductions', 'Переводы', 'Traducciones', 'Terjemahan'],
      'admin.add_language' => ['Add Language', 'إضافة لغة', '添加语言', 'Ajouter une langue', 'Добавить язык', 'Añadir idioma', 'Tambah Bahasa'],
      'admin.edit_language' => ['Edit Language', 'تعديل اللغة', '编辑语言', 'Modifier la langue', 'Редактировать язык', 'Editar idioma', 'Edit Bahasa'],
      'admin.delete_language' => ['Delete Language', 'حذف اللغة', '删除语言', 'Supprimer la langue', 'Удалить язык', 'Eliminar idioma', 'Hapus Bahasa'],
    ],
  ];

  $langOrder = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
  $langIds = [];

  foreach ($langOrder as $index => $code) {
    $lang = $languages[$code];
    $stmt = $link->prepare("INSERT INTO {$prefix}tbl_languages (lang_code, lang_name, lang_native, lang_locale, lang_direction, lang_sort, lang_is_default, lang_is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssii", $code, $lang['name'], $lang['native'], $lang['locale'], $lang['direction'], $lang['sort'], $lang['is_default']);
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

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  $link = ($ca !== false) ? make_secure_connection($dbhost, $dbuser, $dbpassword, $dbname, $dbport, $ca) : make_connection($dbhost, $dbuser, $dbpassword, $dbname, $dbport);

  $configuration = false;

  if (isset($_SESSION['install']) && $_SESSION['install'] === true) {

    $distro = isset(get_linux_distro()['NAME']) ? get_linux_distro()['NAME'] : "";

    // Generate Defuse encryption key for authentication
    $defuse_key_path = generate_defuse_key();

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
    $configFile .= '        \'defuse_key\' => \'lib/utility/.lts/lts.txt\',' . PHP_EOL;
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

      // Write config.php
      file_put_contents(__DIR__ . '/../../config.php', $configFile, LOCK_EX);

      // Also generate .env file for environment variables
      write_env_file($protocol, $server_name, $dbhost, $dbuser, $dbpassword, $dbname, $dbport, $prefix, $email, $app_key, $distro);

      $configuration = true;
    }
  }

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
 */
function write_env_file($protocol, $server_name, $dbhost, $dbuser, $dbpass, $dbname, $dbport, $prefix, $email, $app_key, $distro)
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
  $tokens = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No ambiguous characters
  $token_length = strlen($tokens); // Store token length for efficiency

  // Determine the number of segments and characters per segment
  $num_segments = isset($suffix) ? 3 : 4;
  $segment_chars = isset($suffix) ? 6 : 5;

  // Build the license string
  $segments = [];
  for ($i = 0; $i < $num_segments; $i++) {
    $segment = '';
    for ($j = 0; $j < $segment_chars; $j++) {
      $segment .= $tokens[rand(0, $token_length - 1)];
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

      unset($_SESSION['token']);

      $_SESSION = array();

      session_destroy();
    }
  }
}

/**
 * Generate and save Defuse encryption key
 * 
 * @return string The key in ASCII-safe format
 * @throws Exception
 */
function generate_defuse_key()
{
    $keyFile = __DIR__ . '/../../lib/utility/.lts/lts.txt';
    
    if (!is_dir(dirname($keyFile))) {
        mkdir(dirname($keyFile), 0755, true);
    }
    
    $key = Defuse\Crypto\Key::createNewRandomKey();
    $keyAscii = $key->saveToAsciiSafeString();
    file_put_contents($keyFile, $keyAscii, LOCK_EX);
    
    return $keyAscii;
}

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
