<?php
/**
 * photo_instance()
 * 
 * if fileinfo enabled then Intervention Image works properly else will implement non-secure approach
 * 
 * @category function
 * @author  Oliver Vogel the author of Intervention Image 
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see http://image.intervention.io/
 * @see https://anchetawern.github.io/blog/2016/02/18/using-the-intervention-image-library-in-php/
 * @see https://www.tutmecode.com/php/create-thumbnail-from-big-size-image-in-php-or-laravel/
 * 
 */
use Intervention\Image\ImageManager;

function photo_instance()
{
  // Currently you can choose between gd and imagick
  $manager = new ImageManager(['driver' => 'gd']);

  return $manager;

}

/**
 * upload_photo()
 * 
 * uploading picture
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $file_location
 * @param string $file_size
 * @param string $file_type
 * @param string $file_name
 * @return void
 * 
 */
function upload_photo($file_location, $file_size, $file_type, $file_name)
{

$small_size = 320;
$medium_size = 640;
$large_size = 770; 
$temp_src = $file_location;
$img_ext = get_file_extension($file_name);
$img_name = pathinfo($file_name, PATHINFO_BASENAME);
$img_source = null;

switch (strtolower($file_type)) {

  case 'image/png':
    
    $img_source = imagecreatefrompng($file_location);
    imagepalettetotruecolor($img_source);
    imagealphablending($img_source, true);
    imagesavealpha($img_source, true);

    break;
  
  case 'image/gif':

    $img_source = imagecreatefromgif($file_location);

    break;
  
  case 'image/jpeg':
  case 'image/pjpeg':
  case 'image/jpg':
  
    $img_source = imagecreatefromjpeg($file_location);
    
    break;

  case 'image/webp':

    $img_source = imagecreatefromwebp($file_location);

    break;
  
  case 'image/bmp':

    $img_source = imagecreatefrombmp($file_location);

    break;

  default:
    
    scriptlog_error("Unsupported File!");

    break;

}

list($current_width, $current_height) = getimagesize($temp_src);

// construct new name
$large_thumb_name = 'large_'.$img_name;
$medium_thumb_name = 'medium_'.$img_name;
$small_thumb_name = 'small_'.$img_name;

// picture directory
$origin_path = __DIR__ . '/../../'.APP_IMAGE;
$origin_path_uploaded = $origin_path . $file_name;

$small_path = __DIR__ . '/../../'.APP_IMAGE_SMALL;
create_directory($small_path);
$small_path_uploaded = $small_path . $small_thumb_name;

$medium_path = __DIR__ . '/../../'.APP_IMAGE_MEDIUM;
create_directory($medium_path);
$medium_path_uploaded = $medium_path. $medium_thumb_name;

$large_path = __DIR__ . '/../../'.APP_IMAGE_LARGE;
create_directory($large_path);
$large_path_uploaded = $large_path . $large_thumb_name;

if (!(extension_loaded('fileinfo') || function_exists('finfo_open') || class_exists('finfo'))) {

  if (resize_image($current_width, $current_height, $medium_size, $medium_path_uploaded, $img_source, 80, $file_type)) {

    if (!crop_image($current_width, $current_height, $small_size, $small_path_uploaded, $img_source, 80, $file_type)) {

        scriptlog_error("Error Creating small size of thumbnail!");

    }
    
    if (! move_uploaded_file($temp_src, $origin_path_uploaded)) {

      scriptlog_error("Error uploading picture");
      
    } 

    // creating large size thumbnail
    $large_size_thumb = new Resize($origin_path_uploaded);
    $large_size_thumb->resizeImage($large_size, 400, "crop");
    $large_size_thumb->saveImage($large_path_uploaded, 80);

  }

} else {

  if(($img_ext == "jpeg" || $img_ext == "jpg" || $img_ext == "png" || $img_ext == "gif") ) {

    if (false === set_webp_origin($current_width, $current_height, $file_location, $file_size, $origin_path_uploaded, $origin_path, $file_name)) {

      scriptlog_error("Error creating origin size of webp image format", E_USER_WARNING);
       
    }

    if(false === set_webp_regular($current_width, $current_height, $origin_path_uploaded, $large_path, $file_name)) {
  
       scriptlog_error("Error creating regular size of webp image format", E_USER_WARNING);
  
    }
  
    if(false === set_webp_medium($current_width, $current_height, $origin_path_uploaded, $medium_path, $file_name)) {
  
       scriptlog_error("Error creating medium size of webp image format", E_USER_WARNING);
  
    }
  
    if(false === set_webp_small($current_width, $current_height, $origin_path_uploaded, $small_path, $file_name)) {
  
       scriptlog_error("Error creating small of webp image format", E_USER_WARNING);
  
    }
  
  } 

}

 // save origin picture
 if (false === set_origin_photo( $current_width, $current_height, $file_location, $file_size, $origin_path_uploaded) ) {

  scriptlog_error("Error uploading picture", E_USER_WARNING);
 
 }

 // crop to regular size
if (false === set_regular_photo($current_width, $current_height, $origin_path_uploaded, $large_path, $file_name, $file_type)) {

 scriptlog_error("Error creating regular size of picture", E_USER_WARNING);

}

// crop to medium size
if( false === set_medium_photo($current_width, $current_height, $origin_path_uploaded, $medium_path, $file_name, $file_type) ) {

scriptlog_error("Error creating medium size of picture", E_USER_WARNING);

}

// crop to smaller size
if( false === set_small_photo($current_width, $current_height, $origin_path_uploaded, $small_path, $file_name, $file_type ) ) {

scriptlog_error("Error creating smaller size of picture", E_USER_WARNING);

}

}

