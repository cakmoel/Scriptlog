<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * resolve_session_save_path()
 *
 * Resolve a writable session save path.
 * Checks SESSION_SAVE_PATH env var first, then falls back to
 * an app-specific directory within the project root.
 * Auto-creates the directory if it doesn't exist.
 *
 * @category Function
 * @author Blogware Team
 * @license MIT
 * @return string
 */
function resolve_session_save_path()
{
    $envPath = getenv('SESSION_SAVE_PATH');
    if ($envPath) {
        $envPath = rtrim($envPath, DIRECTORY_SEPARATOR);
        if (is_dir($envPath) && is_writable($envPath)) {
            return $envPath;
        }
        if (!is_dir($envPath)) {
            @mkdir($envPath, 0755, true);
            if (is_dir($envPath) && is_writable($envPath)) {
                return $envPath;
            }
        }
    }

    $path = defined('APP_ROOT')
        ? APP_ROOT . 'public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sessions'
        : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blogware_sessions';

    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }

    return $path;
}
