<?php

/**
 * upload_theme()
 *
 * uploading theme with zip format
 *
 * @category function
 * @param string $file_name
 *
 */
function upload_theme($file_name, $file_location, $blacklist)
{

    // get file basename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));
    // get file extension
    $file_extension = substr($file_name, strripos($file_name, '.'));
    
    // WHITELIST VALIDATION - More secure than blacklist
    $whitelist_extensions = ['.zip'];
    $whitelist_mime_types = [
        'application/zip',
        'application/x-zip-compressed',
        'multipart/x-zip',
        'application/x-compressed'
    ];
    $safe_filename_pattern = '/^[a-zA-Z0-9\-_\.]+$/';
    
    // 1. Whitelist extension check (case-insensitive)
    if (!in_array(strtolower($file_extension), $whitelist_extensions)) {
        scriptlog_error("Invalid file extension. Only .zip files are allowed.");
    }
    
    // 2. Whitelist filename pattern check
    if (!preg_match($safe_filename_pattern, $file_name)) {
        scriptlog_error("Invalid characters in filename. Only alphanumeric, hyphens, underscores, and dots are allowed.");
    }
    
    // 3. Check for null byte injection attempts
    if (strpos($file_name, "\0") !== false || strpos($file_basename, "\0") !== false) {
        scriptlog_error("Null byte injection detected.");
    }
    
    // 4. Check for path traversal in basename
    if (strpos($file_basename, '..') !== false || strpos($file_basename, '/') !== false || strpos($file_basename, '\\') !== false) {
        scriptlog_error("Path traversal attempt detected.");
    }
    
    // Generate unique filename for upload
    $rename_file = rename_file(md5(rand(000, 999) . $file_basename));
    $slug = make_slug($file_basename);
    $fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . '.' . $file_extension;
    $pathFile = __DIR__ . '/../../' . APP_THEME . $fileNameUnique;
    
    // Continue with blacklist for additional safety (defense in depth)
    foreach ($blacklist as $item) {
        if (preg_match("/$item\$/i", $file_name)) {
            scriptlog_error("Forbidden File Format");
        }
    }

    if (move_uploaded_file($file_location, $pathFile)) {
        $zip = new ZipArchive();
        $x = $zip->open($pathFile);

        if ($x === true) {
            $zip->extractTo(APP_ROOT . 'public/themes/');
            $zip->close();

            // Fix theme.ini permissions after extraction if exists
            $theme_dir = APP_ROOT . 'public/themes/' . $file_basename;
            $ini_file = $theme_dir . '/theme.ini';
            if (file_exists($ini_file)) {
                chmod($ini_file, 0644);
            }

            unlink($pathFile);
        }

        return true;
    } else {
        scriptlog_error("There was problem with the upload. Please try again");
    }
}
