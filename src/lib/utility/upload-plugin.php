<?php
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
$rename_file = rename_file(md5(rand(000,999).$file_basename));

$slug = make_slug($file_basename);

$fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;

$path = __DIR__ . '/../../' . APP_PLUGIN;

$zip_path_uploaded = $path.basename($fileNameUnique);

$plugin_path_uploaded = $path.current(explode(".",$file_name));

( is_dir(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip') ) ) ?: remove_dir_recursive(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip'));

create_directory($plugin_path_uploaded);

return ( move_uploaded_file($file_location, $zip_path_uploaded) ) ? open_plugin_uploaded($zip_path_uploaded) : false;

}

/**
 * open_plugin_uploaded
 *
 * @category function
 * @see https://rules.sonarsource.com/php/type/Security%20Hotspot/RSPEC-5042
 * @param string $zip_path_uploaded
 * 
 */
function open_plugin_uploaded($zip_path_uploaded)
{

 $file_count = 0;
 $total_size = 0;
 $zip = new ZipArchive();

 if ($zip->open($zip_path_uploaded) === true) {
  
    $file_count = (version_compare(phpversion(), "7.4.30", ">=")) ? $zip->count() : $zip->numFiles;

    for ($i = 0; $i < $file_count; $i++) {

        $file_index = $zip->getNameIndex($i);
        $stats = $zip->statIndex($i);

        // Preventing zip slip path traversal
        if (strpos($file_index, '../') !== false || substr($file_index, 0, 1) === '/') {
            throw new InvalidArgumentException();
        }

        if (substr($file_index, -1) !== '/') {

            $file_count++;
            if ($file_count > MAX_FILES) {
                throw new InvalidArgumentException();
            }

            $fp = $zip->getStream($file_index);
            $current_size = 0;
            while (!feof($fp)) {
                $current_size += READ_LENGTH;
                $total_size += READ_LENGTH;

                if ($total_size > MAX_SIZE) {
                    throw new InvalidArgumentException();
                }

                // Additional protection: checking compression ration
                if ($stats['comp_size'] > 0) {
                    $ratio = $current_size / $stats['com_size'];
                    if ($ratio > MAX_RATIO) {
                        throw new InvalidArgumentException();
                    }
                }

                file_put_contents($file_index, fread($fp, READ_LENGTH), FILE_APPEND);

            }

            fclose($fp);

        } else {

            mkdir($file_index);
        }

        preg_match('/(.*)(phpinfo|system|php_uname|chmod|fopen|eval|flclose|readfile|base64_decode|passthru)(.*)/Us', $file_index, $matches);

        if (count($matches) > 0) {

         $zip->deleteName($file_index);

        }

    }

    $zip->extractTo($zip_path_uploaded);

    $zip->close();

    unlink($zip_path_uploaded);

    return true;

} else {

    return false;

}

}