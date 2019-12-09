<?php
/**
 * Class MediaApp
 * 
 * @category Class MediaApp extends BaseApp
 * @author   M.Noermoehammad 
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class MediaApp extends BaseApp
{

/**
 * View
 * 
 * @var string
 * 
 */
private $view;

/**
 * MediaEvent
 * 
 * @var string
 * 
 */
private $mediaEvent;

/**
 * Initialize object properties
 * 
 * @param object $mediaEvent
 * 
 */
public function __construct(MediaEvent $mediaEvent)
{
 $this->mediaEvent = $mediaEvent;
}

/**
 * Retrieve all media items
 * 
 * @inheritDoc
 * @see BaseApp::listItems()
 * 
 */
public function listItems()
{

  $errors = array();
  $status = array();
  $checkError = true;
  $checkStatus = false;

  if (isset($_GET['error'])) {

     $checkError = false;
     if ($_GET['error'] == 'mediaNotFound') array_push($errors, "Error: Media Not Found");

  }

  if (isset($_GET['status'])) {
      $checkStatus = true;
      if ($_GET['status'] == 'mediaAdded') array_push($status, "New media added");
      if ($_GET['status'] == 'mediaUpdated') array_push($status, "Media has been updated");
      if ($_GET['status'] == 'mediaDeleted') array_push($status, "Media deleted");
  }

  $this->setView('all-media');
  $this->setPageTitle('Media Library');
  $this->view->set('pageTitle', $this->getPageTitle());

  if (!$checkError) {
     $this->view->set('errors', $errors);
  }

  if ($checkStatus) {
     $this->view->set('status', $status);
  }

  $this->view->set('mediaTotal', $this->mediaEvent->totalMedia());

  if ($this->mediaEvent->isMediaUser() != 'administrator') {

     $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia('ID', $this->mediaEvent->isMediaUser()));

  } else {
     
     $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia());

  }

  return $this->view->render();
  
}

/**
 * Insert new media
 * 
 * {@inheritDoc}
 * @see BaseApp::insert()
 * 
 */
