<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class HandleRequest
 *
 * Static facade used by Dispatcher to dispatch front-end requests when
 * SEO-friendly (pretty) permalinks are disabled. It parses the request URI
 * and query string, validates the requested path against a whitelist, and
 * renders the matching template (or delegates to a registered
 * FrontRequestHandler).
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.1
 * @since Since Release 1.0
 *
 */
use Scriptlog\Controller\DownloadController;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Handler\HandlerRegistry;
use Scriptlog\Model\DownloadModel;
use Scriptlog\Service\DownloadService;
use Scriptlog\Service\FrontService;

final class HandleRequest
{
    /**
     * Cached parsed query string (['key' => ..., 'value' => ...]).
     *
     * Computed once per request by {@see isQueryStringRequested()} to avoid
     * re-running escape_html()/explode()/urldecode() for every template and
     * helper that reads it.
     *
     * @var array|null
     */
    private static $queryStringParsed;

    /**
     * Cached request path computed by {@see findRequestToPath()}.
     *
     * @var string|null
     */
    private static $requestPathProcessed;

    /**
     * frontHelper
     *
     * @var FrontService|null
     *
     */
    private static $frontHelper;

    /**
     * ThemeRendererInterface instance for centralized theme rendering
     *
     * @var ThemeRendererInterface|null
     */
    private static ?ThemeRendererInterface $themeRenderer = null;

    /**
     * Set the ThemeRendererInterface instance
     *
     * @param ThemeRendererInterface|null $renderer
     */
    public static function setThemeRenderer(?ThemeRendererInterface $renderer): void
    {
        self::$themeRenderer = $renderer;
    }

    /**
     * handleFrontHelper
     *
     * Resolve the shared FrontService instance from the global registry.
     * Returns null when the service has not been registered so callers can
     * fail safely. The FrontHelper static facade is deprecated; front-end
     * content is now accessed through this service.
     *
     * @return FrontService|null
     *
     */
    public static function handleFrontHelper()
    {
        $service = class_exists('Registry') ? Registry::get('frontService') : null;
        self::$frontHelper = ($service instanceof FrontService) ? $service : null;
        return self::$frontHelper;
    }

    /**
     * findRequestToRules
     *
     * Match the current request URI against a list of route patterns and
     * return the first match's captured parameters.
     *
     * @param array $rules List of route regex patterns.
     * @return array The captured matches for the first matching rule, or an
     *               empty array when no rule matches.
     *
     */
    public static function findRequestToRules($rules)
    {
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR) : '';
        $request_uri = DIRECTORY_SEPARATOR . trim(str_replace($script_name, '', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''), DIRECTORY_SEPARATOR);
        $uri = urldecode($request_uri);

        $parameters = [];

        if (is_array($rules)) {
            foreach ($rules as $value) {
                if (preg_match('~^' . $value . '$~i', $uri, $matches)) {
                    $parameters[] = $matches;
                    return $parameters;
                }
            }
        }

