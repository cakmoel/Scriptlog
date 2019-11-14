<?php 
/**
 * ImageUploader Class
 *
 * @package   SCRIPTLOG/LIB/CORE/ImageUploader
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ImageUploader
{
 /**
  * File name
  * 
  * @var string
  */
 public $file_name;
 
 /**
  * File location
  * @var string
  */
 public $file_location;
 
 /**
  * File Type 
  * @var string
  */
 public $file_type;
 
 /**
  * File size
  *
  * @var string
  */
 public $file_size;
 
 /**
  * File error
  *
  * @var string
  */
 public $file_error;
 
 /**
  * Path destination
  * 
  * @var string
  */
 public $path_destination;
 
 /**
  * File basename
  *
  * @var string
  */
 private $file_basename;
 
 /**
  * File extension
  * @var string
  */
 private $file_extension;
 
 /**
  * Image source origin
  * @var string
  */
 private $image_source = null;
 
 /**
  * Maximum size uploaded
  * @var number | integer
  */
 private $max_size = 586000;
 
 /**
  * Image file type allowed 
  * @var array
  */
 private $image_permitted = array(
     'jpg' => 'image/jpeg', 
     'png' => 'image/png', 
     'gif' => 'image/gif'
 );
 
 /**
  * compress setinng
  * @var array
  */
 private $compress_setting = array(
     'directory' => APP_ROOT . APP_PUBLIC . DS . 'picture'. DS,
     'file_type' => array( // file format allowed
         'image/jpeg',
         'image/png',
         'image/gif'
     )
 );
 
 /**
  * Error message
  * @var string
  */
 private $error_message;
 
 /**
  * constructor
  * @param string $key
  * @param string $path
  * 
  */
 public function __construct($key, $path)
 {
   $this->file_location = $_FILES[$key]['tmp_name'];
   $this->file_type = $_FILES[$key]['type'];
   $this->file_name = $_FILES[$key]['name'];
   $this->file_size = $_FILES[$key]['size'];
   $this->file_error = $_FILES[$key]['error'];
   
   $this->path_destination = $path;
   
 }
 
 private function setFileBaseName($file_name)
 {
   $this->file_basename = substr($file_name, 0, strripos($file_name, '.'));
 }
 
 private function getFileBaseName()
 {
   return $this->file_basename;
 }
 
 private function setFileExtension($file_extension)
 {
   $this->file_extension = substr($file_extension, strripos($file_extension, '.'));
 }
 
 private function getFileExtension()
 {
   return $this->file_extension;
 }
  
 private function checkImageSize($file_name)
 {
     
  $size_conf = substr($this->max_size, -1);
  $max_conf = (int)substr($this->max_size, 0, -1);
  
  switch($size_conf){
      case 'k':
      case 'K':
          $max_size *= 1024;
          break;
      case 'm':
      case 'M':
          $max_size *= 1024;
          $max_size *= 1024;
          break;
      default:
          $max_size = 1024000;
  }
  
  if (filesize($file_name) > $max_size) {
      
      return false;
      
  } else {
      
      return true;
      
  }
  
 }
 
 private function checkImageMimeType($file_location) 
 {
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $file_contents = file_get_contents($this->file_location);
  $mime_type = $finfo -> buffer($file_contents);
  
  try {
      
      $ext = array_search($mime_type, $this->image_permitted, true);
      
      if (false === $ext) {
           
          throw new UploadException('Invalid file format');
      }
      
      return true;
      
  } catch (UploadException $e) {
      
      $this->error_message = LogError::newMessage($e);
      $this->error_message = LogError::customErrorMessage();
      
  }
 
 }

 private function readyToUpload()
 {
    
   $canUpload = true;

   try {

    if (!isset($this->file_error) || is_array($this->file_error)) {
        
        $canUpload = false;
        throw new UploadException('Invalid parameters');
        
    }
  
    $writableFolder = is_writable($this->path_destination);
    
    $tempName = is_uploaded_file($this->file_location);
    
    $maxSize = ini_get('upload_max_filesize');
    
    if ($this->checkImageSize($this->file_location) === false) {
        $canUpload = false;
        throw new UploadException("File is too big");
    }
    
    if ($this->checkImageMimeType($this->file_location) === false) {

        $canUpload = false;
        throw new UploadException("File type is not supported");

    }
    
    if ($writableFolder === false ) {
        
        $canUpload = false;
        throw new UploadException("destination folder is not writable");

    } elseif ($tempName === false) {

        $canUpload = false;
        throw new UploadException("There is no picture uploaded");

    } elseif ($this->file_error === 1) {
       
        $canUpload = false;
        throw new UploadException("Error! Exceeded file limit. Max file size is ".format_size_unit($maxSize));
        
    } elseif ($this->file_error > 1) {
        
        $canUpload = false;
        throw new UploadException("Something Went Wrong");

    } else {
        
        $canUpload = true;
        
    }
    
    return $canUpload;
    
   } catch(UploadException $e) {

      $this->error_message = LogError::newMessage($e);
      $this->error_message = LogError::customErrorMessage();
   
   }

 }

 protected function saveImageLogo($file_name, $width, $height, $mode)
 {
   if($this -> readyToUpload() === true) {

    $upload_dir = $this -> path_destination;
    $upload_dir_thumb = $this->path_destination . 'thumbs/';
    $file_uploaded = $upload_dir . $file_name;

    if (filesize($this->file_size) > 52000) {

        move_uploaded_file($this->file_location, $file_uploaded);

        $resizer = new Resize($file_uploaded);
        $resizer -> resizeImage($width, $height, $mode);
        $resizer -> saveImage($file_uploaded, 100);

    } else {
        move_uploaded_file($this->file_location, $file_uploaded);
    }

     // checking file type
    $img_source = null;
         
    if ($file_type == "image/jpeg") {
        
        $img_source = imagecreatefromjpeg($file_uploaded);
        
    } elseif ($file_type == "image/png") {
        
        $img_source = imagecreatefrompng($file_uploaded);
        
    } elseif ($file_type == "image/jpg") {
        
        $img_source = imagecreatefromjpeg($file_uploaded);
        
    } elseif ($file_type == "image/gif") {
        
        $img_source = imagecreatefromgif($file_uploaded);
        
    }
    
    $source_width = imagesx($img_source);
    $source_height = imagesy($img_source);
    
    // set picture's size
    $set_width = 135;
    $set_height = ($set_width/$source_width) * $source_height;
    
    // process
    $img_processed = imagecreatetruecolor($set_width, $set_height);
    imagecopyresampled($img_processed, $img_source, 0, 0, 0, 0, $set_width, $set_height, $source_width, $source_height);
    
    // save picture's thumbnail
    if ($this->file_type == "image/jpeg") {
        
        imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name, 75);
        
    } elseif ($this->file_type == "image/png") {
        
        imagepng($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    } elseif ($$this->file_type == "image/gif") {
        
        imagegif($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    } elseif ($this->file_type == "image/jpg") {
        
        imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name, 75);
        
    }
    
    // Delete Picture in computer's memory
    imagedestroy($img_source);
    imagedestroy($img_processed);
    
} else {
    
    $exception = new UploadException($this->error_message);
    $this->error_message = LogError::newMessage($exception);
    $this->error_message = LogError::customErrorMessage();
    
}

}

