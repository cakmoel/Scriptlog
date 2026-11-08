<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * plugin_manifest_relative_files()
 *
 * Collect every file covered by a plugin manifest: `plugin.ini` plus all
 * `*.php` files below the plugin directory. Paths are returned relative to the
 * plugin directory, normalised to forward slashes and sorted byte-wise so the
 * resulting hash is deterministic across filesystems and directory read order.
 *
 * Symbolic links are skipped: a manifest must only cover files that actually
 * live inside the plugin directory.
 *
 * @category  function
 * @author    Scriptlog Team
 * @license   MIT
 * @version   1.0.0
 * @since     1.10.0
 * @param string $pluginDirectory Absolute path to the plugin directory
 * @return array|false Sorted relative paths, or false when the directory is unusable
 */
function plugin_manifest_relative_files($pluginDirectory)
{
    if (!is_string($pluginDirectory) || $pluginDirectory === '') {
        return false;
    }

    $directory = rtrim($pluginDirectory, '/\\');

    if (!is_dir($directory) || !is_readable($directory)) {
        return false;
    }

    if (!is_file($directory . DIRECTORY_SEPARATOR . 'plugin.ini')) {
        return false;
    }

    $files = array();

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            $basename = $file->getBasename();

            if ($extension !== 'php' && $basename !== 'plugin.ini') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1);
            $files[] = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        }
    } catch (\Throwable $th) {
        return false;
    }

    sort($files, SORT_STRING);

    return $files;
}

/**
 * plugin_manifest_hash()
 *
 * Compute the lowercase hex SHA-256 digest of a plugin's loadable code:
 * every PHP file plus `plugin.ini`, keyed by relative path and content.
 *
 * The digest is what gets persisted in `tbl_plugin.plugin_manifest` at install
 * time and re-computed before activation, so any later edit, addition or
 * removal of plugin code invalidates it.
 *
 * @category  function
 * @author    Scriptlog Team
 * @license   MIT
 * @version   1.0.0
 * @since     1.10.0
 * @param string $pluginDirectory Absolute path to the plugin directory
 * @return string|false 64-character hex digest, or false when it cannot be computed
 */
function plugin_manifest_hash($pluginDirectory)
{
    $files = plugin_manifest_relative_files($pluginDirectory);

    if ($files === false || empty($files)) {
        return false;
    }

    $directory = rtrim($pluginDirectory, '/\\');

    $context = hash_init('sha256');

    foreach ($files as $relative) {
        $absolute = $directory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (!is_readable($absolute)) {
            return false;
        }

        $contents = @file_get_contents($absolute);

        if ($contents === false) {
            return false;
        }

        hash_update($context, $relative);
        hash_update($context, "\0");
        hash_update($context, (string)strlen($contents));
        hash_update($context, "\0");
        hash_update($context, $contents);
        hash_update($context, "\0");
    }

    return hash_final($context);
}

/**
 * plugin_manifest_is_hash()
 *
 * @category  function
 * @author    Scriptlog Team
 * @license   MIT
 * @version   1.0.0
 * @since     1.10.0
 * @param mixed $value Candidate manifest value
 * @return bool
 */
function plugin_manifest_is_hash($value)
{
    return is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) === 1;
}

/**
 * plugin_manifest_verify()
 *
 * Confirm that the plugin directory on disk still matches the digest recorded
 * at install time. Fails closed: a missing, malformed or unreadable manifest
 * never verifies.
 *
 * @category  function
 * @author    Scriptlog Team
 * @license   MIT
 * @version   1.0.0
 * @since     1.10.0
 * @param string $pluginDirectory Absolute path to the plugin directory
 * @param mixed  $expectedHash   Manifest stored in tbl_plugin.plugin_manifest
 * @return bool
 */
function plugin_manifest_verify($pluginDirectory, $expectedHash)
{
    $expected = is_string($expectedHash) ? strtolower(trim($expectedHash)) : '';

    if (!plugin_manifest_is_hash($expected)) {
        return false;
    }

    $actual = plugin_manifest_hash($pluginDirectory);

    if ($actual === false) {
        return false;
    }

    return hash_equals($expected, $actual);
}
