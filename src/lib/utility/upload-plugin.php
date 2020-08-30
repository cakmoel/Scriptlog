<?php
/**
 * upload plugin function
 * extract plugin file with .zip extension
 * 
 * @param string $file_name
 * @param array $file_location
 */
function upload_plugin($file_name, $file_location, array $blacklist)
{
 
  // get file basename
  $file_basename = substr($file_name, 0, strripos($file_name, '.'));
  // get file extension
  $file_extension = substr($file_name, strripos($file_name, '.'));

  $rename_file = rename_file(sha1(rand(000000, 999999) . $file_basename));
  $slug = make_slug($file_basename);
  $fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;
  $pathFile = __DIR__ .'/../'.APP_LIBRARY . DS . 'plugins'. DS . $fileNameUnique;

  foreach ($blacklist as $item) {
    if (preg_match("/$item\$/i", $file_name)) {
      scriptlog_error("Forbidden File Format");    
    }
  }

  move_uploaded_file($file_location, $pathFile);

  if (file_exists( __DIR__ . '/../'.APP_LIBRARY.DS.'plugins'.DS.$fileNameUnique)) {

    $archive = new PclZip($pathFile);
    $archive -> extract(PCLZIP_OPT_PATH, __DIR__ . '/../'.APP_LIBRARY.DS.'plugins'.DS);
    unlink("../library/plugins/$fileNameUnique");

  }
  
}