protected function saveImagePost($file_name, $width, $height, $mode)
{
     if ($this->readyToUpload() === true) {
         
         $upload_dir = $this->path_destination;
         $upload_dir_thumb = $this->path_destination . 'thumbs/';
         $file_uploaded = $upload_dir . $file_name;
         
         if (filesize($this->file_size) > 52000) {
             
             move_uploaded_file($this->file_location, $file_uploaded);
             
             $resizer = new Resize($file_uploaded);
             $resizer -> resizeImage($width, $height, $mode);
             $resizer -> saveImage($file_uploaded, 100);
             
         } else {
             move_uploaded_file($this->file_location, $file_uploaded);
         }
         
         // checking file type
         $img_source = null;
         
         if ($file_type == "image/jpeg") {
             
             $img_source = imagecreatefromjpeg($file_uploaded);
             
         } elseif ($file_type == "image/png") {
             
             $img_source = imagecreatefrompng($file_uploaded);
             
         } elseif ($file_type == "image/jpg") {
             
             $img_source = imagecreatefromjpeg($file_uploaded);
             
         } elseif ($file_type == "image/gif") {
             
             $img_source = imagecreatefromgif($file_uploaded);
             
         }
         
         $source_width = imagesx($img_source);
         $source_height = imagesy($img_source);
         
         // set picture's size
         $set_width = 320;
         $set_height = ($set_width/$source_width) * $source_height;
         
         // process
         $img_processed = imagecreatetruecolor($set_width, $set_height);
         imagecopyresampled($img_processed, $img_source, 0, 0, 0, 0, $set_width, $set_height, $source_width, $source_height);
         
         // save picture's thumbnail
         if ($this->file_type == "image/jpeg") {
             
             imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name, 75);
             
         } elseif ($this->file_type == "image/png") {
             
             imagepng($img_processed, $upload_path_thumb . "thumb_" . $file_name);
             
         } elseif ($$this->file_type == "image/gif") {
             
             imagegif($img_processed, $upload_path_thumb . "thumb_" . $file_name);
             
         } elseif ($this->file_type == "image/jpg") {
             
             imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name, 75);
             
         }
         
         // Delete Picture in computer's memory
         imagedestroy($img_source);
         imagedestroy($img_processed);
         
     } else {
         
         $exception = new UploadException($this->error_message);
         $this->error_message = LogError::newMessage($exception);
         $this->error_message = LogError::customErrorMessage();
         
     }
     
 }
 
 public function renameImage()
 {
     $this->setFileBaseName($this->file_name);
     $this->setFileExtension($this->file_name);
     return rename_file(md5(rand(0,999).$this->getFileBaseName())).$this->getFileExtension();
 }
 
 public function isImageUploaded()
 {
     $imageUploaded = true;

     if (empty($this->file_location) || empty($this->file_basename)) {

        $imageUploaded = false;
     }

     return $imageUploaded;
     
 }
 
 public function uploadImage($uploadType, $file_name, $width, $height, $mode)
 {
     $allowedType = ['post', 'page', 'logo', 'media'];
     
     if (in_array($uploadType, $allowedType, true)) {
     
        switch ($uploadType) {
         
            case 'page':
            case 'post' :
                
               $this->saveImagePost($file_name, $width, $height, $mode);
                
               break;
                
            case 'logo':
               
               $this->saveImageLogo($file_name, $width, $height, $mode);
               
               break;
   
        }

     }
     
 }

}