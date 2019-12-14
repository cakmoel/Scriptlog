<?php
/**
 * Class MediaEvent
 * 
 * @category Event Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class MediaEvent
{
 
/**
 * Id
 * 
 * @var integer
 * 
 */
 private $mediaId;

/**
 * Media's filename
 * 
 * @var string
 * 
 */
 private $media_filename;

/**
 * Caption
 * 
 * @var string
 * 
 */
 private $media_caption;

/**
 * Media's type
 * 
 * @var string
 * 
 */
 private $media_type;

/**
 * Media's target
 * 
 * @var string
 * 
 */
 private $media_target;

/**
 * Media's user
 * 
 * @var string
 * 
 */
 private $media_user;

/**
 * Media's access
 * 
 * @var string
 * 
 */
 private $media_access;

/**
 * Media's status
 * 
 * @var string
 * 
 */
 private $media_status;

/**
 * Media DAO 
 * 
 * @var object
 * 
 */
 private $mediaDao;

/**
 * Media metadata key
 * 
 * @var string
 * 
 */
 private $meta_key;

/**
 * Media metadata value
 * 
 * @var string
 * 
 */
 private $meta_value;

/**
 * Validator
 * 
 * @var object
 * 
 */
 private $validator;

/**
 * Sanitizer
 * 
 * @var object
 * 
 */
 private $sanitizer;

/**
 * Initialize an intanciates of class properties or method
 * 
 * @param object $mediaDao
 * @param object $validator
 * @param object $sanitizer
 * 
 */
 public function __construct(MediaDao $mediaDao, FormValidator $validator, Sanitize $sanitizer)
 {
   $this->mediaDao  = $mediaDao;
   $this->validator = $validator;
   $this->sanitizer = $sanitizer;
 }

/**
 * set media id
 * 
 * @param integer $mediaId
 * 
 */
 public function setMediaId($mediaId)
 {
   $this->mediaId = $mediaId;
 }

/**
 * set media filename
 * 
 * @param string $string
 * 
 */
 public function setMediaFilename($filename)
 {
   $this->media_filename = $filename;
 }

/**
 * set media caption
 * 
 * @param string $caption
 * 
 */
 public function setMediaCaption($caption)
 {
   $this->media_caption = $caption;
 }

/**
 * Set media type
 * 
 * @param string $type
 * 
 */
 public function setMediaType($type)
 {
   $this->media_type = $type;
 }

/**
 * Set media target
 * 
 * @param string $target
 * 
 */
 public function setMediaTarget($target)
 {
   $this->media_target = $target;
 }

/**
 * Set media user
 * 
 * @param string $user
 * 
 */
 public function setMediaUser($user)
 {
   $this->media_user = $user;
 }

/**
 * Set media access
 * 
 * @param mixed $string
 * 
 */
 public function setMediaAccess($access)
 {
   $this->media_access = $access;
 }

/**
 * Set media status
 * 
 * @param string $status
 * 
 */
 public function setMediaStatus($status)
 {
   $this->media_status = $status;
 }

/**
 * Set media metadata key
 * 
 * @param string $key
 * 
 */
 public function setMediaKey($key)
 {
   $this->meta_key = $key;
 }

/**
 * Set media metadata value
 * 
 * @param string $value
 * 
 */
 public function setMediaValue($value)
 {
   $this->meta_value = $value;
 }

/**
 * Grab all media
 * retrieve all media records
 * 
 * @param integer $orderBy
 * 
 */
 public function grabAllMedia($orderBy = 'ID', $user_level = null)
 {
   return $this->mediaDao->findAllMedia($orderBy, $user_level);
 }

/**
 * GrabMedia
 * retrieve a single record of media
 * 
 * @param integer $id
 * 
 */
 public function grabMedia($id)
 {
   return $this->mediaDao->findMediaById($id, $this->sanitizer);
 }

/**
 * Grab media meta
 * retrieve a single record of media properties(meta)
 * 
 * @param integer $id
 * @return mixed
 * 
 */
 public function grabMediaMeta($id, $filename)
 {
   return $this->mediaDao->findMediaMetaValue($id, $filename, $this->sanitizer); 
 }

/**
 * Add media
 * create new media record
 * 
 * @return mixed
 * 
 */
 public function addMedia()
 {

   $this->validator->sanitize($this->media_caption, 'string');
   $this->validator->sanitize($this->media_filename, 'string');
   $this->validator->sanitize($this->media_user, 'string');
   
   return $this->mediaDao->createMedia([
     'media_filename' => $this->media_filename,
     'media_caption' => $this->media_caption,
     'media_type' => $this->media_type,
     'media_target' => $this->media_target,
     'media_user' => $this->media_user,
     'media_access' => $this->media_access,
     'media_status' => $this->media_status
   ]);
  
 }

