<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * ApiHelper
 *
 * Shared static helpers for the REST API layer.
 * Centralizes app URL resolution so that config.php is
 * read exactly once and cached via Registry.
 *
 * @category  Core Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 */
class ApiHelper
{
    /**
     * Get the application base URL.
     *
     * Reads from config.php and caches the result
     * in Registry under the key 'app_url' so that
     * every caller uses the same resolved value.
     *
     * @return string
     */
    public static function getAppUrl()
    {
        if (Registry::isKeySet('app_url')) {
            return Registry::get('app_url');
        }

        $config = [];
        if (file_exists(__DIR__ . '/../../config.php')) {
            $config = require __DIR__ . '/../../config.php';
        }

        $url = $config['app']['url'] ?? 'http://localhost';

        Registry::set('app_url', $url);

        return $url;
    }
}
