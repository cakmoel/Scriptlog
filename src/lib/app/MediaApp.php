<?php
/**
 * Class MediaApp
 * 
 * @package  SCRIPTLOG/LIB/APP/MediaApp
 * @category App Class
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
  $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia());
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

    $accepted_files = array('pdf' => 'application/pdf', 'doc' => 'application/msword', 'rar' => 'application/rar', 
 	               'zip' => 'application/zip', 'xls' => 'application/vnd.ms-excel', 'xls' => 'application/octet-stream', 
 	               'exe' => 'application/octet-stream', 'ppt' => 'application/vnd.ms-powerpoint',
 	               'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif');

    try {

      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
        throw new AppException("Sorry, unpleasant attempt detected!");
        
      }

      if (!isset($file_error) || is_array($file_error)) {

        $checkError = false;
        array_push($errors, "Invalid paramenter");
        
      }

      if (empty($file_location)) {

        $checkError = false;
        array_push($errors, "No file uploaded");

      }

      if ($file_size > 524876) {

         $checkError = false;
         array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(524876));

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

      if(false == check_mime_type($accepted_files, $file_location)) {

        $checkError = false;
        array_push($errors, "Invalid file format");

      }

      $file_info = pathinfo($file_name);
      $name = $file_info['filename'];
      $file_extension = $file_info['extension'];
      $tmp = str_replace(array('.',' '), array('',''), microtime());
      $new_filename = rename_file(md5($name.$tmp)).'-'.date('Ymd').'.'.$file_extension;

      list($width, $heigh) = getimagesize($file_location);

      $media_metavalue = array(
        'File name' => $new_filename, 
        'File type' => $file_type, 
        'File size' => $file_size, 
        'Uploaded on' => date("Y-m-d H:i:s"), 
        'Dimension' => $width.'x'.$height);

      upload_media('media',true,true);

      if (!$checkError) {

         $this->setView('edit-media');
         $this->setPageTitle('Upload New Media');
         $this->setFormAction('newMedia');
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
           $this->mediaEvent->setMediaValue($media_metavalue);
            
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
     $this->setFormAction('newMedia');
     $this->view->set('pageTitle', $this->getPageTitle());
     $this->view->set('formAction', $this->getFormAction());
     $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown());
     $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown());
     $this->view->set('mediaStatus', $this->mediaEvent->mediaStatusDropDown());
     $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
 
  }

  return $this->view->render();

}

public function update($id)
{

  $errors = array();
  $checkError = true;

  if (!$getMedia = $this->mediaEvent->grabMedia($id)) {

     direct_page('index.php?load=media&error=mediaNotFound', 404);

  }

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
    $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
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

      if (!isset($file_error) || is_array($file_error)) {

        $checkError = false;
        array_push($errors, "Invalid paramenter");
        
      }

      if ($file_size > 524876) {

         $checkError = false;
         array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(524876));

      }

      // get filename
	    $file_info = pathinfo($file_name);
	    $name = $file_info['filename'];
	    $file_extension = $file_info['extension'];
      $tmp = str_replace(array('.',' '), array('',''), microtime());
      $newFileName = rename_file(md5($name.$tmp)).'-'.date('Ymd').$file_extension;

      $fileUploaded = upload_media('media',true,true);

      if (is_array($fileUploaded['error'])) {

        $message = '';
        foreach ($fileUploaded['error'] as $msg) {
          
          $message .= $msg;
           
        }

        $checkError = false;
        array_push($errors, $message);

      }

      if (!$checkError) {

         $this->setView('edit-media');
         $this->setPageTitle('Upload New Media');
         $this->setFormAction('newMedia');
         $this->view->set('pageTitle', $this->getPageTitle());
         $this->view->set('formAction', $this->getFormAction());
         $this->view->set('errors', $errors);
         $this->view->set('mediaData', $data_media);
         $this->view->set('mediaType', $this->mediaEvent->mediaTypeDropDown($getMedia['media_type']));
         $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown($getMedia['media_target']));
         $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown($getMedia['media_access']));
         $this->view->set('mediaStatus', $this->mediaEvent->mediaStatusDropDown($getMedia['media_status']));
         $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

      } else {

        if (!empty($file_location)) {

          $this->mediaEvent->setMediaFilename($newFileName);
          $this->mediaEvent->setMediaCaption($caption);
          $this->mediaEvent->setMediaType($type);
          $this->mediaEvent->setMediaTarget($target);
          $this->mediaEvent->setMediaAccess($access);
          $this->mediaEvent->setMediaStatus($status);
          $this->mediaEvent->setMediaId($media_id);

        } else {

          $this->mediaEvent->setMediaFilename($newFileName);
          $this->mediaEvent->setMediaCaption($caption);
          $this->mediaEvent->setMediaType($type);
          $this->mediaEvent->setMediaTarget($target);
          $this->mediaevent->setMediaUser($this->mediaEvent->isMediaUser());
          $this->mediaEvent->setMediaAccess($access);
          $this->mediaEvent->setMediaStatus($status);
          $this->mediaEvent->setMediaId($media_id);

        }

        $this->mediaEvent->modifyMedia();
        direct_page('index.php?load=medialib&status=mediaUpdated', 200);
        
      }

    } catch(AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::newMessage($e);
      LogError::customErrorMessage('admin');

    }

  } else {
    
    $this->setView('edit-media');
    $this->setPageTitle('Media Library');
    $this->setFormAction('editMedia');
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('formAction', $this->getFormAction());
    $this->view->set('mediaData', $data_media);
    $this->view->set('mediaType', $this->mediaEvent->mediaTypeDropDown($getMedia['media_type']));
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
  $this->mediaEvent->removeMedi();
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