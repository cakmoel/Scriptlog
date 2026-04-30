<?php

/**
 * Blog Theme Functions
 * 
 * Theme-specific functions for the Bootstrap Blog theme.
 * All functions use function_exists() guards to avoid redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * t() - Translation function for frontend
 */
if (!function_exists('t')) {
function t(string $key, array $params = []): string
{
    static $translations = [];
    static $locale = null;

    if ($locale === null) {
        $locale = detect_browser_locale();
    }

    if (!isset($translations[$locale])) {
        $translations[$locale] = load_theme_translations($locale);
    }

    $value = $translations[$locale][$key] ?? ($translations['en'][$key] ?? $key);

    if (!empty($params)) {
        foreach ($params as $param => $val) {
            $value = str_replace('%' . $param . '%', $val, $value);
        }
    }

    return $value;
}
}

/**
 * detect_browser_locale() - Detect browser locale
 */
if (!function_exists('detect_browser_locale')) {
function detect_browser_locale(): string
{
    $available = ['en', 'es', 'ar', 'zh', 'fr', 'ru', 'id'];
    $default = 'en';

    if (isset($_SESSION['scriptlog_locale']) && in_array($_SESSION['scriptlog_locale'], $available)) {
        return $_SESSION['scriptlog_locale'];
    }

    if (isset($_COOKIE['scriptlog_locale']) && in_array($_COOKIE['scriptlog_locale'], $available)) {
        return $_COOKIE['scriptlog_locale'];
    }

    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($languages as $lang) {
            $parts = explode(';', trim($lang));
            $code = explode('-', $parts[0])[0];
            if (in_array($code, $available)) {
                return $code;
            }
        }
    }

    return $default;
}
}

/**
 * load_theme_translations() - Load translations from JSON file
 */
if (!function_exists('load_theme_translations')) {
function load_theme_translations(string $locale): array
{
    static $cache = [];

    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $themeLangDir = __DIR__ . '/lang/';
    $file = $themeLangDir . $locale . '.json';

    if (file_exists($file)) {
        $content = file_get_contents($file);
        $cache[$locale] = json_decode($content, true) ?: [];
    } else {
        $cache[$locale] = [];
    }

    return $cache[$locale];
}
}

/**
 * reset_i18n_cache() - Reset translation cache
 */
if (!function_exists('reset_i18n_cache')) {
function reset_i18n_cache(): void
{
    // Utility function for testing
}
}

/**
 * locale_url() - Get URL with locale prefix
 */
if (!function_exists('locale_url')) {
function locale_url(string $path = '', ?string $locale = null): string
{
    if (!class_exists('I18nManager')) {
        return $path;
    }

    $i18n = I18nManager::getInstance();
    $detector = $i18n->getDetector();
    $defaultLocale = $detector->getDefaultLocale();
    $targetLocale = $locale ?? $i18n->getLocale();

    $permalinksEnabled = function_exists('rewrite_status') && rewrite_status() === 'yes';
    $prefixEnabled = function_exists('is_locale_prefix_enabled') ? is_locale_prefix_enabled() : false;

    if (!$permalinksEnabled) {
        return $path;
    }

    if ($permalinksEnabled && !$prefixEnabled) {
        if ($targetLocale === $defaultLocale) {
            return $path;
        }
        return $path;
    }

    if ($targetLocale === $defaultLocale) {
        return $path;
    }

    return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
}
}

/**
 * is_locale_prefix_enabled() - Check if locale prefix should be added
 */
if (!function_exists('is_locale_prefix_enabled')) {
function is_locale_prefix_enabled(): bool
{
    if (!class_exists('ConfigurationDao')) {
        return false;
    }

    try {
        $configDao = new ConfigurationDao();
        $setting = $configDao->findConfigByName('lang_prefix_required', new Sanitize());
        return ($setting['setting_value'] ?? '0') === '1';
    } catch (Throwable $e) {
        return false;
    }
}
}

/**
 * get_default_locale() - Get the default locale
 */
