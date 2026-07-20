<?php

namespace Scriptlog\Core;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * final class HandleRequest
 *
 * this class used by Dispatcher to check allowed path requested
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 *
 */
use Scriptlog\Controller\DownloadController;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Handler\HandlerRegistry;
use Scriptlog\Model\DownloadModel;
use Scriptlog\Service\DownloadService;

final

class HandleRequest
{
    /**
     * requestPathURI
     *
     * @var object
     *
     */
    private static $requestPathURI;

    /**
     * frontHelper
     *
     * @var object
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
     * @return object
     *
     */
    public static function handleFrontHelper()
    {
        self::$frontHelper = class_exists('FrontHelper') ? new FrontHelper() : '';
        return self::$frontHelper;
    }

    /**
     * findRequestToRules
     *
     * @param array $rules
     * @return array
     *
     */
    public static function findRequestToRules($rules)
    {

        $script_name = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR) : '';
        $request_uri = DIRECTORY_SEPARATOR . trim(str_replace($script_name, '', $_SERVER['REQUEST_URI']), DIRECTORY_SEPARATOR);
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
     * @return string
     *
     */
    private static function findRequestToPath()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? explode(DIRECTORY_SEPARATOR, trim(escape_html($_SERVER['REQUEST_URI']), DIRECTORY_SEPARATOR)) : null;
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? explode(DIRECTORY_SEPARATOR, trim(escape_html($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR)) : null;
        $parts = array_diff_assoc($request_uri, $script_name);

        if (empty($parts)) {
            return DIRECTORY_SEPARATOR;
        }

        $path = implode(DIRECTORY_SEPARATOR, $parts);

        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        return $path;
    }

    /**
     * isRequestToPathValid()
     *
     * @param int $args
     * @uses HandleRequest::findRequestPath
     * @return string|boolean return string if return true otherwise will return false
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
     * @return mixed
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
     * @return mixed
     *
     */
    public static function isQueryStringRequested()
    {
        $string_requested = isset($_SERVER['QUERY_STRING']) ? escape_html($_SERVER['QUERY_STRING']) : null;
        $slice_query = explode('=', $string_requested);
        $get_key = isset($slice_query[0]) ? $slice_query[0] : "";
        $get_value = isset($slice_query[1]) ? urldecode($slice_query[1]) : "";
        return array('key' => $get_key, 'value' => $get_value);
    }

    /**
     * checkMatchUriRequested()
     *
     * @return bool
     *
     */
    public static function checkMatchUriRequested()
    {
        self::$requestPathURI = class_exists('RequestPath') ? new RequestPath() : '';
        return (self::isMatchedUriRequested() === self::$requestPathURI->matched) ? true : false;
    }

    /**
     * allowedPathRequested
     * * Checking whether the first segment of the URI is in the whitelist.
     */
    public static function allowedPathRequested($path, $_rules)
    {
        // Fix: We only check if the first path segment (e.g., 'post', 'category')
        // is present in the simple $path whitelist. This allows the Dispatcher
        // to proceed to its full regex matching.
        $first_segment = self::isRequestToPathValid(0);

        // Check if the first segment is an empty string (meaning root '/' request)
        // or if it is present in the provided whitelist ($path).
        if (empty($first_segment) || in_array($first_segment, $path, true)) {
            return true;
        }

        // If the first segment is not in the whitelist (e.g., 'admin', or 'images'), block it.
        return false;
    }

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

    private static function handleDownloadRequest($identifier)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/file') !== false) {
            $identifier = preg_replace('#/file$#', '', $identifier);
            $downloadController = self::getDownloadController();
            $downloadController->download($identifier);
            return;
        }

        self::renderTemplate('download');
    }

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
     */
    public static function deliverQueryString()
    {
        $queryKey = static::isQueryStringRequested()['key'];

        $registry = class_exists('Registry') ? Registry::get('handlerRegistry') : null;
        if ($registry instanceof HandlerRegistry && $registry->has($queryKey)) {
            $registry->get($queryKey)->handle([
                'key'   => $queryKey,
                'value' => static::isQueryStringRequested()['value'],
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
            default:
                self::deliverDefaultQuery();
                break;
        }
    }

    private static function deliverQueryPost()
    {
        $value = static::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $query_post = self::handleFrontHelper()->grabSimpleFrontPost($value);
        if (empty($query_post['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('single');
    }

    private static function deliverQueryCategory()
    {
        $value = static::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $query_cat = self::handleFrontHelper()->grabSimpleFrontTopic($value);
        if (empty($query_cat['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('category');
    }

    private static function deliverQueryPage()
    {
        $value = static::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        $query_page = self::handleFrontHelper()->grabSimpleFrontPage($value);
        if (empty($query_page['ID'])) {
            self::renderTemplate('404', 404);
            return;
        }

        self::renderTemplate('page');
    }

    private static function deliverQueryArchive()
    {
        $value = static::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        self::renderTemplate('archive');
    }

    private static function deliverQueryTag()
    {
        $value = static::isQueryStringRequested()['value'];
        if (empty($value)) {
            direct_page('', 302);
            return;
        }

        self::renderTemplate('tag');
    }

    private static function deliverQueryDownload()
    {
        $identifier = static::isQueryStringRequested()['value'] ?? '';
        if (empty($identifier)) {
            direct_page('', 302);
            return;
        }

        self::handleDownloadRequest($identifier);
    }

    private static function deliverDefaultQuery()
    {
        $firstSegment = self::isRequestToPathValid(0);
        $validSegments = ['', 'index.php', 'blog', 'privacy', 'download', 'download_file', 'rss.php', 'atom.php'];
        $queryStringKey = static::isQueryStringRequested()['key'];

        if (self::handlePathBasedDownload($firstSegment)) {
            return;
        }

        if (!empty($queryStringKey)) {
            if (static::checkMatchUriRequested()) {
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