        return $parameters;
    }

    /**
     * findRequestToPath()
     *
     * Resolve the raw request path (query string stripped) once per request
     * and cache the result so repeat callers do no redundant work.
     *
     * @return string The request path without leading/trailing slashes, or a
     *                directory separator for the site root.
     *
     */
    private static function findRequestToPath()
    {
        if (self::$requestPathProcessed !== null) {
            return self::$requestPathProcessed;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? explode(DIRECTORY_SEPARATOR, trim(escape_html($_SERVER['REQUEST_URI']), DIRECTORY_SEPARATOR)) : null;
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? explode(DIRECTORY_SEPARATOR, trim(escape_html($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR)) : null;
        $parts = array_diff_assoc($request_uri, $script_name);

        if (empty($parts)) {
            return self::$requestPathProcessed = DIRECTORY_SEPARATOR;
        }

        $path = implode(DIRECTORY_SEPARATOR, $parts);

        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        return self::$requestPathProcessed = $path;
    }

    /**
     * isRequestToPathValid()
     *
     * @param int $args Zero-based segment index into the request path.
     * @return string|boolean The basename of the requested segment, or false
     *                        when the segment does not exist.
     *
     */
    private static function isRequestToPathValid($args)
    {

        $path = explode(DIRECTORY_SEPARATOR, self::findRequestToPath());

        if (isset($path[$args])) {
            return basename($path[$args]);
        }

        return false;
    }

    /**
     * isMatchedUriRequested()
     *
     * Return the first path segment of the requested URI.
     *
     * @return string The first segment, or an empty string when no request
     *                URI is present.
     *
     */
    public static function isMatchedUriRequested()
    {
        $matched_uri = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) : "";
        $slice_matched = explode(DIRECTORY_SEPARATOR, $matched_uri);
        return isset($slice_matched[0]) ? $slice_matched[0] : "";
    }

    /**
     * isQueryStringRequested()
     *
     * Parse the query string into a ['key' => ..., 'value' => ...] pair. The
     * result is computed once per request and cached for all subsequent
     * callers (templates, permalink helpers and theme utilities).
     *
     * @return array Associative array with 'key' and 'value' entries.
     *
     */
    public static function isQueryStringRequested()
    {
        if (self::$queryStringParsed !== null) {
            return self::$queryStringParsed;
        }

        $string_requested = isset($_SERVER['QUERY_STRING']) ? escape_html($_SERVER['QUERY_STRING']) : "";
        $slice_query = explode('=', $string_requested);
        $get_key = isset($slice_query[0]) ? $slice_query[0] : "";
        $get_value = isset($slice_query[1]) ? urldecode($slice_query[1]) : "";
        return self::$queryStringParsed = array('key' => $get_key, 'value' => $get_value);
    }

    /**
     * checkMatchUriRequested()
     *
     * Compare the first URI segment with the first sanitized RequestPath
     * segment to detect a canonical (matching) request.
     *
     * @return bool True when both segments are identical, false otherwise.
     *
     */
    public static function checkMatchUriRequested()
    {
        $requestPathURI = class_exists('RequestPath') ? new RequestPath() : null;

        if (!($requestPathURI instanceof RequestPath)) {
            return false;
        }

        return (self::isMatchedUriRequested() === $requestPathURI->matched) ? true : false;
    }

    /**
     * allowedPathRequested
     *
     * Check whether the first path segment of the request URI is present in
     * the whitelist, or the request targets the site root.
     *
     * @param array $path Whitelist of allowed first path segments.
     * @return bool True when the request may proceed, false otherwise.
     *
     */
    public static function allowedPathRequested($path)
    {
        $first_segment = self::isRequestToPathValid(0);

        // The empty segment means the root '/' request - always allowed.
        if (empty($first_segment) || in_array($first_segment, $path, true)) {
            return true;
        }

        // If the first segment is not in the whitelist (e.g., 'admin', 'images'), block it.
        return false;
    }

    /**
     * renderTemplate
     *
     * Render a template through the injected ThemeRenderer, or fall back to
     * the legacy global theme functions when no renderer is available.
     *
     * @param string $template   Template name (without .php extension).
     * @param int    $statusCode HTTP status code used by the legacy fallback.
     */
    private static function renderTemplate($template, $statusCode = 200)
    {
        if (self::$themeRenderer) {
            if ($template === '404') {
                self::$themeRenderer->render404();
                return;
            }
            self::$themeRenderer->render($template);
            return;
        }

        http_response_code($statusCode);
        call_theme_header();
        call_theme_content($template);
        call_theme_footer();
    }

    /**
     * deliverQuerySearch
     *
     * Render the search template. When the keyword is too short the search
     * globals are emptied; otherwise a SearchFinder query is run and its
     * results exposed through $GLOBALS for the theme template.
     */
    private static function deliverQuerySearch()
    {
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (empty($keyword) || mb_strlen($keyword, 'UTF-8') < 2) {
            $GLOBALS['search_results'] = [];
            $GLOBALS['search_keyword'] = '';
            $GLOBALS['search_pagination'] = [];
            self::renderTemplate('search');
            return;
        }
        $finder = new SearchFinder();
        $results = $finder->searchAll($keyword);
        $safeResults = is_array($results) ? $results : [];
        $sanitizedKeyword = isset($safeResults['keyword']) ? $safeResults['keyword'] : '';
        unset($safeResults['error']);

        $page = isset($safeResults['page']) ? (int)$safeResults['page'] : 1;
        $totalPages = isset($safeResults['totalPages']) ? (int)$safeResults['totalPages'] : 0;
        $totalRows = isset($safeResults['totalRows']) ? (int)$safeResults['totalRows'] : 0;

        $GLOBALS['search_results'] = $safeResults;
        $GLOBALS['search_keyword'] = $sanitizedKeyword;
        $GLOBALS['search_pagination'] = [
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'html' => ''
        ];
        self::renderTemplate('search');
    }

    /**
     * handleDownloadRequest
     *
     * Dispatch a download request read from the query string. When the
     * request URI also contains a '/file' segment the download controller
     * streams the file; otherwise the download landing page is rendered.
     *
     * @param string $identifier Download identifier (UUID).
     */
    private static function handleDownloadRequest($identifier)
    {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/file') !== false) {
            $identifier = preg_replace('#/file$#', '', $identifier);
            $downloadController = self::getDownloadController();
            $downloadController->download($identifier);
            return;
        }

        self::renderTemplate('download');
    }

    /**
     * handlePathBasedDownload
     *
     * Handle '/download/{identifier}' style paths when permalinks are
     * disabled. Renders the download landing page or streams the file for
     * an additional '/file' path segment.
     *
     * @param string $firstSegment First path segment of the request.
     * @return bool True when the request was handled as a download, false
     *              otherwise so the caller can continue dispatching.
     */
    private static function handlePathBasedDownload($firstSegment)
    {
        if ($firstSegment !== 'download') {
            return false;
        }

        $requestPath = self::findRequestToPath();
        $pathParts = explode('/', trim($requestPath, '/'));
        $identifier = $pathParts[1] ?? '';
        $isFileDownload = isset($pathParts[2]) && $pathParts[2] === 'file';

        if (empty($identifier) || !preg_match('/^[a-f0-9\-]{36}$/', $identifier)) {
            return false;
        }

        if ($isFileDownload) {
            $downloadController = self::getDownloadController();
            $downloadController->download($identifier);
            return true;
        }

        $GLOBALS['download_identifier'] = $identifier;
        self::renderTemplate('download');
        return true;
    }

    private static function getDownloadController()
    {
        $controller = class_exists('Registry') ? Registry::get('downloadController') : null;
        if ($controller instanceof DownloadController) {
            return $controller;
        }
        return new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
    }

    /**
     * deliverQueryString
     *
     * Dispatch a query-string request to its handler, its dedicated delivery
     * method, or the default (home) renderer.
     *
     */
    public static function deliverQueryString()
    {
        $queryKey = self::isQueryStringRequested()['key'];

        $registry = class_exists('Registry') ? Registry::get('handlerRegistry') : null;
        if ($registry instanceof HandlerRegistry && $registry->has($queryKey)) {
            $registry->get($queryKey)->handle([
                'key'   => $queryKey,
                'value' => self::isQueryStringRequested()['value'],
            ]);
            return;
        }

        switch ($queryKey) {
            case 'p':
                self::deliverQueryPost();
                break;
            case 'cat':
                self::deliverQueryCategory();
                break;
            case 'pg':
                self::deliverQueryPage();
                break;
            case 'a':
                self::deliverQueryArchive();
                break;
            case 'tag':
                self::deliverQueryTag();
                break;
            case 'blog':
                self::renderTemplate('blog');
                break;
            case 'privacy':
                self::renderTemplate('privacy');
                break;
            case 'download':
                self::deliverQueryDownload();
                break;
            case 'q':
                self::deliverQuerySearch();
                break;
            default:
                self::deliverDefaultQuery();
                break;
        }
    }

    /**
     * deliverQueryPost
     *
     * Render a single post from the 'p' query-string key.
     */
    private static function deliverQueryPost()
    {
        $value = self::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $frontService = self::handleFrontHelper();
        $query_post = $frontService ? $frontService->getSimplePost($value) : null;
        if (empty($query_post['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('single');
    }

    /**
     * deliverQueryCategory
     *
     * Render a category archive from the 'cat' query-string key.
     */
    private static function deliverQueryCategory()
    {
        $value = self::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $frontService = self::handleFrontHelper();
        $query_cat = $frontService ? $frontService->getSimpleTopic($value) : null;
        if (empty($query_cat['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('category');
    }

    /**
     * deliverQueryPage
     *
     * Render a static page from the 'pg' query-string key.
     */
    private static function deliverQueryPage()
    {
        $value = self::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $frontService = self::handleFrontHelper();
        $query_page = $frontService ? $frontService->getSimplePage($value) : null;
        if (empty($query_page['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('page');
    }

    /**
     * deliverQueryArchive
     *
     * Render the monthly archive from the 'a' query-string key.
     */
    private static function deliverQueryArchive()
    {
        $value = self::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        self::renderTemplate('archive');
    }

    /**
     * deliverQueryTag
     *
     * Render the tag archive from the 'tag' query-string key.
     */
    private static function deliverQueryTag()
    {
        $value = self::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        self::renderTemplate('tag');
    }

    /**
     * deliverQueryDownload
     *
     * Dispatch a download request from the 'download' query-string key.
     */
    private static function deliverQueryDownload()
    {
        $identifier = self::isQueryStringRequested()['value'] ?? '';
        if (empty($identifier)) {
            direct_page('', 302);
            return;
        }

        self::handleDownloadRequest($identifier);
    }

    /**
     * deliverDefaultQuery
     *
     * Fallback dispatch for requests without a recognized query-string key.
     * Renders the home template for the root, whitelisted legacy entry
     * points and matched URIs; otherwise renders a 404.
     */
    private static function deliverDefaultQuery()
    {
        $firstSegment = self::isRequestToPathValid(0);
        $validSegments = ['', 'index.php', 'blog', 'privacy', 'download', 'download_file', 'rss.php', 'atom.php'];
        $queryStringKey = self::isQueryStringRequested()['key'];

        if (self::handlePathBasedDownload($firstSegment)) {
            return;
        }

        if (!empty($queryStringKey)) {
            if (self::checkMatchUriRequested()) {
                self::renderTemplate('home');
                return;
            }
            self::renderTemplate('404', 404);
            return;
        }

        if (empty($firstSegment) || in_array($firstSegment, $validSegments, true)) {
            self::renderTemplate('home');
            return;
        }

        self::renderTemplate('404', 404);
    }
}