if (!function_exists('get_default_locale')) {
function get_default_locale(): string
{
    if (!class_exists('I18nManager')) {
        return 'en';
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getDetector()->getDefaultLocale();
}
}

/**
 * get_locale() - Get current locale
 */
if (!function_exists('get_locale')) {
function get_locale(): string
{
    if (!class_exists('I18nManager')) {
        return 'en';
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getLocale();
}
}

/**
 * available_locales() - Get available locales
 */
if (!function_exists('available_locales')) {
function available_locales(): array
{
    if (!class_exists('I18nManager')) {
        return ['en'];
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getAvailableLocales();
}
}

/**
 * is_rtl() - Check if current locale is RTL
 */
if (!function_exists('is_rtl')) {
function is_rtl(): bool
{
    if (!class_exists('I18nManager')) {
        return false;
    }

    $i18n = I18nManager::getInstance();
    return $i18n->isRtl();
}
}

/**
 * get_html_dir() - Get HTML dir attribute
 */
if (!function_exists('get_html_dir')) {
function get_html_dir(): string
{
    return is_rtl() ? 'rtl' : 'ltr';
}
}

/**
 * get_language_name() - Get human-readable language name
 */
if (!function_exists('get_language_name')) {
function get_language_name(string $locale, bool $native = true): string
{
    $names = [
        'en' => ['native' => 'English', 'english' => 'English'],
        'ar' => ['native' => 'العربية', 'english' => 'Arabic'],
        'zh' => ['native' => '中文', 'english' => 'Chinese'],
        'fr' => ['native' => 'Français', 'english' => 'French'],
        'ru' => ['native' => 'Русский', 'english' => 'Russian'],
        'es' => ['native' => 'Español', 'english' => 'Spanish'],
        'id' => ['native' => 'Bahasa Indonesia', 'english' => 'Indonesian'],
    ];

    $key = $native ? 'native' : 'english';
    return $names[$locale][$key] ?? ucfirst($locale);
}
}

/**
 * get_all_language_names() - Get all available language names
 */
if (!function_exists('get_all_language_names')) {
function get_all_language_names(): array
{
    $locales = available_locales();
    $names = [];

    foreach ($locales as $locale) {
        $names[$locale] = [
            'native' => get_language_name($locale, true),
            'english' => get_language_name($locale, false),
            'code' => strtoupper($locale),
        ];
    }

    return $names;
}
}

/**
 * language_switcher() - Generate language switcher HTML
 */
if (!function_exists('language_switcher')) {
function language_switcher(array $args = []): string
{
    $current = get_locale();
    $locales = available_locales();

    if (count($locales) <= 1) {
        return '';
    }

    $style = $args['style'] ?? 'dropdown';
    $show_names = $args['show_names'] ?? true;
    $class = $args['class'] ?? 'language-switcher';
    $current_native = get_language_name($current, true);
    $current_code = strtoupper($current);

    $html = '<div class="' . escape_html($class) . '">';

    if ($style === 'dropdown') {
        $html .= '<div class="dropdown">';
        $html .= '<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '<i class="fa fa-globe" aria-hidden="true"></i> ';
        $html .= $show_names ? escape_html($current_native) : $current_code;
        $html .= '</button>';
        $html .= '<div class="dropdown-menu">';

        foreach ($locales as $locale) {
            $active = ($locale === $current) ? 'active' : '';
            $url = '?switch-lang=' . escape_html($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
            $native_name = get_language_name($locale, true);
            $lang_code = strtoupper($locale);
            $html .= '<a class="dropdown-item ' . $active . '" href="' . $url . '">';
            $html .= '<span class="lang-code-badge">' . $lang_code . '</span>';
            $html .= '<span class="lang-name">' . escape_html($native_name) . '</span>';
            if ($active) {
                $html .= ' <i class="fa fa-check" aria-hidden="true"></i>';
            }
            $html .= '</a>';
        }

        $html .= '</div></div>';
    } else {
        foreach ($locales as $locale) {
            $active = ($locale === $current) ? 'active' : '';
            $url = '?switch-lang=' . escape_html($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
            $html .= '<a class="' . $active . '" href="' . $url . '">' . get_language_name($locale, true) . '</a>';
        }
    }

    $html .= '</div>';

    return $html;
}
}

/**
 * request_path() - Get request path object
 */
if (!function_exists('request_path')) {
function request_path()
{
    return class_exists('RequestPath') ? new RequestPath() : "";
}
}

/**
 * previous_post() - Get previous post link
 */
if (!function_exists('previous_post')) {
function previous_post($id)
{
    $idsanitized = sanitizer($id, 'sql');

    $html = null;

    $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID < '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID DESC LIMIT 1";

    $stmt = db_simple_query($sql);

    while ($rows = $stmt->fetch()) {
        $html .= '<a href="' . permalinks($rows['ID'])['post'] . '" class="prev-post text-left d-flex align-items-center">';
        $html .= '<div class="icon prev"><i class="fa fa-angle-left" aria-hidden="true"></i></div>';
        $html .= '<div class="text"><strong class="text-primary">Previous Post </strong>';
        $html .= '<h6>' . escape_html($rows['post_title']) . '</h6>';
        $html .= '</div>';
        $html .= '</a>';
    }

    return $html;
}
}

/**
 * next_post() - Get next post link
 */
if (!function_exists('next_post')) {
function next_post($id)
{
    $idsanitized = sanitizer($id, 'sql');

    $html = null;

    $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID > '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID ASC LIMIT 1";

    $stmt = db_simple_query($sql);

    while ($rows = $stmt->fetch()) {
        $html .= '<a href="' . permalinks($rows['ID'])['post'] . '"  class="next-post text-right d-flex align-items-center justify-content-end">';
        $html .= '<div class="text"><strong class="text-primary">Next Post </strong>';
        $html .= '<h6>' . escape_html($rows['post_title']) . '</h6>';
        $html .= '</div>';
        $html .= '<div class="icon next"><i class="fa fa-angle-right" aria-hidden="true"></i></div>';
        $html .= '</a>';
    }

    return $html;
}
}

/**
 * initialize_page()
 */
if (!function_exists('initialize_page')) {
function initialize_page()
{
    return class_exists('PageModel') ? new PageModel() : "";
}
}

/**
 * initialize_post()
 */
if (!function_exists('initialize_post')) {
function initialize_post()
{
    return class_exists('PostModel') ? new PostModel() : "";
}
}

/**
 * initialize_comment()
 */
if (!function_exists('initialize_comment')) {
function initialize_comment()
{
    return class_exists('CommentModel') ? new CommentModel() : "";
}
}

/**
 * initialize_archive()
 */
if (!function_exists('initialize_archive')) {
function initialize_archive()
{
    return class_exists('ArchivesModel') ? new ArchivesModel() : "";
}
}

/**
 * initialize_topic()
 */
if (!function_exists('initialize_topic')) {
function initialize_topic()
{
    return class_exists('TopicModel') ? new TopicModel() : "";
}
}

/**
 * initialize_tag()
 */
if (!function_exists('initialize_tag')) {
function initialize_tag()
{
    return class_exists('TagModel') ? new TagModel() : "";
}
}

/**
 * initialize_gallery()
 */
if (!function_exists('initialize_gallery')) {
function initialize_gallery()
{
    return class_exists('GalleryModel') ? new GalleryModel() : "";
}
}

/**
 * featured_post() - Get random headline posts
 */
if (!function_exists('featured_post')) {
function featured_post()
{
    $headlines = class_exists('FrontContentModel') ? FrontContentModel::frontRandomHeadlines(initialize_post()) : "";
    return is_iterable($headlines) ? $headlines : array();
}
}

/**
 * get_slideshow() - Get posts with media for slideshow
 */
if (!function_exists('get_slideshow')) {
function get_slideshow($limit = 5)
{
    if (function_exists('medoo_init')) {
        $database = medoo_init();
        return $database->select('tbl_posts', [
            '[>]tbl_media' => ['media_id' => 'ID'],
            '[>]tbl_users' => ['post_author' => 'ID']
        ], [
            'tbl_posts.ID(post_id)',
            'tbl_posts.post_title',
            'tbl_posts.post_content',
            'tbl_posts.post_slug',
            'tbl_posts.post_summary',
            'tbl_posts.post_date(created_at)',
            'tbl_posts.post_modified(modified_at)',
            'tbl_media.media_filename',
            'tbl_media.media_caption',
            'tbl_users.user_fullname',
            'tbl_users.user_login'
        ], [
            'tbl_posts.post_status' => 'publish',
            'tbl_posts.post_type' => 'blog',
            'tbl_media.media_target' => 'blog',
            'tbl_media.media_access' => 'public',
            'tbl_media.media_status' => 1,
            'tbl_users.user_banned' => 0,
            'ORDER' => ['tbl_posts.post_date' => 'DESC'],
            'LIMIT' => $limit
        ]);
    }
    return [];
}
}

/**
 * sticky_page() - Get random sticky page
 */
if (!function_exists('sticky_page')) {
function sticky_page()
{
    $sticky_page = class_exists('FrontContentModel') ? FrontContentModel::frontRandomStickyPage(initialize_page()) : "";
    return is_iterable($sticky_page) ? $sticky_page : array();
}
}

/**
 * random_posts() - Get random posts
 */
if (!function_exists('random_posts')) {
function random_posts($start, $end)
{
    $random_posts = class_exists('FrontContentModel') ? FrontContentModel::frontRandomPosts($start, $end, initialize_post()) : "";
    return is_iterable($random_posts) ? $random_posts : array();
}
}

/**
 * latest_posts() - Get latest posts
 */
if (!function_exists('latest_posts')) {
function latest_posts($limit, $position = null)
{
    $latest_posts = class_exists('FrontContentModel') ? FrontContentModel::frontLatestPosts($limit, initialize_post(), $position) : "";
    return is_iterable($latest_posts) ? $latest_posts : array();
}
}

/**
 * format_topics() - Format topics string to HTML links
 */
if (!function_exists('format_topics')) {
function format_topics($topics_data)
{
    if (empty($topics_data)) {
        return "";
    }

    $topics = explode('|', $topics_data);
    $links = [];

    foreach ($topics as $topic) {
        $parts = explode(':', $topic);
        if (count($parts) === 3) {
            $id = $parts[0];
            $title = $parts[1];
            $slug = $parts[2];

            $permalink = (function_exists('rewrite_status') && rewrite_status() === 'yes') ? permalinks($slug)['cat'] : permalinks($id)['cat'];
            $title_esc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $links[] = "<a href='{$permalink}'>{$title_esc}</a>";
        }
    }

    return implode(', ', $links);
}
}

/**
 * retrieves_topic_simple() - Get topic links for post
 */
if (!function_exists('retrieves_topic_simple')) {
function retrieves_topic_simple($id)
{
    $categories = array();

    $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug
          FROM tbl_topics, tbl_post_topic WHERE tbl_topics.ID = tbl_post_topic.topic_id
          AND tbl_topics.topic_status = 'Y' AND tbl_post_topic.post_id = ? ";

    $stmt = db_prepared_query($sql, [$id], 'i');

    if ($stmt) {
        while ($result = $stmt->fetch()) {
            $permalinks = (function_exists('rewrite_status') && rewrite_status() === 'yes')
                ? (permalinks($result['topic_slug'])['cat'] ?? '#')
                : (permalinks($result['ID'])['cat'] ?? '#');

            $topic_title = htmlspecialchars($result['topic_title'], ENT_QUOTES, 'UTF-8');

            $categories[] = "<a href='{$permalinks}'>{$topic_title}</a>";
        }
    }

    return implode("", $categories);
}
}

/**
 * retrieves_topic_prepared() - Get topic links with status check
 */
if (!function_exists('retrieves_topic_prepared')) {
function retrieves_topic_prepared($id)
{
    $topics = null;
    $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status
          FROM tbl_topics, tbl_post_topic
          WHERE tbl_topics.ID = tbl_post_topic.topic_id 
          AND tbl_topics.topic_status = 'Y' 
          AND tbl_post_topic.post_id = ? ";

    $items = db_prepared_query($sql, [$id], 'i');

    while ($item = $items->fetch()) {
        $permalinks = ((function_exists('rewrite_status')) && (rewrite_status() === 'yes') ? permalinks($item['topic_slug'])['cat'] : permalinks($item['ID'])['cat']);
        $topics[] = "<a href='" . $permalinks . "'>" . $item['topic_title'] . "</a>";
    }

    return implode("", $topics ?? []);
}
}

/**
 * sidebar_topics() - Get topics for sidebar
 */
if (!function_exists('sidebar_topics')) {
function sidebar_topics()
{
    $sidebar_topics = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarTopics(initialize_topic()) : "";
    return is_iterable($sidebar_topics) ? $sidebar_topics : array();
}
}

/**
 * retrieve_tags() - Get tags for sidebar
 */
if (!function_exists('retrieve_tags')) {
function retrieve_tags()
{
    return (function_exists('outputting_tags')) ? outputting_tags() : "";
}
}

/**
 * link_tag() - Generate tag links
 */
if (!function_exists('link_tag')) {
function link_tag($id)
{
    return (class_exists('FrontContentModel')) ? FrontContentModel::frontLinkTag($id, initialize_tag()) : "";
}
}

/**
 * link_topic() - Generate topic link
 */
if (!function_exists('link_topic')) {
function link_topic($id)
{
    return (class_exists('FrontContentModel')) ? FrontContentModel::frontLinkTopic($id, initialize_topic()) : "";
}
}

/**
 * display_galleries() - Get gallery images
 */
if (!function_exists('display_galleries')) {
function display_galleries($start, $limit)
{
    $showcase = class_exists('FrontContentModel') ? FrontContentModel::frontGalleries(initialize_gallery(), $start, $limit) : "";
    return is_iterable($showcase) ? $showcase : array();
}
}

/**
 * retrieve_blog_posts() - Get all blog posts
 */
if (!function_exists('retrieve_blog_posts')) {
function retrieve_blog_posts()
{
    $posts = class_exists('FrontContentModel') ? FrontContentModel::frontBlogPosts(initialize_post()) : "";
    return is_iterable($posts) ? $posts : array();
}
}

/**
 * retrieve_detail_post() - Get single post by ID
 */
if (!function_exists('retrieve_detail_post')) {
function retrieve_detail_post($id)
{
    $detail_post = class_exists('FrontContentModel') ? FrontContentModel::frontPostById($id, initialize_post()) : "";
    return is_iterable($detail_post) ? $detail_post : array();
}
}

/**
 * posts_by_archive() - Get posts by archive
 */
if (!function_exists('posts_by_archive')) {
function posts_by_archive(array $values)
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByArchive($values, initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}
}

/**
 * archive_index() - Get all archives for index
 */
if (!function_exists('archive_index')) {
function archive_index()
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontArchiveIndex(initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}
}

/**
 * posts_by_tag() - Get posts by tag
 */
if (!function_exists('posts_by_tag')) {
function posts_by_tag($tag)
{
    $tags = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByTag($tag, initialize_tag()) : "";
    return is_iterable($tags) ? $tags : array();
}
}

/**
 * searching_by_tag() - Full-text tag search
 */
if (!function_exists('searching_by_tag')) {
function searching_by_tag($tag)
{
    $tags = class_exists('FrontHelper') ? FrontHelper::simpleSearchingTag($tag) : "";
    return is_iterable($tags) ? $tags : array();
}
}

/**
 * posts_by_category() - Get posts by category
 */
if (!function_exists('posts_by_category')) {
function posts_by_category($topicId)
{
    $entries = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['entries'];
    $pagination = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['pagination'];

    return is_iterable($entries) ? array('entries' => $entries, 'pagination' => $pagination) : array();
}
}

/**
 * retrieve_archives() - Get archives for sidebar
 */
if (!function_exists('retrieve_archives')) {
function retrieve_archives()
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarArchives(initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}
}

/**
 * retrieve_page() - Get page by ID or slug
 */
if (!function_exists('retrieve_page')) {
function retrieve_page($arg, $rewrite)
{
    if ($rewrite == 'no') {
        $page = class_exists('FrontContentModel') ? FrontContentModel::frontPageById($arg, initialize_page()) : "";
        return is_iterable($page) ? $page : [];
    } else {
        $page = class_exists('FrontContentModel') ? FrontContentModel::frontPageBySlug($arg, initialize_page()) : "";
        return is_iterable($page) ? $page : [];
    }
}
}

/**
 * total_comment() - Get total approved comments for post
 */
if (!function_exists('total_comment')) {
function total_comment($id)
{
    $sql = "SELECT COUNT(1) AS total_comments FROM tbl_comments WHERE comment_post_id = ? AND comment_status = 'approved'";
    $result = db_prepared_query($sql, [$id], "i");
    $row = $result->fetch();

    return isset($row['total_comments']) ? ['total' => $row['total_comments']] : ['total' => 0];
}
}

/**
 * block_csrf() - Generate CSRF token for comment form
 */
if (!function_exists('block_csrf')) {
function block_csrf()
{
    return (function_exists('generate_form_token')) ? generate_form_token('comment_form', 32) : "";
}
}

/**
 * front_navigation() - Render navigation menu
 */
if (!function_exists('front_navigation')) {
function front_navigation($parent, $menu)
{
    $html = "";
    $permalinkEnabled = function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes';

    if (isset($menu['parents'][$parent])) {
        foreach ($menu['parents'][$parent] as $itemId) {
            $item = $menu['items'][$itemId];
            $link = $item['menu_link'];
            $label = $item['menu_label'];
            
            $convertedLink = convert_menu_link($link, $permalinkEnabled);
            
            if (!isset($menu['parents'][$itemId])) {
                $html .= "<li><a href='" . htmlspecialchars($convertedLink, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a></li>";
            }
            if (isset($menu['parents'][$itemId])) {
                $html .= "<li class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='" . htmlspecialchars($convertedLink, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</a>";
                $html .= '<ul class="dropdown-menu">';
                $html .= front_navigation($itemId, $menu);
                $html .= '</ul>';
                $html .= "</li>";
            }
        }
    }

    return $html;
}
}

/**
 * convert_menu_link() - Convert menu link between URL formats
 */
if (!function_exists('convert_menu_link')) {
function convert_menu_link(string $link, bool $permalinkEnabled): string
{
    if (empty($link) || $link === '#' || strpos($link, '://') !== false || strpos($link, 'mailto:') !== false || strpos($link, '#') === 0) {
        return $link;
    }
    
    if (strpos($link, 'http://') === 0 || strpos($link, 'https://') === 0) {
        return $link;
    }
    
    if ($permalinkEnabled) {
        if (preg_match('/^\?p=(\d+)$/', $link, $matches)) {
            $id = $matches[1];
            $converted = permalinks($id);
            return $converted['post'] ?? $link;
        }
        
        if (preg_match('/^\?pg=(\d+)$/', $link, $matches)) {
            $id = $matches[1];
            $converted = permalinks($id);
            return $converted['page'] ?? $link;
        }
        
        if (preg_match('/^\?cat=(\d+)$/', $link, $matches)) {
            $id = $matches[1];
            $converted = permalinks($id);
            return $converted['cat'] ?? $link;
        }
        
        if (preg_match('/^\?a=(\d+)$/', $link, $matches)) {
            $id = $matches[1];
            $converted = permalinks($id);
            return $converted['archive'] ?? $link;
        }
        
        if (strpos($link, '/') === 0) {
            return $link;
        }
        
        $cleanLink = str_replace('.php', '', $link);
        if (strpos($cleanLink, '/') !== 0) {
            $cleanLink = '/' . $cleanLink;
        }
        
        return $cleanLink;
    } else {
        if (preg_match('/^\/post\/(\d+)\/[\w-]+$/', $link, $matches)) {
            return '?p=' . $matches[1];
        }
        
        if (preg_match('/^\/page\/([\w-]+)$/', $link, $matches)) {
            if (class_exists('FrontHelper')) {
                $page = FrontHelper::grabPreparedFrontPageBySlug($matches[1]);
                return '?pg=' . ($page['ID'] ?? 1);
            }
            return '?pg=1';
        }
        
        if (preg_match('/^\/category\/([\w-]+)$/', $link, $matches)) {
            if (class_exists('FrontHelper')) {
                $cat = FrontHelper::grabPreparedFrontTopicBySlug($matches[1]);
                return '?cat=' . ($cat['ID'] ?? 1);
            }
            return '?cat=1';
        }
        
        if (preg_match('/^\/archive\/(\d{2})\/(\d{4})$/', $link, $matches)) {
            return '?a=' . $matches[2] . $matches[1];
        }
        
        if (strpos($link, '/') === 0) {
            return '?' . ltrim($link, '/');
        }
        
        return $link;
    }
}
}

/**
 * retrieve_site_url() - Get site URL from config
 */
if (!function_exists('retrieve_site_url')) {
function retrieve_site_url()
{
    $config_file = read_config(invoke_config());
    return isset($config_file['app']['url']) ? $config_file['app']['url'] : "";
}
}

/**
 * load_more_comments()
 */
if (!function_exists('load_more_comments')) {
function load_more_comments()
{
}
}

/**
 * nothing_found() - Display "no posts" message
 */
if (!function_exists('nothing_found')) {
function nothing_found()
{
    $site_url = function_exists('app_url') ? app_url() . "/admin/login.php" : "";

    return <<<_NOTHING_FOUND
<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops!</h4>
  <p>I haven't posted to my blog yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url" target="_blank" rel="noopener noreferrer" title="administrator panel">administrator panel</a> to populate your blog.</p>
</div>
_NOTHING_FOUND;
}
}

/**
 * render_comments_section() - Render comments section HTML
 */
if (!function_exists('render_comments_section')) {
function render_comments_section(int $postId, int $offset = 0): string
{
    $totalRecords = isset(total_comment($postId)['total']) ? (int) total_comment($postId)['total'] : 0;
    $commentLimit = isset(app_reading_setting()['comment_per_post']) ? (int) app_reading_setting()['comment_per_post'] : 3;

    ob_start(); ?>

    <div id="comments-section" class="post-comments container-fluid px-0">
       <script>
            window.CommentSettings = {
                postId: <?= (int)$postId ?>,
                limit: <?= (int)$commentLimit ?>
            };
        </script>

    <?php if ($offset === 0) : ?>
        <div class="row">
            <div class="col">
                <header class="mb-3">
                    <h3 class="h5 font-weight-bold">
                        Post Comments
                        <span class="badge badge-secondary"><?= htmlspecialchars($totalRecords) ?></span>
                    </h3>
                </header>
            </div>
        </div>
    <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div id="comments" data-post-id="<?= $postId ?>"></div>
                <div class="text-center mt-3">
                    <button id="load-more" class="btn btn-outline-primary">Load More Comments</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
}

if (!function_exists('get_download_page_data')) {
/**
 * Get download page data
 *
 * @param string $identifier
 * @return array
 */
function get_download_page_data($identifier)
{
    if (empty($identifier)) {
        return ['error' => 'Invalid download identifier'];
    }
    
    if (!class_exists('DownloadController') || !class_exists('DownloadService') || !class_exists('DownloadModel') || !class_exists('MediaDao')) {
        return ['error' => 'Download system not available'];
    }
    
    try {
        $downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
        return $downloadController->getDownloadPage($identifier);
    } catch (Exception $e) {
        error_log('Download page error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        return ['error' => 'Unable to retrieve download information'];
    }
}
}