/**
 * set_origin_photo
 *
 * @category Function
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_location
 * @param int|num $file_size
 * @param string $file_name
 * @param string $file_path_uploaded
 * @param string $file_type
 * @return void
 * 
 */
function set_origin_photo( $current_width, $current_height, $file_location, $file_size, $file_path_uploaded) {

if($current_width <= 0 || $current_height <= 0) {

  return false;

}

if(move_uploaded_file($file_location, $file_path_uploaded)) {

  if( filesize($file_path_uploaded) !== $file_size ) {

    unlink($file_path_uploaded);
  
  } 

}
 
}

/**
 * set_webp_origin
 * 
 * @category Function
 * @param int|numeric $current_width
 * @param int|numeric $current_height
 * @param string $file_location
 * @param string $file_size
 * @param string $origin_path_uploaded
 * @param string $origin_path
 * @param string $file_name
 * @return void
 * 
 */
function set_webp_origin($current_width, $current_height, $file_location, $file_size, $origin_path_uploaded, $origin_path, $file_name)
{

if($current_width <= 0 || $current_height <= 0) {

  return false;

}

if( !move_uploaded_file($file_location, $origin_path_uploaded) ) {

  return false;

}

if( filesize($origin_path_uploaded) !== $file_size ) {

  unlink($origin_path_uploaded);
  return false;

}

// get filename
$file_basename = substr($file_name, 0, strripos($file_name, '.'));
    
$origin_webp = photo_instance()->make($origin_path_uploaded);
if($origin_webp->save($origin_path.$file_basename.'.webp', 80, 'webp')){

   $origin_webp->destroy();
   return true;

}

}

/**
 * set_regular_photo
 *
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 * @param string $file_type
 * @return void
 * 
 */
function set_regular_photo($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type)
{

  $regular_size = 770;

  if ($current_width <= 0 || $current_height <= 0) {
  
      return false;
  
  }
  
  $regular_scaled = min($regular_size/$current_width, $regular_size/$current_height);
  $new_width = ceil($regular_scaled*$current_width);
  $new_height = ceil($regular_scaled*$current_height);
  
  $regular_photo = photo_instance()->make($file_path_uploaded);
  $regular_photo->fit($new_width, $new_height);
  
  switch ($file_type) {
    
    case "image/jpeg":
    case "image/jpg":
  
      if( $regular_photo->save($file_path_thumb .'large_'.$file_name, 80, 'jpg') ) {
  
        $regular_photo->destroy();
        return true;
     
      }
      
      break;
    
    case "image/png":
  
      if( $regular_photo->save($file_path_thumb .'large_'.$file_name, 80, 'png') ) {
  
        $regular_photo->destroy();
        return true;
     
      }
  
      break;
  
    case "image/gif":
  
      if( $regular_photo->save($file_path_thumb .'large_'.$file_name, 80, 'gif') ) {
  
        $regular_photo->destroy();
        return true;
     
      }

      break;
    
    case "image/webp":

      if( $regular_photo->save($file_path_thumb.'large_'.$file_name, 80, 'webp')) {

         $regular_photo->destroy();
         return true;

      }

     break;
  
    default:
      
      return false;
  
      break;
  
  }
  
}

/**
 * set_webp_regular
 *
 * @category Function
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 * @return void
 * 
 */
