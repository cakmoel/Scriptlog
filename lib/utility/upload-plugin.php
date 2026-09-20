<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * upload_plugin()
 *
 * uploading and extract plugin file with .zip extension
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see https://snipplr.com/view/69947/script-for-uploading-zip-file-and-unzip-it-on-the-server
 * @see https://bavotasan.com/2010/how-to-upload-zip-file-using-php/
 * @see https://code.tutsplus.com/tutorials/file-compression-and-extraction-in-php--cms-31977
 * @see https://www.php.net/manual/en/class.ziparchive.php#105312
 *
 * @param string $file_name
 * @param string $file_location
 *
 */
function upload_plugin($file_location, $file_name)
{

    // get file basename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));
    // get file extension
    $file_extension = file_extension_pathinfo($file_name);
    // rename file
    $rename_file = rename_file(md5(rand(000, 999) . $file_basename));

    $slug = make_slug($file_basename);

    $fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;

    $path = __DIR__ . '/../../' . APP_PLUGIN;

    $zip_path_uploaded = $path . basename($fileNameUnique);

    $plugin_path_uploaded = $path . current(explode(".", $file_name));

    (is_dir(__DIR__ . '/../../' . APP_PLUGIN . basename($file_name, '.zip'))) ?: remove_dir_recursive(__DIR__ . '/../../' . APP_PLUGIN . basename($file_name, '.zip'));

    create_directory($plugin_path_uploaded);

    if (!move_uploaded_file($file_location, $zip_path_uploaded)) {
        return false;
    }

    $extractionResult = open_plugin_uploaded($zip_path_uploaded, $plugin_path_uploaded);

    if ($extractionResult !== true) {
        return $extractionResult;
    }

    require_once __DIR__ . '/plugin-validator.php';

    $validation = validate_plugin_structure($plugin_path_uploaded);

    if (!$validation['valid']) {
        remove_dir_recursive($plugin_path_uploaded);
        throw new InvalidArgumentException(implode(', ', $validation['errors']));
    }

    return true;
}

/**
 * open_plugin_uploaded
 *
 * @category function
 * @see https://rules.sonarsource.com/php/type/Security%20Hotspot/RSPEC-5042
 * @param string $zip_path_uploaded
 * @param string $plugin_path
 *
 */
function open_plugin_uploaded($zip_path_uploaded, $plugin_path)
{

    // Defense-in-depth: skip entries matching the legacy dangerous-filename
    // blacklist. The real control is the canonical path validation performed by
    // safe_zip_extract(), which also closes the Windows-style "..\" gap.
    $blacklist = [
        '/(.*)(phpinfo|system|php_uname|chmod|fopen|eval|flclose|readfile|base64_decode|passthru)(.*)/Us'
    ];

    $result = safe_zip_extract($zip_path_uploaded, $plugin_path, $blacklist);

    if ($result === true) {
        @unlink($zip_path_uploaded);
    }

    return $result;
}
