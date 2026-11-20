<?php

// Allow inclusion from common.php which defines SCRIPTLOG later
if (!defined('SCRIPTLOG')) {
    // Define a temporary SCRIPTLOG if not defined (for bootstrap)
    if (!defined('SCRIPTLOG_BOOTSTRAP')) {
        define('SCRIPTLOG_BOOTSTRAP', true);
    }
} else {
    // Normal case
}

/**
 * scriptlog_app_is_development()
 *
 * Resolve whether the application is running in development mode from
 * environment variables, with a safe default of false.
 *
 * Precedence (highest to lowest):
 * - APP_DEVELOPMENT (explicit boolean-like string/value)
 * - APP_DEBUG
 * - APP_ENV (development/debug/local -> true, production/empty -> false)
 * - Fallback: read APP_DEVELOPMENT/APP_DEBUG/APP_ENV from .env if present
 *
 * This function never emits output, never requires vendor autoload, and
 * treats unreadable files as false. It is safe to call from lib/common.php
 * before Dotenv has been loaded.
 *
 * @category function
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    1.10.0
 * @return bool
 */
function scriptlog_app_is_development()
{
    static $resolved = null;

    if (defined('SCRIPTLOG_TEST_MODE') && SCRIPTLOG_TEST_MODE) {
        // Don't cache in test mode to allow tests to change env
        $resolved = null;
    }

    if ($resolved !== null) {
        return $resolved;
    }

    if (defined('SCRIPTLOG_TEST_MODE') && SCRIPTLOG_TEST_MODE) {
        // continue to resolve
    }

    // 1) Explicit APP_DEVELOPMENT from environment sources
    $appDevelopment = scriptlog_env_get('APP_DEVELOPMENT');
    if ($appDevelopment !== null && $appDevelopment !== '') {
        $resolved = scriptlog_is_truthy_environment_value($appDevelopment);
        return $resolved;
    }

    // 2) APP_DEBUG
    $appDebug = scriptlog_env_get('APP_DEBUG');
    if ($appDebug !== null && $appDebug !== '') {
        $resolved = scriptlog_is_truthy_environment_value($appDebug);
        return $resolved;
    }

    // 3) APP_ENV
    $appEnv = scriptlog_env_get('APP_ENV');
    if (is_string($appEnv) && $appEnv !== '') {
        $normalized = strtolower(trim($appEnv));
        if (in_array($normalized, array('production', 'prod'), true)) {
            $resolved = false;
            return $resolved;
        }
        if (in_array($normalized, array('development', 'dev', 'local', 'testing', 'test'), true)) {
            $resolved = true;
            return $resolved;
        }
        // Unknown env value defaults to non-development for safety
        $resolved = false;
        return $resolved;
    }

    // 4) Fallback: read .env directly if not yet loaded
    $envPath = scriptlog_resolve_env_path();
    if ($envPath !== '' && is_readable($envPath)) {
        $contents = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($contents)) {
            foreach ($contents as $line) {
                $lineTrimmed = trim($line);
                if ($lineTrimmed === '' || strpos($lineTrimmed, '#') === 0) {
                    continue;
                }
                if (strpos($lineTrimmed, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $lineTrimmed, 2);
                $keyTrimmed = trim($key);
                $valueTrimmed = trim($value);
                // Strip surrounding quotes
                if ((strlen($valueTrimmed) >= 2) && (($valueTrimmed[0] === '"' && substr($valueTrimmed, -1) === '"') || ($valueTrimmed[0] === "'" && substr($valueTrimmed, -1) === "'"))) {
                    $valueTrimmed = substr($valueTrimmed, 1, -1);
                }

                if ($keyTrimmed === 'APP_DEVELOPMENT') {
                    $resolved = scriptlog_is_truthy_environment_value($valueTrimmed);
                    return $resolved;
                }
                if ($keyTrimmed === 'APP_DEBUG' && $appDebug === null) {
                    $appDebug = $valueTrimmed;
                    // continue to check if we already have APP_DEVELOPMENT; else store
                }
                if ($keyTrimmed === 'APP_ENV' && $appEnv === null) {
                    $appEnv = $valueTrimmed;
                }
            }
            // Re-check after scanning
            if (isset($appDebug) && is_string($appDebug) && $appDebug !== '') {
                $resolved = scriptlog_is_truthy_environment_value($appDebug);
                return $resolved;
            }
            if (isset($appEnv) && is_string($appEnv) && $appEnv !== '') {
                $normalized = strtolower(trim($appEnv));
                if (in_array($normalized, array('production', 'prod'), true)) {
                    $resolved = false;
                    return $resolved;
                }
                if (in_array($normalized, array('development', 'dev', 'local', 'testing', 'test'), true)) {
                    $resolved = true;
                    return $resolved;
                }
                $resolved = false;
                return $resolved;
            }
        }
    }

    // Safe default
    $resolved = false;
    return $resolved;
}

