<?php

/**
 * purify_dirty_html()
 *
 * clean and sanitize bad html code with HTMLPurifier
 *
 * @category function
 * @license MIT
 * @version 1.0
 * @see https://paragonie.com/blog/2015/06/preventing-xss-vulnerabilities-in-php-everything-you-need-know
 * @param string $dirty_html
 *
 */

function purify_dirty_html($dirty_html)
{
    // REM-7: lazy-load the purifier at the use site so pages that never
    // purify (static assets, shortcut 404s) skip the autoloader entirely.
    // The load is unconditional (but idempotent via
    // SCRIPTLOG_HTMLPURIFIER_LOADED): gating on class_exists() is wrong
    // because Composer's classmap can autoload HTMLPurifier_Config WITHOUT
    // defining HTMLPURIFIER_PREFIX (defined by the library's own
    // HTMLPurifier/Bootstrap.php, pulled in via HTMLPurifier.auto.php),
    // which fatals in ConfigSchema::makeFromSerial().
    if (function_exists('call_htmlpurifier')) {
        call_htmlpurifier();
    }

    static $purifier = null;

    if ($purifier === null) {
        $config = class_exists('HTMLPurifier_Config') ? HTMLPurifier_Config::createDefault() : "";

        if (is_object($config)) {
            // S5: keep the definition cache between requests. The default
            // Serializer backend rebuilds (and E2 shows a cold rebuild on
            // every admin login) unless pointed at a writable permanent
            // folder. The folder is blocked from the web (see .htaccess
            // below) since cached definitions can leak page content.
            $cacheDir = defined('APP_ROOT')
                ? APP_ROOT . 'public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'htmlpurifier' . DIRECTORY_SEPARATOR
                : "";

            if ($cacheDir !== "" && (!is_dir($cacheDir) || is_writable($cacheDir))) {
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }

                $htaccess = $cacheDir . '.htaccess';
                if (is_dir($cacheDir) && !file_exists($htaccess)) {
                    @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
                }

                if (is_dir($cacheDir) && is_writable($cacheDir)) {
                    $config->set('Cache.DefinitionImpl', 'Serializer');
                    $config->set('Cache.SerializerPath', $cacheDir);
                }
            }
        }

        $purifier = (class_exists('HTMLPurifier') && is_object($config)) ? new HTMLPurifier($config) : "";
    }

    if (!is_object($purifier)) {
        return $dirty_html;
    }

    return $purifier->purify($dirty_html);
}
