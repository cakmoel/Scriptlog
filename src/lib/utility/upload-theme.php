<?php
/**
 * Upload Theme Function
 * 
 * @param string $file_name
 * 
 */
function upload_theme($file_name, $file_location, $max_filesize, array $blacklist)
{
  $file_type = (isset($_FILES['zip_file']['type']) ? $_FILES['zip_file']['type'] : null) ;
  $file_size = (isset($_FILES['zip_file']['size']) ? $_FILES['zip_file']['size'] : null);
  $file_error = (isset($_FILES['zip_file']['error']) ? $_FILES['zip_file']['error'] : null);

  // get file basename
  $file_basename = substr($file_name, 0, strripos($file_name, '.'));
  // get file extension
  $file_extension = substr($file_name, strripos($file_name, '.'));
  // rename file
  $rename_file = rename_file(md5(rand(000,999).$file_basename));
  $slug = makeSlug($file_basename);
  $fileNameUnique = $slug . "-" . $rename_file . "-scriptlog" . $file_extension;
  $pathFile = '../public/themes/'.$fileNameUnique;

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $file_content = file_get_contents($file_location);
  $mime_type = $finfo -> buffer($file_content);

  $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
  
  if (!isset($file_error) || is_array($file_error)) {
     scriptlog_error('Invalid parameters');
  }

  switch ($file_error) {

    case UPLOAD_ERR_OK:
      
      break;
    
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
          scriptlog_error('Exceeded Filesize limit');
      default:
          scriptlog_error('Unknown Errors');

  }

  if ($file_size > $max_filesize) {
    scriptlog_error('Exceeded filesize limit.Maximum file size: '.format_size_unit(10485760));
  }

  $ext = array_search($mime_type, $accepted_types, true);
  if (!$ext) {
    scriptlog_error("Invalid file format");
  }

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