/**
 * scriptlog_env_get()
 *
 * @param string $key Environment variable name
 * @return string|null
 */
function scriptlog_env_get($key)
{
    if (!is_string($key) || $key === '') {
        return null;
    }

    if (function_exists('getenv')) {
        $value = getenv($key);
        if ($value !== false && $value !== null) {
            // getenv can return empty string; treat as empty
            if (is_string($value)) {
                return $value;
            }
        }
    }

    if (isset($_ENV[$key]) && is_string($_ENV[$key])) {
        return $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    return null;
}

/**
 * scriptlog_is_truthy_environment_value()
 *
 * @param mixed $value
 * @return bool
 */
function scriptlog_is_truthy_environment_value($value)
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value)) {
        return $value !== 0;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }
        if (in_array($normalized, array('1', 'true', 'on', 'yes', 'y', 'enabled'), true)) {
            return true;
        }
        if (in_array($normalized, array('0', 'false', 'off', 'no', 'n', 'disabled', ''), true)) {
            return false;
        }
        // Non-standard truthy string defaults to false for safety
        return false;
    }

    return false;
}

/**
 * scriptlog_resolve_env_path()
 *
 * @return string
 */
function scriptlog_resolve_env_path()
{
    if (defined('APP_ROOT')) {
        $candidate = APP_ROOT . '.env';
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    $baseDir = dirname(dirname(__DIR__));
    $candidate = $baseDir . DIRECTORY_SEPARATOR . '.env';
    if (is_file($candidate)) {
        return $candidate;
    }

    $cwd = getcwd();
    if (is_string($cwd)) {
        $candidate = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

/**
 * scriptlog_env_file_value()
 *
 * Read a single key from a Dotenv-style file without loading the whole file
 * into the process environment. This exists because lib/common.php runs before
 * Dotenv in lib/main.php, so the .env fallback must be read directly.
 *
 * Parsing rules (mirrors the subset of Dotenv syntax this project ships):
 * - blank lines and lines starting with "#" are ignored;
 * - the first occurrence of a key wins;
 * - a value wrapped in matching single or double quotes is unquoted;
 * - an unquoted " #" sequence starts an inline comment;
 * - unreadable or missing files resolve to null.
 *
 * @category function
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    1.13.0
 * @param string      $key     Environment variable name
 * @param string|null $envPath Optional explicit .env path
 * @return string|null Raw value, or null when the key/file is absent
 */
function scriptlog_env_file_value($key, $envPath = null)
{
    static $cache = array();

    if (!is_string($key) || $key === '') {
        return null;
    }

    if ($envPath === null || $envPath === '') {
        $envPath = scriptlog_resolve_env_path();
    }

    if (!is_string($envPath) || $envPath === '' || !is_readable($envPath)) {
        return null;
    }

    // Never cache in test mode so a test can rewrite the same path.
    $useCache = !(defined('SCRIPTLOG_TEST_MODE') && SCRIPTLOG_TEST_MODE);

    if ($useCache && isset($cache[$envPath])) {
        return array_key_exists($key, $cache[$envPath]) ? $cache[$envPath][$key] : null;
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!is_array($lines)) {
        return null;
    }

    $parsed = array();

    foreach ($lines as $line) {
        $lineTrimmed = trim($line);

        if ($lineTrimmed === '' || strpos($lineTrimmed, '#') === 0) {
            continue;
        }

        $separator = strpos($lineTrimmed, '=');

        if ($separator === false) {
            continue;
        }

        $lineKey = trim(substr($lineTrimmed, 0, $separator));

        if ($lineKey === '' || array_key_exists($lineKey, $parsed)) {
            continue;
        }

        $value = trim((string) substr($lineTrimmed, $separator + 1));
        $quote = substr($value, 0, 1);

        if (($quote === '"' || $quote === "'") && strlen($value) >= 2) {
            $closing = strpos($value, $quote, 1);

            if ($closing !== false) {
                $value = (string) substr($value, 1, $closing - 1);
            }
        } else {
            $comment = strpos($value, ' #');

            if ($comment !== false) {
                $value = rtrim((string) substr($value, 0, $comment));
            }
        }

        $parsed[$lineKey] = $value;
    }

    if ($useCache) {
        $cache[$envPath] = $parsed;
    }

    return array_key_exists($key, $parsed) ? $parsed[$key] : null;
}

/**
 * scriptlog_php_security_is_strict()
 *
 * Resolve whether the PHP runtime security guard must fail closed. The switch
 * is opt-in and fail-open: only an explicit truthy value enables it.
 *
 * Precedence (highest to lowest):
 * - process environment / $_ENV / $_SERVER (via scriptlog_env_get());
 * - the project .env file, read directly because lib/common.php runs before
 *   Dotenv in lib/main.php;
 * - fallback: false.
 *
 * @category function
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    1.13.0
 * @param string|null $envPath Optional explicit .env path
 * @return bool True when SCRIPTLOG_STRICT_PHP_SECURITY is truthy
 */
function scriptlog_php_security_is_strict($envPath = null)
{
    $value = scriptlog_env_get('SCRIPTLOG_STRICT_PHP_SECURITY');

    if ($value === null || $value === '') {
        $fileValue = scriptlog_env_file_value('SCRIPTLOG_STRICT_PHP_SECURITY', $envPath);

        if ($fileValue !== null) {
            $value = $fileValue;
        }
    }

    if ($value === null) {
        return false;
    }

    return scriptlog_is_truthy_environment_value($value);
}

/**
 * scriptlog_apply_error_display_policy()
 *
 * Enforce the production error-display policy. A development install may
 * render PHP diagnostics; every other install must hide them from the client
 * even when php.ini has `display_errors=On`. Errors are still written to the
 * server log (`log_errors=1`) for operators.
 *
 * APP_DEVELOPMENT is resolved in lib/common.php from APP_ENV / APP_DEBUG /
 * APP_DEVELOPMENT. Set `APP_ENV=local` (or `APP_DEBUG=true`) locally to keep
 * diagnostics visible.
 *
 * @category function
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    1.13.0
 * @param bool|null $isDevelopment Override for tests; null resolves APP_DEVELOPMENT
 * @return bool True when display_errors is left enabled
 */
function scriptlog_apply_error_display_policy($isDevelopment = null)
{
    if ($isDevelopment === null) {
        $isDevelopment = defined('APP_DEVELOPMENT') ? (bool) APP_DEVELOPMENT : false;
    }

    if ($isDevelopment) {
        return true;
    }

    // ini_set() can be disabled by a host; never let a failure here break boot.
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    @ini_set('log_errors', '1');

    return false;
}

/**
 * scriptlog_scrub_server_secrets()
 *
 * Remove credential-bearing keys from $_SERVER without touching $_ENV.
 *
 * @param array|null $keys Optional list of keys to scrub
 * @return void
 */
function scriptlog_scrub_server_secrets($keys = null)
{
    if ($keys === null) {
        $keys = array(
            'DB_PASS',
            'DB_PASSWORD',
            'DB_USER',
            'DB_USERNAME',
            'DB_HOST',
            'DB_NAME',
            'DB_DATABASE',
            'DB_PREFIX',
            'APP_KEY',
            'DEFUSE_KEY_PATH',
            'MAIL_PASSWORD',
            'SMTP_PASSWORD',
            'MAILER_PASSWORD'
        );
    }

    foreach ($keys as $key) {
        if (is_string($key) && isset($_SERVER[$key])) {
            unset($_SERVER[$key]);
        }
        // Also scrub prefixed variants commonly injected
        if (is_string($key)) {
            // Remove keys that match *_SECRET/*_KEY/*_TOKEN patterns if requested
        }
    }

    // Scrub any keys matching secret patterns
    foreach (array_keys($_SERVER) as $serverKey) {
        if (!is_string($serverKey)) {
            continue;
        }
        $upper = strtoupper($serverKey);
        if (strpos($upper, 'DB_PASS') !== false || strpos($upper, 'DB_PASSWORD') !== false) {
            unset($_SERVER[$serverKey]);
            continue;
        }
        if ($upper === 'APP_KEY' || $upper === 'DEFUSE_KEY_PATH') {
            unset($_SERVER[$serverKey]);
            continue;
        }
        if (strpos($upper, 'SECRET') !== false || strpos($upper, '_TOKEN') !== false || (strpos($upper, '_KEY') !== false && $upper !== 'PUBLIC_KEY')) {
            if (strpos($upper, 'PASSWORD') !== false || strpos($upper, 'PASS') !== false || $upper === 'APP_KEY' || strpos($upper, 'SECRET') !== false) {
                unset($_SERVER[$serverKey]);
            }
        }
    }
}