public function insert()
{
  
  $errors = array();
  $checkError = true;

  if (isset($_POST['mediaFormSubmit'])) {

    $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
    $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
    $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
    $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
    $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

    $media_caption = isset($_POST['media_caption']) ? prevent_injection($_POST['media_caption']) : '';
    $media_target = $_POST['media_target'];
    $media_access = $_POST['media_access'];

    $accepted_files = array(
                 'pdf'  => 'application/pdf', 
                 'doc'  => 'application/msword', 
                 'rar'  => 'application/rar', 
                 'zip'  => 'application/zip', 
                 'xls'  => 'application/vnd.ms-excel', 
                 'xls'  => 'application/octet-stream', 
                 'exe'  => 'application/vnd.microsoft.portable-executable', 
                 'ppt'  => 'application/vnd.ms-powerpoint',
                 'jpeg' => 'image/jpeg', 
                 'jpg'  => 'image/jpeg', 
                 'png'  => 'image/png', 
                 'gif'  => 'image/gif', 
                 'webp' => 'image/webp',
                 'mp3'  => 'audio/mpeg', 
                 'wav'  => 'audio/wav',
                 'ogg'  => 'audio/ogg',
                 'mp4'  => 'video/mp4',
                 'webm' => 'video/webm',
                 'ogg'  => 'video/ogg'
                );

    try {

      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
        throw new AppException("Sorry, unpleasant attempt detected!");
        
      }

      if (!isset($file_error) || is_array($file_error)) {

        $checkError = false;
        array_push($errors, "Invalid paramenter");
        
      }

      switch ($file_error) {

        case UPLOAD_ERR_OK:
 	         break;
        case UPLOAD_ERR_NO_FILE:
           
           $checkError = false;
           array_push($errors, "No file uploaded");

           break;
 	    
 	     case UPLOAD_ERR_INI_SIZE:
 	     case UPLOAD_ERR_FORM_SIZE:
          
          $checkError = false;
          array_push($errors, "Exceeded filesize limit");

          break;

        default:
            
          $checkError = false;
          array_push($errors, "Unknown errors");
          
          break;
          
      }

      if ($file_size > APP_FILE_SIZE) {

         $checkError = false;
         array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(APP_FILE_SIZE));

      }

      if(false === check_file_name($file_location)) {

        $checkError = false;
        array_push($errors, "file name is not valid");

      }

      if(true === check_file_length($file_location)) {

        $checkError = false;
        array_push($errors, "file name is too long");
        
      }

      if(false == check_mime_type($accepted_files, $file_location)) {

        $checkError = false;
        array_push($errors, "Invalid file format");

      }

      // get new filename
      $file_info = pathinfo($file_name);
      $name = $file_info['filename'];
      $file_extension = $file_info['extension'];
      $tmp = str_replace(array('.',' '), array('',''), microtime());
      $new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').'.'.$file_extension;

      list($width, $height) = getimagesize($file_location);

      $media_metavalue = array(
        'File name' => $new_filename, 
        'File type' => $file_type, 
        'File size' => format_size_unit($file_size), 
        'Uploaded on' => date("Y-m-d H:i:s"), 
        'Dimension' => $width.'x'.$height);

      // upload file
      if (is_uploaded_file($file_location)) {

        upload_media($file_location, $file_type, $file_size, basename($new_filename));

      }
     
      if (!$checkError) {

         $this->setView('edit-media');
         $this->setPageTitle('Upload New Media');
         $this->setFormAction(ActionConst::NEWMEDIA);
         $this->view->set('pageTitle', $this->getPageTitle());
         $this->view->set('formAction', $this->getFormAction());
         $this->view->set('errors', $errors);
         $this->view->set('formData', $_POST);
         $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown());
         $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown());
         $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

      } else {

         $this->mediaEvent->setMediaFilename($new_filename);
         $this->mediaEvent->setMediaCaption($media_caption);
         $this->mediaEvent->setMediaType($file_type);
         $this->mediaEvent->setMediaTarget($media_target);
         $this->mediaEvent->setMediaUser($this->mediaEvent->isMediaUser());
         $this->mediaEvent->setMediaAccess($media_access);
         $this->mediaEvent->setMediaStatus('1');
        
         $media_id = $this->mediaEvent->addMedia();

         if($media_id) {
            
           $this->mediaEvent->setMediaId($media_id);
           $this->mediaEvent->setMediaKey($new_filename);
           $this->mediaEvent->setMediaValue(json_encode($media_metavalue));

           $this->mediaEvent->addMediaMeta();
 
         }

         direct_page('index.php?load=medialib&status=mediaAdded', 200);

      }
      
    } catch(AppException $e) {

       LogError::setStatusCode(http_response_code());
       LogError::newMessage($e);
       LogError::customErrorMessage('admin');
       
    }

  } else {

     $this->setView('edit-media');
     $this->setPageTitle('Media Library');
     $this->setFormAction(ActionConst::NEWMEDIA);
     $this->view->set('pageTitle', $this->getPageTitle());
     $this->view->set('formAction', $this->getFormAction());
     $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown());
     $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown());
     $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
 
  }

  return $this->view->render();

}

