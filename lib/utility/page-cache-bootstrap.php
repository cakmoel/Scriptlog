<?php

// lib/utility/page-cache-bootstrap.php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Early page-cache serving (F11).
 *
 * index.php serves a cached page only after lib/main.php has run the complete
 * bootstrap (config, cipher key, 230 utilities, PDO, ~12 DAOs/services, i18n,
 * admin registry, dispatcher, security headers) - none of which a cache hit
 * uses. A hit needs four things:
 *
 *   1. the cache file path (APP_CACHE_DIR, defined by lib/common.php),
 *   2. the shared security headers (lib/utility/secure-http-headers.php),
 *   3. a CSP nonce, and
 *   4. a comment-form CSRF token minted against the visitor's encrypted
 *      session (the cached markup embeds both).
 *
 * This file loads only those pieces, then delegates to the existing
 * page_cache_exists()/page_cache_serve() so freshness rules, marker stripping
 * and dynamic-markup refresh stay in one place.
 *
 * It is called from lib/main.php after Composer, Dotenv, the PHP-runtime guard
 * and the backward-compatibility alias map have run, so those are not repeated
 * here. Every decision that cannot be reproduced exactly (hard kill-switch off,
 * non-GET, htmx, search, logged-in, locale switch, non-front-controller,
 * missing utility/function, DB failure) returns false and lets the normal
 * bootstrap run. The early path can therefore never make a response worse -
 * only faster.
 *
 * @category Bootstrap
 * @author   M.Noermoehammad
 * @license  MIT
 * @since    1.13.0
 */

