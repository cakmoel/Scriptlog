<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * dotenv_cache_load
 *
 * S1: load `.env` once into a plain PHP cache file and rebuild it only when
 * `.env` itself changes (checked by file date). The warm path is a single
 * `require` of a flat array plus in-memory assignment, bringing the 12.3
 * percent Dotenv + Parser cost from E1 under 1 percent.
 *
 * Replay honors Dotenv immutable semantics: cached values never override
 * variables already present in the environment.
 *
 * @param string   $envFile   Absolute path to the `.env` file.
 * @param string   $cacheFile Absolute path to the generated PHP cache file.
 * @param callable $loader    Cold-path loader (runs Dotenv and populates $_ENV).
 * @return void
 */
function dotenv_cache_load($envFile, $cacheFile, $loader)
{
    if (!is_string($envFile) || !is_string($cacheFile) || !file_exists($envFile)) {
        return;
    }

    if (is_string($cacheFile) && file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($envFile)) {
        $vars = require $cacheFile;
        if (is_array($vars)) {
            foreach ($vars as $name => $value) {
                if (!is_string($name)) {
                    continue;
                }
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                }
                if (!array_key_exists($name, $_SERVER)) {
                    $_SERVER[$name] = $value;
                }
                if (getenv($name) === false && (is_string($value) || is_numeric($value))) {
                    @putenv($name . '=' . $value);
                }
            }
        }
        return;
    }

    $before = is_array($_ENV) ? $_ENV : array();
    call_user_func($loader);
    $after = is_array($_ENV) ? $_ENV : array();

    $delta = array();
    foreach ($after as $name => $value) {
        if (!array_key_exists($name, $before) || $before[$name] !== $value) {
            $delta[$name] = $value;
        }
    }

    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    if (is_dir($cacheDir) && is_writable($cacheDir)) {
        $htaccess = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
        }

        @file_put_contents(
            $cacheFile,
            "<?php\ndefined('SCRIPTLOG') || die(\"Direct access not permitted\");\nreturn " . var_export($delta, true) . ";\n",
            LOCK_EX
        );
    }
}

/**
 * dotenv_cache_paths
 *
 * Default `.env` / cache file locations for the application root.
 *
 * @param string $appRoot Application root with trailing directory separator.
 * @return array Tuple of (env file, cache file).
 */
function dotenv_cache_paths($appRoot)
{
    $envFile = rtrim($appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';
    $cacheFile = rtrim($appRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . 'public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR
        . 'cache' . DIRECTORY_SEPARATOR . 'dotenv' . DIRECTORY_SEPARATOR . 'env.cache.php';

    return array($envFile, $cacheFile);
}