public function update($id)
{

  $errors = array();
  $checkError = true;

  if (!$getMedia = $this->mediaEvent->grabMedia($id)) {

     direct_page('index.php?load=medialib&error=mediaNotFound', 404);

  }

  $accepted_files = array(
    'pdf'  => 'application/pdf', 
    'doc'  => 'application/msword', 
    'rar'  => 'application/rar', 
    'zip'  => 'application/zip', 
    'xls'  => 'application/vnd.ms-excel', 
    'xls'  => 'application/octet-stream', 
    'exe'  => 'application/vnd.microsoft.portable-executable', 
    'ppt'  => 'application/vnd.ms-powerpoint',
    'jpeg' => 'image/jpeg', 
    'jpg'  => 'image/jpeg', 
    'png'  => 'image/png', 
    'gif'  => 'image/gif', 
    'webp' => 'image/webp',
    'mp3'  => 'audio/mpeg', 
    'wav'  => 'audio/wav',
    'ogg'  => 'audio/ogg',
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogg'  => 'video/ogg'
   );

  $data_media = array(
    
    'ID' => $getMedia['ID'],
    'media_filename' => $getMedia['media_filename'],
    'media_caption' => $getMedia['media_caption'],
    'media_type' => $getMedia['media_type'],
    'media_target' => $getMedia['media_target'],
    'media_user' => $getMedia['media_user'],
    'media_access' => $getMedia['media_access'],
    'media_status' => $getMedia['media_status']

  );

  if (isset($_POST['mediaFormSubmit'])) {

    $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
    $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
    $file_name = isset($_FILES['media']['name']) ? $_FILES['media_generci']['name'] : '';
    $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
    $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

    $caption = isset($_POST['caption']) ? prevent_injection($_POST['caption']) : '';
    $media_type = $_POST['media_type'];
    $media_target = $_POST['media_target'];
    $media_access = $_POST['media_access'];
    $media_status = $_POST['media_status'];
    $media_id = (int)$_POST['media_id'];

    try {

      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
        throw new AppException("Sorry, unpleasant attempt detected!");
        
      }

      if (!$checkError) {

        $this->setView('edit-media');
        $this->setPageTitle('Edit Media');
        $this->setFormAction(ActionConst::EDITMEDIA);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('errors', $errors);
        $this->view->set('mediaData', $data_media);
        $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown($getMedia['media_target']));
        $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown($getMedia['media_access']));
        $this->view->set('mediaStatus', $this->mediaEvent->mediaStatusDropDown($getMedia['media_status']));
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

     } 

     if (!empty($file_location)) {

        if (!isset($file_error) || is_array($file_error)) {

          $checkError = false;
          array_push($errors, "Invalid paramenter");
          
        }
  
        switch ($file_error) {
  
           case UPLOAD_ERR_OK:
              break;
           case UPLOAD_ERR_NO_FILE:
             
             $checkError = false;
             array_push($errors, "No file uploaded");
  
             break;
         
           case UPLOAD_ERR_INI_SIZE:
           case UPLOAD_ERR_FORM_SIZE:
            
             $checkError = false;
             array_push($errors, "Exceeded filesize limit");
  
             break;
  
           default:
              
             $checkError = false;
             array_push($errors, "Unknown errors");
            
             break;
            
         }
  
         if ($file_size > APP_FILE_SIZE) {
  
           $checkError = false;
           array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(APP_FILE_SIZE));
  
         }
  
         if(false === check_file_name($file_location)) {
  
          $checkError = false;
          array_push($errors, "file name is not valid");
  
         }
  
         if(true === check_file_length($file_location)) {
  
          $checkError = false;
          array_push($errors, "file name is too long");
          
         }
  
         if(false == check_mime_type($accepted_files, $file_location)) {
  
          $checkError = false;
          array_push($errors, "Invalid file format");
  
         }
  
        // get new filename
        $file_info = pathinfo($file_name);
        $name = $file_info['filename'];
        $file_extension = $file_info['extension'];
        $tmp = str_replace(array('.',' '), array('',''), microtime());
        $new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').$file_extension;
  
        list($width, $height) = getimagesize($file_location);
  
        $media_metavalue = array(
          'File name' => $new_filename, 
          'File type' => $file_type, 
          'File size' => $file_size, 
          'Uploaded on' => date("Y-m-d H:i:s"), 
          'Dimension' => $width.'x'.$height);
  
         if (is_uploaded_file($file_location)) {

            upload_media($file_location, $file_type, $file_size, basename($new_filename));

         }
        
         $this->mediaEvent->setMediaFilename($new_filename);
         $this->mediaEvent->setMediaCaption($caption);
         $this->mediaEvent->setMediaType($type);
         $this->mediaEvent->setMediaTarget($target);
         $this->mediaEvent->setMediaAccess($access);
         $this->mediaEvent->setMediaStatus($status);
         $this->mediaEvent->setMediaId($media_id);

      } else {

        $this->mediaEvent->setMediaCaption($caption);
        $this->mediaEvent->setMediaType($type);
        $this->mediaEvent->setMediaTarget($target);
        $this->mediaEvent->setMediaAccess($access);
        $this->mediaEvent->setMediaStatus($status);
        $this->mediaEvent->setMediaId($media_id);

      }

      $this->mediaEvent->modifyMedia();
      direct_page('index.php?load=medialib&status=mediaUpdated', 200);

    } catch(AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::newMessage($e);
      LogError::customErrorMessage('admin');

    }

  } else {
    
    $this->setView('edit-media');
    $this->setPageTitle('Media Library');
    $this->setFormAction(ActionConst::EDITMEDIA);
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('formAction', $this->getFormAction());
    $this->view->set('mediaData', $data_media);
    $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown($getMedia['media_target']));
    $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown($getMedia['media_access']));
    $this->view->set('mediaStatus', $this->mediaEvent->mediaStatusDropDown($getMedia['media_status']));
    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

  }

  return $this->view->render();

}

public function remove($id)
{
  $this->mediaEvent->setMediaId($id);
  $this->mediaEvent->removeMedia();
  direct_page('index.php?load=medialib&status=mediaDeleted', 200);
}

public function download($Id)
{

}

protected function setView($viewName)
{
  $this->view = new View('admin', 'ui', 'medialib', $viewName);
}

}