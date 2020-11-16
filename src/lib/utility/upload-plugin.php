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
 
$plugin_extracted = false;

// get file basename
$file_basename = substr($file_name, 0, strripos($file_name, '.'));
// get file extension
$file_extension = file_extension_pathinfo($file_name);
// rename file
$rename_file = rename_file(md5(rand(000,999).$file_basename));

$slug = make_slug($file_basename);

$fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;

$path = __DIR__ . '/../../' . APP_PLUGIN;

$zip_path_uploaded = $path.$fileNameUnique;

$plugin_path_uploaded = $path.current(explode(".",$file_name));

if (is_dir(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip'))) remove_dir_recursive(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip'));

create_directory($plugin_path_uploaded);

if (move_uploaded_file($file_location, $zip_path_uploaded)) {

    $zip = new ZipArchive();

    $opened = $zip->open($zip_path_uploaded);

    if ( $opened === true) {

      $file_count = (version_compare(phpversion(), "7.2.0", ">=")) ? $zip->count() : $zip->numFiles;

      for ($i = 0; $i < $file_count; $i++) {

          $file_index = $zip->getNameIndex($i);

          preg_match('/(.*)(phpinfo|system|php_uname|chmod|fopen|eval|flclose|readfile|base64_decode|passthru)(.*)/Us', $file_index, $matches);

          if (count($matches) > 0) {

             $zip->deleteName($file_index);

          }

       }

      $zip->extractTo($plugin_path_uploaded);

      $zip->close();

      unlink($zip_path_uploaded);

      $plugin_extracted = true;

    } else {

       $plugin_extracted = false;

    }
    
} else {

    $plugin_extracted = false;

}

return $plugin_extracted;

}