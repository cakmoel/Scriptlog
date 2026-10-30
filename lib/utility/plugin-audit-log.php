<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * plugin_audit_log()
 *
 * Append a JSON audit entry for plugin actions. Writes outside the web root
 * when possible (storage/logs/).
 *
 * @category function
 * @author   Scriptlog Team
 * @license  MIT
 * @version  1.0.0
 * @since    1.10.0
 * @param string      $action  Machine-readable action name
 * @param array       $context Structured context (plugin id/name, manifest, ...)
 * @param string|null $logDir  Optional explicit destination directory
 * @return bool
 */
function plugin_audit_log($action, array $context = array(), $logDir = null)
{
    if (!is_string($action) || $action === '') {
        return false;
    }

    $userId = null;
    if (function_exists('get_current_user_id')) {
        $userId = get_current_user_id();
    } elseif (isset($_SESSION['scriptlog_session_login_id'])) {
        $userId = $_SESSION['scriptlog_session_login_id'];
    }

    $ip = null;
    if (function_exists('get_ip_address')) {
        $ip = get_ip_address();
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $entry = array(
        'timestamp' => date('c'),
        'action' => $action,
        'user_id' => $userId,
        'ip' => $ip,
        'context' => $context,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null
    );

    $logDirs = array();

    if (is_string($logDir) && $logDir !== '') {
        $logDirs[] = $logDir;
    }

    if (defined('APP_ROOT')) {
        $logDirs[] = APP_ROOT . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        $logDirs[] = APP_ROOT . 'public' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
    }
    $logDirs[] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;

    $written = false;
    foreach ($logDirs as $logDir) {
        $logDir = rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (@mkdir($logDir, 0755, true) || is_dir($logDir)) {
            $logFile = $logDir . 'plugin-audit-' . date('Y-m-d') . '.jsonl';
            $line = json_encode($entry) . PHP_EOL;
            if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) !== false) {
                $written = true;
                break;
            }
        }
    }

    return $written;
}
