<?php

/**
 * Fast JSON-based translation for frontend
 * No database calls - uses JSON files directly
 *
 * @param string $key Translation key
 * @param array $params Interpolation parameters
 * @return string
 */
function t(string $key, array $params = []): string
{
    static $translations = [];
    static $locale = null;

    // Detect locale once
    if ($locale === null) {
        $locale = detect_browser_locale();
    }

    // Load translations if not loaded
    if (!isset($translations[$locale])) {
        $translations[$locale] = load_theme_translations($locale);
    }

    // Get translation or fallback to English
    $value = $translations[$locale][$key] ?? ($translations['en'][$key] ?? $key);

    // Interpolate parameters
    if (!empty($params)) {
        foreach ($params as $param => $val) {
            $value = str_replace('%' . $param . '%', $val, $value);
        }
    }

    return $value;
}

/**
 * Detect browser locale - fastest method
 */
function detect_browser_locale(): string
{
    $available = ['en', 'es', 'ar', 'zh', 'fr', 'ru', 'id'];
    $default = 'en';

    // Check session/cookie first (if user explicitly selected)
    if (isset($_SESSION['scriptlog_locale']) && in_array($_SESSION['scriptlog_locale'], $available)) {
        return $_SESSION['scriptlog_locale'];
    }

    if (isset($_COOKIE['scriptlog_locale']) && in_array($_COOKIE['scriptlog_locale'], $available)) {
        return $_COOKIE['scriptlog_locale'];
    }

    // Detect from browser - fastest approach
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

/**
 * Load translations from JSON file - fast, no database
 */
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

/**
 * Reset translation cache - useful for testing
 */
function reset_i18n_cache(): void
{
    // This function can be called to reset static caches
    // Not implemented for production use but useful for testing
}

/**
 * locale_url() - Get URL with locale prefix
 *
 * @param string $path
 * @param string|null $locale
 * @return string
 */
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
    $prefixEnabled = is_locale_prefix_enabled();

    // When permalinks disabled: never add prefix (query string format)
    if (!$permalinksEnabled) {
        return $path;
    }

    // When permalinks enabled but prefix toggle off:
    // - Default language: no prefix (e.g., /post/1/slug)
    // - Non-default language: no prefix (maintains existing behavior)
    if ($permalinksEnabled && !$prefixEnabled) {
        // Only add prefix for non-default languages if you want
        // But per current logic, no prefix for any when toggle is off
        if ($targetLocale === $defaultLocale) {
            return $path;
        }
        // Optional: add prefix for non-default when toggle is off
        // return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
        return $path;
    }

    // When both permalinks enabled and prefix toggle on:
    // - Default language: no prefix (e.g., /post/1/slug)
    // - Non-default language: add prefix (e.g., /es/post/1/slug)
    if ($targetLocale === $defaultLocale) {
        return $path;
    }

    return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * is_locale_prefix_enabled()
 *
 * Check if locale prefix should be added to URLs
 *
 * @return bool
 */
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

/**
 * get_default_locale()
 *
 * Get the default locale from settings
 *
 * @return string
 */
function get_default_locale(): string
{
    if (!class_exists('I18nManager')) {
        return 'en';
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getDetector()->getDefaultLocale();
}

/**
 * get_locale() - Get current locale
 *
 * @return string
 */
function get_locale(): string
{
    if (!class_exists('I18nManager')) {
        return 'en';
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getLocale();
}

/**
 * available_locales() - Get available locales
 *
 * @return array
 */
function available_locales(): array
{
    if (!class_exists('I18nManager')) {
        return ['en'];
    }

    $i18n = I18nManager::getInstance();
    return $i18n->getAvailableLocales();
}

/**
 * is_rtl() - Check if current locale is RTL
 *
 * @return bool
 */
function is_rtl(): bool
{
    if (!class_exists('I18nManager')) {
        return false;
    }

    $i18n = I18nManager::getInstance();
    return $i18n->isRtl();
}

/**
 * get_html_dir() - Get HTML dir attribute
 *
 * @return string
 */
function get_html_dir(): string
{
    return is_rtl() ? 'rtl' : 'ltr';
}

/**
 * language_switcher() - Generate language switcher HTML
 *
 * @param array $args Optional arguments
 * @return string
 */
function language_switcher(array $args = []): string
{
    $current = get_locale();
    $locales = available_locales();

    if (count($locales) <= 1) {
        return '';
    }

    $style = $args['style'] ?? 'dropdown';
    $show_flag = $args['show_flag'] ?? false;
    $class = $args['class'] ?? 'language-switcher';

    $html = '<div class="' . escape_html($class) . '">';

    if ($style === 'dropdown') {
        $html .= '<div class="dropdown">';
        $html .= '<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">';
        $html .= strtoupper($current);
        $html .= '</button>';
        $html .= '<div class="dropdown-menu">';

        foreach ($locales as $locale) {
            $active = ($locale === $current) ? 'active' : '';
            $url = locale_url('/', $locale);
            $html .= '<a class="dropdown-item ' . $active . '" href="' . escape_html($url) . '">' . strtoupper($locale) . '</a>';
        }

        $html .= '</div></div>';
    } else {
        foreach ($locales as $locale) {
            $active = ($locale === $current) ? 'active' : '';
            $url = locale_url('/', $locale);
            $html .= '<a class="' . $active . '" href="' . escape_html($url) . '">' . strtoupper($locale) . '</a>';
        }
    }

    $html .= '</div>';

    return $html;
}

/**
 * request_path()
 *
 * @category theme function
 * @return object
 *
 */
function request_path()
{
    return class_exists('RequestPath') ? new RequestPath() : "";
}

/**
 * initialize_post()
 *
 * @category theme function
 * @return object
 *
 */
function initialize_post()
{
    return class_exists('PostModel') ? new PostModel() : "";
}

/**
 * initialize_page()
 *
 * @category theme function
 * @return object
 *
 */
function initialize_page()
{
    return class_exists('PageModel') ? new PageModel() : "";
}

/**
 * initialize_comment
 *
 * @category theme function
 * @return object
 *
 */
function initialize_comment()
{
    return class_exists('CommentModel') ? new CommentModel() : "";
}

/**
 * initialize_archive()
 *
 * @category theme function
 * @return object
 *
 */
function initialize_archive()
{
    return class_exists('ArchivesModel') ? new ArchivesModel() : "";
}

/**
 * initialize_topic()
 *
 * @category theme function
 * @return object
 */
function initialize_topic()
{
    return class_exists('TopicModel') ? new TopicModel() : "";
}

/**
 * initialize_tag()
 *
 * @category theme function
 * @return object
 *
 */
function initialize_tag()
{
    return class_exists('TagModel') ? new TagModel() : "";
}

/**
 * initialize_gallery()
 *
 * @return object
 *
 */
function initialize_gallery()
{
    return (class_exists('GalleryModel')) ? new GalleryModel() : "";
}

/**
 * featured_post()
 *
 * retrieving random headlines
 *
 * @category themes function
 * @return array
 *
 */
function featured_post()
{
    $headlines = class_exists('FrontContentModel') ? FrontContentModel::frontRandomHeadlines(initialize_post()) : "";
    return is_iterable($headlines) ? $headlines : array();
}

/**
 * get_slideshow
 *
 * @category theme function
 * @return mixed|array
 */
function get_slideshow($limit = 5)
{
    if (function_exists('medoo_init')) {
        $database = medoo_init();
    }

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

/**
 * sticky_page()
 *
 * @category theme function
 * @return mixed
 *
 */
function sticky_page()
{
    $sticky_page = class_exists('FrontContentModel') ? FrontContentModel::frontRandomStickyPage(initialize_page()) : "";
    return is_iterable($sticky_page) ? $sticky_page : array();
}

/**
 * random_posts()
 *
 * @category theme function
 * @param int|num $limit
 * @return mixed
 *
 */
function random_posts($start, $end)
{
    $random_posts = class_exists('FrontContentModel') ? FrontContentModel::frontRandomPosts($start, $end, initialize_post()) : "";
    return is_iterable($random_posts) ? $random_posts : array();
}

/**
 * latest_posts()
 *
 * @category theme function
 * @param int|numeric $position
 * @param int|numeric $limit
 * @return array
 *
 */
function latest_posts($limit, $position = null)
{
    $latest_posts = class_exists('FrontContentModel') ? FrontContentModel::frontLatestPosts($limit, initialize_post(), $position) : "";
    return is_iterable($latest_posts) ? $latest_posts : array();
}

/**
 * format_topics
 *
 * @param string $topics_data
 * @return string
 */
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

            $permalink = (rewrite_status() === 'yes') ? permalinks($slug)['cat'] : permalinks($id)['cat'];
            $title_esc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $links[] = "<a href='{$permalink}'>{$title_esc}</a>";
        }
    }

    return implode(', ', $links);
}

/**
 * retrieves_topic_simple()
 *
 * @category theme function
 * @param int|num $id
 *
 */
function retrieves_topic_simple($id)
{

    $categories = array();

    $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug
          FROM tbl_topics, tbl_post_topic WHERE tbl_topics.ID = tbl_post_topic.topic_id
          AND tbl_topics.topic_status = 'Y' AND tbl_post_topic.post_id = ? ";

    $stmt = db_prepared_query($sql, [$id], 'i');

    if ($stmt) {
        // Get the result set
        $result_set = $stmt->get_result();

        // Check if the result set has rows
        if ($result_set && $result_set->num_rows > 0) {
            while ($result = $result_set->fetch_array(MYSQLI_ASSOC)) {
                // Determine permalinks based on rewrite status
                $permalinks = (rewrite_status() === 'yes')
                    ? (permalinks($result['topic_slug'])['cat'] ?? '#')
                    : (permalinks($result['ID'])['cat'] ?? '#');

                // Sanitize the topic title for HTML output
                $topic_title = htmlspecialchars($result['topic_title'], ENT_QUOTES, 'UTF-8');

                // Generate the HTML link
                $categories[] = "<a href='{$permalinks}'>{$topic_title}</a>";
            }
        } else {
            error_log("No topics found for post ID: " . $id);
        }

        // Close the result set
        $result_set->close();
    } else {
        error_log("Database query failed for post ID: " . $id);
    }

    // Return the concatenated HTML links
    return implode("", $categories);
}

/**
 * retrieves_topic_prepared()
 *
 * @category theme function
 * @param int|num $id
 *
 */
function retrieves_topic_prepared($id)
{

    $topics = null;
    $sql = "SELECT tbl_topics.ID, tbl_topics.topic_title, tbl_topics.topic_slug, tbl_topics.topic_status
          FROM tbl_topics, tbl_post_topic
          WHERE tbl_topics.ID = tbl_post_topic.topic_id 
          AND tbl_topics.topic_status = 'Y' 
          AND tbl_post_topic.post_id = ? ";

    $items = db_prepared_query($sql, [$id], 'i')->get_result();
    $count_items = db_num_rows($items);

    if ($count_items > 0) {
        while ($item = $items->fetch_assoc()) {
            $permalinks = ((function_exists('rewrite_status')) && (rewrite_status() === 'yes') ? permalinks($item['topic_slug'])['cat'] : permalinks($item['ID'])['cat']);
            $topics[] = "<a href='" . $permalinks . "'>" . $item['topic_title'] . "</a>";
        }
    }

    return implode("", $topics);
}

/**
 * sidebar_topics()
 *
 * retrieving categories and display it on sidebar
 *
 * @return mixed
 *
 */
function sidebar_topics()
{
    $sidebar_topics = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarTopics(initialize_topic()) : "";
    return is_iterable($sidebar_topics) ? $sidebar_topics : array();
}

/**
 * retrieve_tags()
 *
 * retrieving tags records and display it on sidebar
 *
 */
function retrieve_tags()
{
    return (function_exists('outputting_tags')) ? outputting_tags() : "";
}

/**
 * link_tag()
 *
 * @param num|int $id
 * @return mixed
 *
 */
function link_tag($id)
{
    return (class_exists('FrontContentModel')) ? FrontContentModel::frontLinkTag($id, initialize_tag()) : "";
}

/**
 * link_topic()
 *
 * @param num|int $id
 * @return mixed
 *
 */
function link_topic($id)
{
    return (class_exists('FrontContentModel')) ? FrontContentModel::frontLinkTopic($id, initialize_topic()) : "";
}

/**
 * previous_post()
 *
 * @param int|num $id
 *
 */
function previous_post($id)
{
    $idsanitized = sanitizer($id, 'sql');

    $html = null;

    $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID < '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID DESC LIMIT 1";

    $stmt = db_simple_query($sql);

    if ($stmt->num_rows > 0) {
        while ($rows = $stmt->fetch_array(MYSQLI_ASSOC)) {
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
 * next_post()
 *
 * @category theme function
 *
 * @param int|num $id
 *
 */
function next_post($id)
{
    $idsanitized = sanitizer($id, 'sql');

    $html = null;

    $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE ID > '$idsanitized' AND post_status = 'publish' AND post_type = 'blog' ORDER BY ID ASC LIMIT 1";

    $stmt = db_simple_query($sql);

    if ($stmt->num_rows > 0) {
        while ($rows = $stmt->fetch_array(MYSQLI_ASSOC)) {
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
 * display_galleries()
 *
 * @category theme function
 * @param int|num $start
 * @param int|num $limit
 *
 */
function display_galleries($start, $limit)
{
    $showcase = class_exists('FrontContentModel') ? FrontContentModel::frontGalleries(initialize_gallery(), $start, $limit) : "";
    return is_iterable($showcase) ? $showcase : array();
}

/**
 * retrieve_blog_posts()
 *
 * retrieving all posts published and display it on blog
 *
 * @category theme function
 * @return mixed
 *
 */
function retrieve_blog_posts()
{
    $posts = class_exists('FrontContentModel') ? FrontContentModel::frontBlogPosts(initialize_post()) : "";
    return is_iterable($posts) ? $posts : array();
}

/**
 * retrieve_detail_post()
 *
 * @category theme function
 * @param int $id
 * @return mixed
 *
 */
function retrieve_detail_post($id)
{
    $detail_post = class_exists('FrontContentModel') ? FrontContentModel::frontPostById($id, initialize_post()) : "";
    return is_iterable($detail_post) ? $detail_post : array();
}

/**
 * posts_by_archive
 *
 * retrieving posts by archive requested
 *
 * @category theme function
 * @param array $values
 * @return mixed
 *
 */
function posts_by_archive(array $values)
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByArchive($values, initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}

/**
 * archive_index()
 *
 * Get all archives for index page
 *
 * @category theme function
 * @return mixed
 */
function archive_index()
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontArchiveIndex(initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}

/**
 * posts_by_tag
 *
 * @category theme function
 * @param string $tag
 * @return mixed
 *
 */
function posts_by_tag($tag)
{
    $tags = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByTag($tag, initialize_tag()) : "";
    return is_iterable($tags) ? $tags : array();
}

/**
 * posts_by_tag()
 *
 * Full-Text searching for posts based on tag requested
 *
 * @category theme function
 * @param string $tag
 * @return mixed
 *
 */
function searching_by_tag($tag)
{
    $tags = class_exists('FrontHelper') ? FrontHelper::simpleSearchingTag($tag) : "";
    return is_iterable($tags) ? $tags : array();
}

/**
 * posts_by_category
 *
 * @category theme function
 * @param numeric|int $category
 * @param string $rewrite
 * @return mixed
 */
function posts_by_category($topicId)
{

    $entries = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['entries'];
    $pagination = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['pagination'];

    return is_iterable($entries) ? array('entries' => $entries, 'pagination' => $pagination) : array();
}

/**
 * retrieve_archives()
 *
 * retrieving list of archives and display it on sidebar theme
 *
 * @category theme function
 * @return mixed
 *
 */
function retrieve_archives()
{
    $archives = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarArchives(initialize_archive()) : "";
    return is_iterable($archives) ? $archives : array();
}

/**
 * retrieve_page
 *
 * @category theme function
 * @param int|string|numeric $arg
 * @param string $rewrite
 * @return mixed
 *
 */
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

/**
 * total_comment
 *
 * @param int| $id
 *
 */
function total_comment($id)
{
    $sql = "SELECT COUNT(1) AS total_comments FROM tbl_comments WHERE comment_post_id = ? AND comment_status = 'approved'";
    $result = db_prepared_query($sql, [$id], "i")->get_result();
    $row = $result->fetch_assoc()['total_comments'];

    return isset($row) ? ['total' => $row] : 0;
}

/**
 * block_csrf
 *
 * generating string token
 * @return string
 *
 */
function block_csrf()
{
    return (function_exists('generate_form_token')) ? generate_form_token('comment_form', 32) : "";
}

/**
 * front_navigation
 *
 * @param int|num| $parent
 * @param array $menu
 *
 */
function front_navigation($parent, $menu)
{
    $html = "";

    if (isset($menu['parents'][$parent])) {
        foreach ($menu['parents'][$parent] as $itemId) {
            if (!isset($menu['parents'][$itemId])) {
                $html .= "<li><a  href='" . $menu['items'][$itemId]['menu_link'] . "'>" . $menu['items'][$itemId]['menu_label'] . "</a></li>";
            }
            if (isset($menu['parents'][$itemId])) {
                $html .= "<li class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='" . $menu['items'][$itemId]['menu_link'] . "'>" . $menu['items'][$itemId]['menu_label'] . "</a>";
                $html .= '<ul class="dropdown-menu">';
                $html .= front_navigation($itemId, $menu);
                $html .= '</ul>';
                $html .= "</li>";
            }
        }
    }

    return $html;
}

/**
 * retrieve_site_url
 *
 * @return mixed
 *
 */
function retrieve_site_url()
{
    $config_file = read_config(invoke_config());
    return isset($config_file['app']['url']) ? $config_file['app']['url'] : "";
}

function load_more_comments()
{
}
/**
 * nothing_found
 *
 * @return string
 *
 */
function nothing_found()
{

    $site_url = function_exists('app_url') ? app_url() . "/admin/login.php" : "";

    return <<<_NOTHING_FOUND

<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops !</h4>
  <p>I haven't posted to my blog yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url" target="_blank" rel="noopener noreferrer" title="administrator panel">administrator panel</a> to populate your blog.</p>
</div>

_NOTHING_FOUND;
}

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
