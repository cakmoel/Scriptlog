<?php

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
final class HandleRequest
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
            foreach ($rules as $key => $value) {
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

        if (($position = strpos($path, '?')) !== false) {
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
        } else {
            return false;
        }
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
    public static function allowedPathRequested($path, $rules)
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

    /**
     * deliverQueryString
     *
     */
    public static function deliverQueryString()
    {

        switch (static::isQueryStringRequested()['key']) {
            case 'p':
                // Deliver request to a single post entry
                if (! empty(static::isQueryStringRequested()['value'])) {
                    $query_post = self::handleFrontHelper()->grabSimpleFrontPost(static::isQueryStringRequested()['value']);

                    if (empty($query_post['ID'])) {
                        http_response_code(404);
                        call_theme_header();
                        call_theme_content('404');
                        call_theme_footer();
                    } else {
                        http_response_code(200);
                        call_theme_header();
                        call_theme_content('single');
                        call_theme_footer();
                    }
                } else {
                    direct_page('', 302);
                }

                break;

            case 'cat':
                // Deliver request to a single category or topic
                if (! empty(static::isQueryStringRequested()['value'])) {
                    $query_cat = self::handleFrontHelper()->grabSimpleFrontTopic(static::isQueryStringRequested()['value']);

                    if (empty($query_cat['ID'])) {
                        http_response_code(404);
                        call_theme_header();
                        call_theme_content('404');
                        call_theme_footer();
                    } else {
                        http_response_code(200);
                        call_theme_header();
                        call_theme_content('category');
                        call_theme_footer();
                    }
                } else {
                    direct_page('', 302);
                }

                break;

            case 'pg':
                // Deliver request to a single page
                if (! empty(static::isQueryStringRequested()['value'])) {
                    $query_page = self::handleFrontHelper()->grabSimpleFrontPage(static::isQueryStringRequested()['value']);

                    if (empty($query_page['ID'])) {
                        http_response_code(404);
                        call_theme_header();
                        call_theme_content('404');
                        call_theme_footer();
                    } else {
                        http_response_code(200);
                        call_theme_header();
                        call_theme_content('page');
                        call_theme_footer();
                    }
                } else {
                    direct_page('', 302);
                }

                break;

            case 'a':
                // Deliver request to an archives
                if (! empty(static::isQueryStringRequested()['value'])) {
                    http_response_code(200);
                    call_theme_header();
                    call_theme_content('archive');
                    call_theme_footer();
                } else {
                    direct_page('', 302);
                }

                break;

            case 'tag':
                // Deliver request to a tag - always render tag page, let template handle "no results"
                if (! empty(static::isQueryStringRequested()['value'])) {
                    $tagValue = static::isQueryStringRequested()['value'];

                    // Just render the tag page - let the template handle if no posts found
                    http_response_code(200);
                    call_theme_header();
                    call_theme_content('tag');
                    call_theme_footer();
                } else {
                    direct_page('', 302);
                }

                break;

            case 'blog':
                // Deliver request to blog
                http_response_code(200);
                call_theme_header();
                call_theme_content('blog');
                call_theme_footer();

                break;

            case 'privacy':
                // Deliver request to privacy policy page
                http_response_code(200);
                call_theme_header();
                call_theme_content('privacy');
                call_theme_footer();

                break;

            default:  # default request will be delivered
                # When permalinks are disabled, check if the path is valid
                # Get the first path segment (without query string)
                $firstSegment = self::isRequestToPathValid(0);

                # Define valid segments when permalinks are disabled
                $validSegments = ['', 'index.php', 'blog', 'privacy', 'download', 'download_file', 'rss.php', 'atom.php'];

                # Also check query string keys that are handled
                $queryStringKey = static::isQueryStringRequested()['key'];

                if (!empty($queryStringKey)) {
                    # Has query string - let the switch handle it above
                    if (false === static::checkMatchUriRequested()) {
                        http_response_code(404);
                        call_theme_header();
                        call_theme_content('404');
                        call_theme_footer();
                    } else {
                        http_response_code(200);
                        call_theme_header();
                        call_theme_content('home');
                        call_theme_footer();
                    }
                } elseif (empty($firstSegment) || in_array($firstSegment, $validSegments, true)) {
                    # No query string but valid path segment - show home
                    http_response_code(200);
                    call_theme_header();
                    call_theme_content('home');
                    call_theme_footer();
                } else {
                    # Invalid path - show 404
                    http_response_code(404);
                    call_theme_header();
                    call_theme_content('404');
                    call_theme_footer();
                }

                break;
        }
    }
}
