<?php
/**
 * Upload Theme Function
 * 
 * @param string $file_name
 * 
 */
function upload_theme($file_name, $file_location, array $blacklist)
{
  
  // get file basename
  $file_basename = substr($file_name, 0, strripos($file_name, '.'));
  // get file extension
  $file_extension = substr($file_name, strripos($file_name, '.'));
  // rename file
  $rename_file = rename_file(md5(rand(000,999).$file_basename));
  $slug = make_slug($file_basename);
  $fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;
  $pathFile = __DIR__ . '/../../'.APP_THEME.$fileNameUnique;

  foreach ($blacklist as $item) {
    if (preg_match("/$item\$/i", $file_name)) {
      scriptlog_error("Forbidden File Format");    
    }
  }

  if (move_uploaded_file($file_location, $pathFile)) {
    
    $zip = new ZipArchive();
    $x = $zip -> open($pathFile);

    if ($x === true) {
      $zip -> extractTo(APP_ROOT.'public/themes/');
      $zip -> close();

      unlink($pathFile);

    }

    return true;

  } else {

    scriptlog_error("There was problem with the upload. Please try again");

  }
  
}