function set_webp_regular($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name )
{
  $regular_size = 770;

  if ($current_width <= 0 || $current_height <= 0) {
  
    return false;
  
  }
  
  // get filename
  $file_basename = substr($file_name, 0, strripos($file_name, '.'));
   
  $regular_scaled = min($regular_size/$current_width, $regular_size/$current_height);
  $new_width = ceil($regular_scaled*$current_width);
  $new_height = ceil($regular_scaled*$current_height);
  
  $regular_webp = photo_instance()->make($file_path_uploaded);
  $regular_webp->fit($new_width, $new_height);
  if($regular_webp->save($file_path_thumb.'large_'.$file_basename.'.webp', 80, 'webp')) {
  
     $regular_webp->destroy();
     return true;
  
  }

}

// setting medium size of picture
function set_medium_photo( $current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type )
{

$medium_size = 640;

if ($current_width <= 0 || $current_height <= 0) {

    return false;

}

$medium_scaled = min($medium_size/$current_width, $medium_size/$current_height);
$new_width = ceil($medium_scaled*$current_width);
$new_height = ceil($medium_scaled*$current_height);

$medium_photo = photo_instance()->make($file_path_uploaded);
$medium_photo->fit($new_width, $new_height);

switch ($file_type) {
  
  case "image/jpeg":
  case "image/jpg":

    if( $medium_photo->save($file_path_thumb .'medium_'.$file_name, 80, 'jpg') ) {

      $medium_photo->destroy();
      return true;
   
    }
    
    break;
  
  case "image/png":

    if( $medium_photo->save($file_path_thumb .'medium_'.$file_name, 80, 'png') ) {

      $medium_photo->destroy();
      return true;
   
   }

   break;

  case "image/gif":

    if( $medium_photo->save($file_path_thumb .'medium_'.$file_name, 80, 'gif') ) {

      $medium_photo->destroy();
      return true;
   
    }
  
   break;

  case "image/webp":

    if ($medium_photo->save($file_path_thumb.'medium_'.$file_name, 80, 'webp')) {

       $medium_photo->destroy();
       return true;

    }

    break;

  default:
    
    return false;

    break;

}


}

// setting medium size of webp image format
function set_webp_medium( $current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name )
{

$medium_size = 640;

if ($current_width <= 0 || $current_height <= 0) {

  return false;

}

// get filename
$file_basename = substr($file_name, 0, strripos($file_name, '.'));
 
$medium_scaled = min($medium_size/$current_width, $medium_size/$current_height);
$new_width = ceil($medium_scaled*$current_width);
$new_height = ceil($medium_scaled*$current_height);

$medium_webp = photo_instance()->make($file_path_uploaded);
$medium_webp->fit($new_width, $new_height);
if($medium_webp->save($file_path_thumb.'medium_'.$file_basename.'.webp', 80, 'webp')) {

   $medium_webp->destroy();
   return true;

}

}

// setting smaller size of picture
function set_small_photo( $current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type)
{

$small_size = 320;

if ($current_width <= 0 || $current_height <= 0) {

    return false;

}

$small_scaled = min($small_size/$current_width, $small_size/$current_height);
$new_width = ceil($small_scaled*$current_width);
$new_height = ceil($small_scaled*$current_height);

$small_photo = photo_instance()->make($file_path_uploaded);
$small_photo->fit($new_width, $new_height);

switch ($file_type) {

  case "image/jpeg":
  case "image/jpg":

    if( $small_photo->save($file_path_thumb.'small_'.$file_name, 80, 'jpg' ) ) {

      $small_photo->destroy();
      return true;
    
    }
    
    break;
  
  case "image/png":

    if( $small_photo->save($file_path_thumb.'small_'.$file_name, 80, 'png' ) ) {

      $small_photo->destroy();
      return true;
    
    }

    break;
  
  case "image/gif":

    if( $small_photo->save($file_path_thumb.'small_'.$file_name, 80, 'gif' ) ) {

      $small_photo->destroy();
      return true;
    
    }

    break;

  case "image/webp":

    if($small_photo->save($file_path_thumb.'small_'.$file_name, 80, 'webp')) {

      $small_photo->destroy();
      return true;

    }

    break;

  default:
    
    return false;

    break;

}

}

// setting smaller size of webp image format
function set_webp_small( $current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name )
{

$small_size = 320;

if ($current_width <= 0 || $current_height <= 0) {

  return false;

}

// get filename
$file_basename = substr($file_name, 0, strripos($file_name, '.'));
 
$small_scaled = min($small_size/$current_width, $small_size/$current_height);
$new_width = ceil($small_scaled*$current_width);
$new_height = ceil($small_scaled*$current_height);

$small_webp = photo_instance()->make($file_path_uploaded);
$small_webp->fit($new_width, $new_height);
if($small_webp->save($file_path_thumb.'small_'.$file_basename.'.webp', 80, 'webp')) {

  $small_webp->destroy();
  return true;

}

}