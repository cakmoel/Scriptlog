<?php

/**
 * api-request.php
 *
 * Bootstrap-safe helpers for the front-controller API hand-off.
 *
 * This file is required directly by index.php *before* lib/main.php runs, so it
 * must not depend on the SCRIPTLOG constant, the autoloader, or any other
 * utility. It only declares pure functions and has no side effects, which is
 * why it intentionally omits the usual `defined('SCRIPTLOG') || die()` guard
 * (lib/ is already blocked at the web-server level).
 *
 * Why this exists: Apache (.htaccess) and the generated nginx include route
 * `/api/*` straight to `api/index.php`. Servers that send every unknown path
 * to the front controller instead - DDEV/nginx without the generated include,
 * PHP's built-in server, default LiteSpeed - used to render the theme 404 for
 * `/api/v1`, so the sidebar autocomplete always reported "An error occurred
 * during search." See docs/dev-docs/TROUBLESHOOTING.md (issue 55).
 *
 * @category  function
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.1
 * @since     Since Release 1.11.0
 */

if (!function_exists('scriptlog_install_base_path')) {

    /**
     * URL base path of the installation, relative to the document root.
     *
     * Returns "" for a root install and "/scriptlog" when the application lives
     * in a sub-directory of the document root. The front controller directory is
     * used instead of SCRIPT_NAME because not every SAPI reports the front
     * controller there: PHP's built-in server reports the nearest matching file
     * (e.g. "/api/index.php" for "/api/v1") and PATH_INFO-style URIs.
     *
     * @param string $documentRoot     Document root (usually $_SERVER['DOCUMENT_ROOT']).
     * @param string $frontControllerDir Absolute directory holding index.php.
     * @return string Base path without a trailing slash (may be empty).
     */
    function scriptlog_install_base_path($documentRoot, $frontControllerDir)
    {
        $documentRoot = (string) $documentRoot;
        $frontControllerDir = (string) $frontControllerDir;

        if ($documentRoot === '' || $frontControllerDir === '') {
            return '';
        }

        $realDocumentRoot = realpath($documentRoot);
        $realFrontDir = realpath($frontControllerDir);

        if ($realDocumentRoot === false || $realFrontDir === false) {
            return '';
        }

        $realDocumentRoot = rtrim(str_replace('\\', '/', $realDocumentRoot), '/');
        $realFrontDir = rtrim(str_replace('\\', '/', $realFrontDir), '/');

        if ($realDocumentRoot === '' || $realFrontDir === $realDocumentRoot) {
            return '';
        }

        if (strpos($realFrontDir, $realDocumentRoot . '/') === 0) {
            return substr($realFrontDir, strlen($realDocumentRoot));
        }

        return '';
    }
}

if (!function_exists('scriptlog_request_path_relative_to_front_controller')) {

    /**
     * Resolve a request path relative to the front controller directory.
     *
     * Makes the API check work for both root installs (`/api/v1`) and
     * sub-directory installs (`/scriptlog/api/v1`), and strips the query
     * string before matching.
     *
     * When $frontControllerDir is supplied the install base is derived from the
     * document root (robust for every SAPI). Otherwise the base is taken from
     * SCRIPT_NAME, which is only meaningful when it points at a PHP script.
     *
     * @param string      $requestUri        Raw REQUEST_URI (query string allowed).
     * @param string      $scriptName        Front controller SCRIPT_NAME.
     * @param string|null $frontControllerDir Absolute directory holding index.php.
     * @return string Path beginning with "/" (never empty).
     */
    function scriptlog_request_path_relative_to_front_controller($requestUri, $scriptName, $frontControllerDir = null)
    {
        $path = parse_url((string) $requestUri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        if ($frontControllerDir !== null) {
            $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
            $base = scriptlog_install_base_path($documentRoot, $frontControllerDir);
        } else {
            $script = str_replace('\\', '/', (string) $scriptName);

            if ($script === '') {
                $script = '/index.php';
            }

            // A non-PHP SCRIPT_NAME is a request path (router / PATH_INFO
            // style), not a front controller, so it carries no install base.
            $base = substr($script, -4) === '.php'
                ? rtrim(str_replace('\\', '/', dirname($script)), '/')
                : '';

            if ($base === '.' || $base === '/') {
                $base = '';
            }
        }

        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        if ($path === '') {
            return '/';
        }

        return $path[0] === '/' ? $path : '/' . $path;
    }
}

if (!function_exists('scriptlog_is_api_request')) {

    /**
     * Whether the current request must be handled by api/index.php.
     *
     * Matches `/api`, `/api/`, and `/api/...` only - a path such as `/apiary`
     * must keep flowing through the normal dispatcher.
     *
     * @param string|null $requestUri        Raw REQUEST_URI; defaults to $_SERVER.
     * @param string|null $scriptName        Front controller SCRIPT_NAME; defaults to $_SERVER.
     * @param string|null $frontControllerDir Absolute directory holding index.php.
     * @return bool
     */
    function scriptlog_is_api_request($requestUri = null, $scriptName = null, $frontControllerDir = null)
    {
        if ($requestUri === null) {
            $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }

        if ($scriptName === null) {
            $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
        }

        $path = scriptlog_request_path_relative_to_front_controller($requestUri, $scriptName, $frontControllerDir);

        return preg_match('#^/api(?:/|$)#', $path) === 1;
    }
}
