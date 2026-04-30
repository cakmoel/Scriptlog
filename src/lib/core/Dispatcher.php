<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Dispatcher
 *
 * @category Core Class
 * @author   M.Noermoehammad
 * @uses HandleRequest::allowedPathRequested if SEO-Friendly URL enabled otherwise then
 * @uses HandleRequest::deliverQueryString when it disabled
 * @license  MIT
 * @see https://stackoverflow.com/questions/11696718/htaccess-rewrite-book-phpid-1234-to-book-1234
 * @see https://stackoverflow.com/questions/1039725/how-to-do-url-re-writing-in-php
 * @see https://stackoverflow.com/questions/60339372/how-to-rewrite-url-by-htaccess-with-basic-parameters-in-core-php
 * @see https://httpd.apache.org/docs/trunk/mod/mod_dir.html#fallbackresource
 * @see https://stackoverflow.com/questions/26419426/htaccess-url-re-styling-image-url-to-seo-friendly
 * @see https://mediatemple.net/community/products/dv/204643270/using-htaccess-rewrite-rules
 * @see https://stackoverflow.com/questions/16388959/url-rewriting-with-php
 * @version  1.1
 * @since    Since Release 0.1
 *
 */
class Dispatcher
{
    /**
     * route
     *
     * @var mixed
     *
     */
    private $route = [];

    /**
     * Theme's Directory
     *
     * @var string
     *
     */
    private $theme_dir;

    /**
     * Constructor
     * Registry route and Initialize an instantiate of theme
     */
    public function __construct()
    {

        if (Registry::isKeySet('route')) {
            $this->route = Registry::get('route');
        }

        $theme = $this->invokeTheme();
        $this->theme_dir = APP_ROOT . APP_THEME . escape_html($theme['theme_directory']) . DIRECTORY_SEPARATOR;
    }

    /**
     * dispatch
     *
     */
    public function dispatch()
    {
        if (rewrite_status() === 'yes') {
            $this->handleSeoFriendlyUrl();
        } else {
            $this->handleQueryStringUrl();
        }
    }

    /**
     * handleSeoFriendlyUrl
     *
     */
    public function handleSeoFriendlyUrl()
    {
        $requestUri = $this->requestURI();

        // Handle download query string even when SEO URLs are enabled
        // This ensures ?download=xxx works regardless of permalink setting
        if (isset($_GET['download']) && !empty($_GET['download'])) {
            $downloadIdentifier = trim($_GET['download']);

            // Check if it's a file download request
            if (strpos($_SERVER['REQUEST_URI'], '/file') !== false) {
                $downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
                $downloadController->download($downloadIdentifier);
                return;
            } else {
                // Pass identifier to template via global variable
                $GLOBALS['download_identifier'] = $downloadIdentifier;
                http_response_code(200);
                call_theme_header();
                call_theme_content('download');
                call_theme_footer();
                return;
            }
        }

        // Handle locale prefix if LocaleRouter is available
        $localeRouter = null;
        if (class_exists('LocaleRouter')) {
            $localeRouter = new LocaleRouter();
            $locale = $localeRouter->extractLocale($requestUri);

            if ($locale) {
                // Set locale for content filtering
                if (class_exists('I18nManager')) {
                    $i18n = I18nManager::getInstance();
                    $i18n->setLocale($locale);
                }

                // Remove locale from path for route matching
                $requestUri = '/' . $localeRouter->stripLocalePrefix($requestUri);
            }
        }

        // The simplified allowedPathRequested now lets the request pass if the first segment is valid.
        if (!HandleRequest::allowedPathRequested($this->whiteListPathRequested(), $this->route)) {
            $this->errorNotFound();
            return;
        }

        // 1. Get RequestPath object from the Registry
        $requestPath = class_exists('Registry') ? Registry::get('uri') : null;

        foreach ($this->route as $key => $pattern) {
            $matches = []; // Initialize $matches as an empty array for safety

            // 2. Perform the regex match, capturing results into $matches
            if (preg_match('~^' . $pattern . '$~i', $requestUri, $matches)) {
                // 3. Ensure $matches is an array and the object is valid before calling the setter
                if (is_object($requestPath) && method_exists($requestPath, 'setParameters') && is_array($matches)) {
                    $requestPath->setParameters($matches);
                }

                // 4. Check if content exists before rendering
                if (!$this->validateContentExists($key, $requestPath)) {
                    $this->errorNotFound();
                    return;
                }

                // 5. Special handling for download_file - bypass theme headers/footers
                if ($key === 'download_file') {
                    $this->renderDownloadFile($requestPath);
                    return;
                }

                // 6. Render the found template
                $this->renderTheme($key);
                return;
            }
        }

        // If the loop finishes without a match, then it's a true 404.
        $this->errorNotFound();
    }