if (!function_exists('scriptlog_page_cache_early_applies')) {

    /**
     * Decide whether this request may be served by the early cache path.
     *
     * Cheap, side-effect-free checks only (constants, superglobals, cookies).
     * The session-based logged-in check still happens inside
     * page_cache_exists() after the session has been started.
     *
     * @category Bootstrap
     * @return bool True when the early path may attempt a cache serve
     */
    function scriptlog_page_cache_early_applies()
    {
        // Only the hard kill-switch is safe to honour without the DB-backed
        // setting; a disabled setting must fall through to the full bootstrap.
        if (!defined('APP_CACHE') || APP_CACHE !== true) {
            return false;
        }

        // CLI never serves cached pages: tests and maintenance scripts include
        // lib/main.php directly and expect the framework to boot.
        if (PHP_SAPI === 'cli' && !defined('SCRIPTLOG_TEST_MODE')) {
            return false;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return false;
        }

        // HTMX fragments and search results are never served cross-user.
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            return false;
        }

        if (isset($_GET['search']) || isset($_GET['s']) || isset($_GET['q']) || isset($_GET['switch-lang'])) {
            return false;
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (is_string($requestPath) && preg_match('#/search/?$#', $requestPath)) {
            return false;
        }

        // Remember-me / session logins must never receive a shared cached page.
        if (
            isset($_COOKIE['scriptlog_auth'])
            || isset($_COOKIE['scriptlog_validator'])
            || isset($_COOKIE['scriptlog_selector'])
        ) {
            return false;
        }

        // lib/main.php is also included by admin/, api/ and standalone scripts.
        // Only the front controller participates in the page cache.
        if (isset($_SERVER['SCRIPT_FILENAME']) && basename($_SERVER['SCRIPT_FILENAME']) !== 'index.php') {
            return false;
        }

        if (is_string($requestPath) && preg_match('#^/(admin|api|install)(/|$)#i', $requestPath)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('scriptlog_page_cache_early_utilities')) {

    /**
     * Utility files required by the early cache path.
     *
     * Transitive closure of the functions a cache hit calls (page cache,
     * shared security headers, form token, encrypted session, app settings).
     * Anything not listed here stays unloaded; if the list ever drifts, the
     * function_exists() guard in scriptlog_serve_page_cache_early() falls back
     * to the full bootstrap instead of failing.
     *
     * @category Bootstrap
     * @return array<int, string> Utility file basenames
     */
    function scriptlog_page_cache_early_utilities()
    {
        return array(
            'app-info.php',
            'app-settings.php',
            'app-url.php',
            'cookies-baked.php',
            'form-security.php',
            'generate-token.php',
            'get-ip-address.php',
            'get-table-prefix.php',
            'invoke-config.php',
            'is-ssl.php',
            'medooin.php',
            'page-cache.php',
            'random-generator.php',
            'safe-html.php',
            'sanitize-email.php',
            'scriptlog-error.php',
            'secure-http-headers.php',
            'session-save-path.php',
            'start-session-on-site.php',
            'turn-on-session.php',
        );
    }
}

if (!function_exists('scriptlog_page_cache_early_dependencies_ready')) {

    /**
     * Verify the functions the early path calls are actually loaded.
     *
     * A missing function here means the curated utility list drifted (or a
     * deploy is incomplete). Returning false sends the request through the
     * full bootstrap, which loads everything, so the failure is a lost
     * optimisation rather than a broken page.
     *
     * @category Bootstrap
     * @return bool
     */
    function scriptlog_page_cache_early_dependencies_ready()
    {
        $required = array(
            'app_setting',
            'generate_form_token',
            'get_ip_address',
            'is_ssl',
            'page_cache_exists',
            'page_cache_serve',
            'resolve_session_save_path',
            'scriptlog_define_csp_nonce',
            'scriptlog_send_security_headers',
            'set_session_cookies_key',
            'start_session_on_site',
        );

        foreach ($required as $function) {
            if (!function_exists($function)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('scriptlog_serve_page_cache_early')) {

    /**
     * Serve a fresh page-cache hit before the full bootstrap.
     *
     * On success the cached HTML is echoed and the process exits (via
     * page_cache_serve()), so lib/main.php never runs the framework. On any
     * failure the function returns false and the caller continues with the
     * normal bootstrap.
     *
     * @category Bootstrap
     * @param string $appRoot Application root with a trailing directory separator
     * @return bool False when the request must fall through to the full bootstrap
     */
    function scriptlog_serve_page_cache_early($appRoot)
    {
        if (!scriptlog_page_cache_early_applies()) {
            return false;
        }

        $appRoot = rtrim((string)$appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // The session handler and settings helpers are namespaced classes that
        // lib/main.php's Composer + alias-map preamble already made loadable.
        if (!class_exists('Scriptlog\\Core\\SessionMaker')) {
            return false;
        }

        $utilityDirectory = $appRoot . 'lib' . DIRECTORY_SEPARATOR . 'utility' . DIRECTORY_SEPARATOR;

        foreach (scriptlog_page_cache_early_utilities() as $utilityFile) {
            if (is_file($utilityDirectory . $utilityFile)) {
                require_once $utilityDirectory . $utilityFile;
            }
        }

        if (!scriptlog_page_cache_early_dependencies_ready()) {
            return false;
        }

        $sessionStarted = false;

        try {
            $configFile = $appRoot . 'config.php';

            if (!is_file($configFile)) {
                return false;
            }

            // $_ENV is already populated by lib/main.php's Dotenv step, so the
            // session key derived from APP_EMAIL/APP_KEY matches the full path.
            $config = require $configFile;

            if (!is_array($config)) {
                return false;
            }

            $appUrl = isset($config['app']['url']) ? (string)$config['app']['url'] : '';
            $appEmail = isset($config['app']['email']) ? (string)$config['app']['email'] : '';
            $appKey = isset($config['app']['key']) ? (string)$config['app']['key'] : '';

            // Bootstrap::loadConfiguration() throws on a missing APP_KEY; let
            // the full path produce that error instead of guessing here.
            if ($appKey === '') {
                return false;
            }

            // Cheap pre-session check. A cache miss must not start a session:
            // Bootstrap::createSession() owns that, and starting it here would
            // make its SessionMaker constructor warn about an active session.
            // page_cache_ttl() memoizes app_settings(), so the full bootstrap
            // reuses this read instead of issuing a second query.
            $cachePath = page_cache_path(page_cache_key());

            if (!is_file($cachePath) || (time() - filemtime($cachePath)) >= page_cache_ttl()) {
                return false;
            }

            // Mirror Bootstrap::createSession(): the cached markup carries a
            // per-session CSRF token, so the session must be the same encrypted
            // handler the full path would start.
            if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
                $sessionMaker = new \Scriptlog\Core\SessionMaker(
                    set_session_cookies_key($appEmail, $appKey)
                );

                // session_save_path() is called for its side effect (it sets
                // the session directory); the return value is the old path.
                /** @psalm-suppress UnusedFunctionCall */
                session_save_path(resolve_session_save_path());
                session_set_save_handler($sessionMaker, true);
                register_shutdown_function('session_write_close');
                start_session_on_site($sessionMaker);
                $sessionStarted = true;
            }

            // Reuse the existing freshness/anonymity rules. A session-only
            // login must fall through; release the session first so the full
            // bootstrap can initialise it without "session already active"
            // warnings.
            if (!page_cache_exists()) {
                if ($sessionStarted && session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }

                return false;
            }

            // Mirror Bootstrap::applySecurity()'s header half. Session is
            // started first, exactly as in the full bootstrap.
            scriptlog_define_csp_nonce();
            scriptlog_send_security_headers($appUrl);
        } catch (\Throwable $e) {
            // Any failure (DB down, unreadable config, session handler error)
            // is handled by the full bootstrap, which owns the error response.
            if ($sessionStarted && session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            error_log('[scriptlog][early-cache] falling back to full bootstrap: ' . $e->getMessage());

            return false;
        }

        page_cache_serve();

        // page_cache_serve() exits on a hit; reaching here means it did not.
        return false;
    }
}
