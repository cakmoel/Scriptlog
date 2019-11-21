<?php
/**
 * Example for the article at medium.com
 * Created by Igor Data.
 * User: igordata
 * Date: 2017-01-23
 * @link https://medium.com/@igordata/php-running-jpg-as-php-or-how-to-prevent-execution-of-user-uploaded-files-6ff021897389 Read the article
 */

/**
 * Check that path is really inside that folder, and return path if yes, and false if not.
 * @param String $path Path to check
 * @param String $folder Path to folder, where $path have to be in
 * @return bool|string False on fail, or $path on success
 *
 */
function check_path_in_folder($path, $folder) {
		if ($path === '' OR $path === null OR $path === false OR $folder === '' OR $folder === null OR $folder === false) {
			/* can't use empty() because it can be a string like "0", and it's valid path */
        return false;
    }
    $folderRealpath = realpath($folder);
    $pathRealpath = realpath($path);
    if ($pathRealpath === false OR $folderRealpath === false) {
        // Some of paths is empty
        return false;
    }
    $folderRealpath = rtrim($folderRealpath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $pathRealpath = rtrim($pathRealpath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strlen($pathRealpath) < strlen($folderRealpath)) {
        // File path is shorter that a folder path. This file can't be inside that folder.
        return false;
    }
    if (substr($pathRealpath, 0, strlen($folderRealpath)) !== $folderRealpath) {
        // Path to a folder of file is not equal to a path to a folder where it have to be located
        return false;
    }
    // OK
    return $path;
}