    /**
     * Validate content exists in database
     *
     * @param string $routeKey
     * @param object $requestPath
     * @return bool
     */
    private function validateContentExists($routeKey, $requestPath)
    {
        if (!is_object($requestPath)) {
            return true;
        }

        switch ($routeKey) {
            case 'single':
                $postId = isset($requestPath->id) ? $requestPath->id : null;
                $postSlug = isset($requestPath->post) ? $requestPath->post : null;

                if (empty($postId) || empty($postSlug)) {
                    return false;
                }

                $post = class_exists('FrontHelper') ? FrontHelper::grabPreparedFrontPostById($postId) : null;

                if (empty($post) || !is_array($post)) {
                    return false;
                }

                // Validate slug matches - redirect to 404 if slug is incorrect
                $dbSlug = isset($post['post_slug']) ? $post['post_slug'] : '';
                return ($dbSlug === $postSlug);

            case 'page':
                $pageSlug = isset($requestPath->page) ? $requestPath->page : null;

                if (empty($pageSlug)) {
                    return false;
                }

                $page = class_exists('FrontHelper') ? FrontHelper::grabPreparedFrontPageBySlug($pageSlug) : null;

                if (empty($page) || !is_array($page)) {
                    return false;
                }

                // Validate slug matches - redirect to 404 if slug is incorrect
                $dbSlug = isset($page['post_slug']) ? $page['post_slug'] : '';
                return ($dbSlug === $pageSlug);

            case 'category':
                $categorySlug = isset($requestPath->category) ? $requestPath->category : null;
                if (empty($categorySlug)) {
                    return false;
                }
                $topic = class_exists('FrontHelper') ? FrontHelper::grabPreparedFrontTopicBySlug($categorySlug) : null;
                return !empty($topic) && is_array($topic);

            case 'archive':
                $month = isset($requestPath->param1) ? $requestPath->param1 : null;
                $year = isset($requestPath->param2) ? $requestPath->param2 : null;
                if (empty($month) || empty($year)) {
                    return false;
                }
                // Check if there are posts in this archive
                $archives = class_exists('FrontHelper') ? FrontHelper::grabSimpleFrontArchive() : [];
                $monthInt = (int)$month;
                $yearInt = (int)$year;
                foreach ($archives as $archive) {
                    if ((int)$archive['month_archive'] === $monthInt && (int)$archive['year_archive'] === $yearInt) {
                        return true;
                    }
                }
                return false;

            case 'tag':
                $tagSlug = isset($requestPath->tag) ? $requestPath->tag : null;
                if (empty($tagSlug)) {
                    return false;
                }
                // Tags are stored as comma-separated values in post_tags column
                // Use FrontHelper::simpleSearchingTag which does full-text search
                $tag = class_exists('FrontHelper') ? FrontHelper::simpleSearchingTag($tagSlug) : null;
                return !empty($tag);

            case 'archives':
                // Archive index - always valid if archives exist
                return true;

            default:
                return true;
        }
    }

    /**
     * handleQueryStringUrl
     *
     */
    private function handleQueryStringUrl()
    {
        if (class_exists('HandleRequest')) {
            HandleRequest::deliverQueryString();
        }
    }

    /**
     * RequestURI()
     *
     * @return mixed
     *
     */
    private function requestURI()
    {
        // Get the path part of the REQUEST_URI, ignoring the query string
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Determine the base path (if installed in a subdirectory)
        $basePath = dirname($_SERVER['SCRIPT_NAME']);

        // Remove the base path from the request path if it exists
        if ($basePath !== '/' && function_exists('str_starts_with') ? str_starts_with($path, $basePath) : (strncmp($path, $basePath, strlen($basePath)) === 0)) {
            $path = substr($path, strlen($basePath));
        }

        // Normalize: Ensure single leading slash and no trailing slash
        $path = '/' . trim($path, '/');

        // The resulting string should be exactly: /post/1/lorem-ipsum
        return urldecode($path);
    }

    /* InvokeTheme
     *
     * invoking theme actived
     * @return mixed
     *
     */
    private function invokeTheme()
    {
        return theme_identifier();
    }

    /**
     * renderTheme
     *
     * @param string $template
     *
     */
    private function renderTheme($template)
    {
        http_response_code(200);
        call_theme_header();
        call_theme_content($template);
        call_theme_footer();
    }

    /**
     * renderDownloadFile
     *
     * Handles actual file download - bypasses theme headers/footers
     *
     * @param RequestPath $requestPath
     *
     */
    private function renderDownloadFile($requestPath)
    {
        // Make $requestPath available to the download handler
        // Directly include download handler without theme wrappers
        include_once APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . 'download_file.php';
    }

    /* errorNotFound

    * set 404 error page
    *
    * @param string $theme_dir
    *
    */
    private function errorNotFound()
    {
        http_response_code(404);
        include $this->theme_dir . 'header.php';
        include $this->theme_dir . '404.php';
        include $this->theme_dir . 'footer.php';
    }

    /**
     * Get whitelisted paths
     */
    private function whiteListPathRequested()
    {
        $base = ['/', '//', 'post', 'page', 'blog', 'category', 'archive', 'archives', 'tag', 'privacy', 'download', 'download_file'];

        // Add locale prefixes if LocaleRouter is available
        if (class_exists('LocaleRouter')) {
            $localeRouter = new LocaleRouter();
            $detector = $localeRouter->getDetector();
            $locales = $detector->getAvailableLocales();

            foreach ($locales as $locale) {
                $base[] = $locale;
                $base[] = "{$locale}/post";
                $base[] = "{$locale}/page";
                $base[] = "{$locale}/blog";
                $base[] = "{$locale}/category";
                $base[] = "{$locale}/archive";
                $base[] = "{$locale}/archives";
                $base[] = "{$locale}/tag";
                $base[] = "{$locale}/privacy";
                $base[] = "{$locale}/download";
            }
        }

        return $base;
    }
}