/**
 * Add media meta
 * 
 * @return mixed
 * 
 */
 public function addMediaMeta()
 {
   $this->validator->sanitize($this->mediaId, 'int');
   $this->validator->sanitize($this->meta_key, 'string');
   $this->validator->sanitize($this->meta_value, 'string');

   return $this->mediaDao->createMediaMeta([
     'media_id' => $this->mediaId,
     'meta_key' => $this->meta_key,
     'meta_value' => $this->meta_value
   ]);

 }

/**
 * Updating media record
 * 
 * @return object
 * 
 */
 public function modifyMedia()
 {

   $this->validator->sanitize($this->mediaId, 'int');
   $this->validator->sanitize($this->media_caption, 'string');

   if(empty($this->media_filename)) {

      return $this->mediaDao->updateMedia($this->sanitizer, [
        'media_caption' => $this->media_caption,
        'media_target' => $this->media_target,
        'media_access' => $this->media_access,
        'media_status' => $this->media_status
      ], $this->mediaId);

   } else {

      return $this->mediaDao->updateMedia($this->sanitizer, [
         'media_filename' => $this->media_filename,
         'media_caption' => $this->media_caption,
         'media_access' => $this->media_access,
         'media_status' => $this->media_status
      ], $this->mediaId);

   }

 }

/**
 * Removes media record
 * if there is a file inside media directory, delete it
 * 
 * @return object
 * 
 */
 public function removeMedia()
 {

   $this->validator->sanitize($this->mediaId, 'int');

   if(!$data_media = $this->mediaDao->findMediaById($this->mediaId, $this->sanitizer)) {
      direct_page('index.php?load=media&error=mediaNotFound', 404);
   }

   $filename = $data_media['media_filename'];
   $filetype = $data_media['media_type'];

   if($filename !== '') {

      switch ($filetype) {

        case 'audio/mpeg':
        case 'audio/wav':
        case 'audio/ogg':    

          if(is_readable(__DIR__ . '/../../public/files/audio/'.$filename)) {

             unlink(__DIR__ . '/../../public/files/audio/'.$filename);

          }

          break;

        case 'application/pdf':
        case 'application/msword':
        case 'application/rar':
        case 'application/zip':
        case 'application/vnd.ms-excel':
        case 'application/vnd.microsoft.portable-executable':
        case 'application/vnd.ms-powerpoint':
        case 'application/octet-stream':              

          if(is_readable(__DIR__ . '/../public/files/docs/'.$filename)) {

             unlink(__DIR__ . '/../public/files/docs/'.$filename);

          } 

          break;

        case 'video/mp4':
        case 'video/webm':
        case 'video/ogg':    

          if(is_readable(__DIR__ . '/../public/files/video/'.$filename)) {

            unlink(__DIR__ . '/../public/files/video/'.$filename);
            
          }

          break;

        default:
          
         # default delete file image

          if(is_readable(__DIR__ . '/../public/files/pictures/'.$filename)) {
            
            unlink(__DIR__ . '/../public/files/pictures/'.$filename);
            unlink(__DIR__ . '/../public/files/pictures/thumbs/thumbs_'.$filename);
            
          }

          break;

      }

      return $this->mediaDao->deleteMedia($this->mediaId, $this->sanitizer);

   }

 }

/**
 * Drop down media target
 * 
 * @param string $selected
 * @return string
 * 
 */
 public function mediaTargetDropDown($selected = "")
 {
   return $this->mediaDao->dropDownMediaTarget($selected);
 }

/**
 * Drop down media access
 * 
 * @param string $selected
 * @return string
 * 
 */
 public function mediaAccessDropDown($selected = "")
 {
   return $this->mediaDao->dropDownMediaAccess($selected);
 }

/**
 * Drop down media status
 * 
 * @param string $selected
 * @return string
 * 
 */
 public function mediaStatusDropDown($selected = "")
 {
   return $this->mediaDao->dropDownMediaStatus($selected);
 }

/**
 * Checking if user session available return it
 * 
 * @return string
 * 
 */
 public function isMediaUser()
 {

  if (isset($_SESSION['user_level'])) {

    return $_SESSION['user_level'];

  }
  
 }

/**
 * Total media libray on database record
 * 
 * @param array $data default value = null
 * @return integer|number
 * 
 */
 public function totalMedia($data = null)
 {
   return $this->mediaDao->totalMediaRecords($data);
 }

}