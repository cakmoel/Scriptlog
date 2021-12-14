<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
 * @var object
 * 
 */
private $view;

/**
 * MediaEvent
 * 
 * @var object
 * 
 */
private $mediaEvent;

const TIME_BEFORE_EXPIRED = 8;

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

  if (isset($_SESSION['error'])) {

    $checkError = false;
    if ($_SESSION['error'] == 'mediaNotFound') array_push($errors, "Error: Media Not Found");
    unset($_SESSION['error']);

  }

  if (isset($_SESSION['status'])) {
    
    $checkStatus = true;
    if ($_SESSION['status'] == 'mediaAdded') array_push($status, "New media added");
    if ($_SESSION['status'] == 'mediaUpdated') array_push($status, "Media has been updated");
    if ($_SESSION['status'] == 'mediaDeleted') array_push($status, "Media deleted");
    unset($_SESSION['status']);

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

  if ($this->mediaEvent->isMediaUser() == 'administrator') {

    $this->view->set('mediaTotal', $this->mediaEvent->totalMedia());
    $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia());
    
  } else {
     
    $this->view->set('mediaTotal', $this->mediaEvent->totalMedia([$this->mediaEvent->isMediaUser()]));
    $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia('ID', $this->mediaEvent->isMediaUser()));

  }

  return $this->view->render();
  
}

/**
 * insert
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

    $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : null;
    $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : null;
    $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : null;
    $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : null;
    $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : null;

    $filters = ['media_caption' => FILTER_SANITIZE_SPECIAL_CHARS, 'media_target' => FILTER_SANITIZE_STRING, 'media_access' => FILTER_SANITIZE_STRING];

    $form_fields = ['media_caption' => 200];

    // get new filename and extension
    $new_filename = generate_filename($file_name)['new_filename'];
    $file_extension = generate_filename($file_name)['file_extension'];

    try {

      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
         header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
         $checkError = false;
         array_push($errors, "Sorry, unpleasant attempt detected!");
       
      }

      if (!empty($_POST['media_caption'])) {

          if(true === form_size_validation($form_fields)) {

             $checkError = false;
             array_push($errors, "Form data is longer than allowed");

          }

      }

      if (false === sanitize_selection_box(distill_post_request($filters)['media_target'], ['blog' => 'Blog', 'download' => 'Download', 'gallery' => 'Gallery', 'page' => 'Page'])) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");

      }

      if (false === sanitize_selection_box(distill_post_request($filters)['media_access'], ['public' => 'Public', 'private' => 'Private'])) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");

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

      if ($file_extension === "jpeg" || $file_extension === "jpg" || $file_extension === "png" || $file_extension === "gif" || $file_extension === "webp" || $file_extension === "bmp" ) {

         list($width, $height) = ($file_location) ? getimagesize($file_location) : null;

         $media_metavalue = array(
              'Origin' => rename_file($file_name), 
              'File type' => $file_type, 
              'File size' => format_size_unit($file_size), 
              'Uploaded on' => date("Y-m-d H:i:s"), 
              'Dimension' => $width.'x'.$height
            );

      } else {

         $media_metavalue = array(
            'Origin' => rename_file($file_name), 
            'File type' => $file_type, 
            'File size' => format_size_unit($file_size), 
            'Uploaded on' => date("Y-m-d H:i:s"
        ));

      }

       // upload file
      if (is_uploaded_file($file_location)) {

        if ( ( false === check_file_extension($file_name) ) || ( false === check_mime_type(mime_type_dictionary(), $file_location) ) ) {

          $checkError = false;
          array_push($errors, "Invalid file format");

        } else {

          upload_media($file_location, $file_type, $file_size, basename($new_filename));

        }
  
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
         $this->mediaEvent->setMediaCaption(prevent_injection(distill_post_request($filters)['media_caption']));
         $this->mediaEvent->setMediaType($file_type);
         $this->mediaEvent->setMediaTarget(distill_post_request($filters)['media_target']);
         $this->mediaEvent->setMediaUser($this->mediaEvent->isMediaUser());
         $this->mediaEvent->setMediaAccess(distill_post_request($filters)['media_access']);
         $this->mediaEvent->setMediaStatus('1');
        
         $media_id = $this->mediaEvent->addMedia();

         if($media_id) {
            
           $this->mediaEvent->setMediaId($media_id);
           $this->mediaEvent->setMediaKey($new_filename);
           $this->mediaEvent->setMediaValue(json_encode($media_metavalue));
           $this->mediaEvent->addMediaMeta();
           
           if (isset($_POST['media_target']) && $_POST['media_target'] == 'download') {
             
            $this->mediaEvent->setMediaId($media_id);
            $this->mediaEvent->setMediaIdentifier(generate_media_identifier());
            $this->mediaEvent->setBeforeExpired(time() + self::TIME_BEFORE_EXPIRED * 60 * 60);
            $this->mediaEvent->setIpAddress(get_ip_address());
            $this->mediaEvent->addMediaDownload();
             
           }
           
         }

        $_SESSION['status'] = "mediaAdded";
        direct_page('index.php?load=medialib&status=mediaAdded', 302);

      }
      
    } catch (Throwable $th) {

      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($th);

    } catch(AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($e);
       
    }

  } else {

     $this->setView('edit-media');
     $this->setPageTitle('Upload New Media');
     $this->setFormAction(ActionConst::NEWMEDIA);
     $this->view->set('pageTitle', $this->getPageTitle());
     $this->view->set('formAction', $this->getFormAction());
     $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown());
     $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown());
     $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
 
  }

  return $this->view->render();

}

/**
 * update
 * 
 * @inheritDoc
 * @uses BaseApp::update()
 * @param int $id
 * @return void
 * 
 */
public function update($id)
{

  $errors = array();
  $checkError = true;

  if (!$getMedia = $this->mediaEvent->grabMedia($id)) {

    $_SESSION['error'] = "mediaNotFound";
    direct_page('index.php?load=medialib&error=mediaNotFound', 404);

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

  $getMediaMeta = $this->mediaEvent->grabMediaMeta($getMedia['ID'], $getMedia['media_filename']);

  $media_properties = array(

    'ID' => $getMediaMeta['ID'],
    'media_id' => $getMediaMeta['media_id'],
    'meta_key' => $getMediaMeta['meta_key'],
    'meta_value' => $getMediaMeta['meta_value']

  );

  if (isset($_POST['mediaFormSubmit'])) {

    $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
    $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
    $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
    $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
    $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

    $filters = [
      'media_caption' => FILTER_SANITIZE_SPECIAL_CHARS,
      'media_target' => FILTER_SANITIZE_STRING,
      'media_access' => FILTER_SANITIZE_STRING,
      'media_status' => FILTER_SANITIZE_NUMBER_INT,
      'media_id' => FILTER_SANITIZE_NUMBER_INT
    ];

    $form_fields = ['media_caption' => 200];

    // get new filename
    $new_filename = generate_filename($file_name)['new_filename'];
    $file_extension = generate_filename($file_name)['file_extension'];

    try {

      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
        throw new AppException("Sorry, unpleasant attempt detected!");
        
      }

      if (!empty($_POST['media_caption'])) {

         if(true === form_size_validation($form_fields)) {

           $checkError = false;
           array_push($errors, "Form data is longer than allowed");

         }

      }

      if (false === sanitize_selection_box(distill_post_request($filters)['media_target'], ['blog' => 'Blog', 'download' => 'Download', 'gallery' => 'Gallery', 'page' => 'Page'])) {

        $checkError = false;
        array_push($errors, "Please choose the available value provided!");

      }

      if (false === sanitize_selection_box(distill_post_request($filters)['media_access'], ['public' => 'Public', 'private' => 'Private'])) {

        $checkError = false;
        array_push($errors, "Please choose the available value provided!");

      }

      if (false === sanitize_selection_box(distill_post_request($filters)['media_status'], ['Enabled', 'Disabled'])) {

        $checkError = false;
        array_push($errors, "Please choose the available value provided!");
         
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
        $this->view->set('mediaProperties', $media_properties);
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

     } else {

     if (!empty($file_location)) {

        if (!isset($file_error) || is_array($file_error)) {

          $checkError = false;
          array_push($errors, "Invalid paramenter");
          
        }
  
        switch ($file_error) {
  
           case UPLOAD_ERR_OK:
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
  
         if ($file_extension == "jpeg" || $file_extension == "jpg" || $file_extension == "png" || $file_extension == "gif" || $file_extension == "webp" || $file_extension === "bmp" ) {

            list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : null;
            
            $media_metavalue = array(

              'Origin' => rename_file($file_name), 
              'File type' => $file_type, 
              'File size' => format_size_unit($file_size), 
              'Uploaded on' => date("Y-m-d H:i:s"), 
              'Dimension' => $width.'x'.$height);
 
          } else {
 
            $media_metavalue = array(
              
              'Origin' => rename_file($file_name), 
              'File type' => $file_type, 
              'File size' => format_size_unit($file_size), 
              'Uploaded on' => date("Y-m-d H:i:s"));
 
          }
          
          // upload file
        if (is_uploaded_file($file_location)) {

          if ( ( false === check_mime_type(mime_type_dictionary(), $file_location)) || ( false === check_file_extension($file_name))) {

            $checkError = false;
            array_push($errors, "Invalid file format");
  
          } else {

           upload_media($file_location, $file_type, $file_size, basename($new_filename));

          }
         
        }

         $this->mediaEvent->setMediaFilename($new_filename);
         $this->mediaEvent->setMediaCaption(prevent_injection(distill_post_request($filters)['media_caption']));
         $this->mediaEvent->setMediaType($file_type);
         $this->mediaEvent->setMediaTarget(distill_post_request($filters)['media_target']);
         $this->mediaEvent->setMediaAccess(distill_post_request($filters)['media_access']);
         $this->mediaEvent->setMediaStatus(distill_post_request($filters)['media_status']);
         $this->mediaEvent->setMediaId(distill_post_request($filters)['media_id']);

         $this->mediaEvent->setMediaKey($new_filename);
         $this->mediaEvent->setMediaValue(json_encode($media_metavalue));
         $this->mediaEvent->modifyMediaMeta();

        if (isset($_POST['media_target']) && $_POST['media_target'] == 'download') {

          $this->mediaEvent->setMediaId(distill_post_request($filters)['media_id']);
          $this->mediaEvent->setMediaIdentifier(generate_media_identifier());
          $this->mediaEvent->setBeforeExpired(time() + self::TIME_BEFORE_EXPIRED * 60 * 60);
          $this->mediaEvent->setIpAddress(get_ip_address());
          $this->mediaEvent->modifyMediaDownload();
            
        }

      } else {

         $this->mediaEvent->setMediaCaption(prevent_injection(distill_post_request($filters)['media_caption']));
         $this->mediaEvent->setMediaTarget(distill_post_request($filters)['media_target']);
         $this->mediaEvent->setMediaAccess(distill_post_request($filters)['media_access']);
         $this->mediaEvent->setMediaStatus(distill_post_request($filters)['media_status']);
         $this->mediaEvent->setMediaId(distill_post_request($filters)['media_id']);

      }

       $this->mediaEvent->modifyMedia();
       $_SESSION['status'] = "mediaUpdated";
       direct_page('index.php?load=medialib&status=mediaUpdated', 302);

     }
     

    } catch (Throwable $th) {

      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($th);

    } catch(AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($e);

    }

  } else {
    
    $this->setView('edit-media');
    $this->setPageTitle('Edit Media ');
    $this->setFormAction(ActionConst::EDITMEDIA);
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('formAction', $this->getFormAction());
    $this->view->set('mediaData', $data_media);
    $this->view->set('mediaTarget', $this->mediaEvent->mediaTargetDropDown($getMedia['media_target']));
    $this->view->set('mediaAccess', $this->mediaEvent->mediaAccessDropDown($getMedia['media_access']));
    $this->view->set('mediaStatus', $this->mediaEvent->mediaStatusDropDown($getMedia['media_status']));
    $this->view->set('mediaProperties', $media_properties);
    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

  }

  return $this->view->render();

}

/**
 * remove
 *
 * {@inheritDoc}
 * @see BaseApp::remove()
 * @param int|num $id
 * 
 */
public function remove($id)
{

  $checkError = true;
  $errors = array();

  if (isset($_GET['Id'])) {

    $getMedia = $this->mediaEvent->grabMedia($id);
     
    try {
      
    
      if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
        throw new AppException("Sorry, unpleasant attempt detected!");

      }
    
      if (!filter_var($id, FILTER_VALIDATE_INT)) {

        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
        throw new AppException("Sorry, unpleasant attempt detected!");

      }

      if (!$getMedia) {

        $checkError = false;
        array_push($errors, 'Error: Media not found');

      }
      
      if(!$checkError) {

        $this->setView('all-media');
        $this->setPageTitle('Media not found');
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('errors', $errors);

        if ($this->mediaEvent->isMediaUser() == 'administrator') {

          $this->view->set('mediaTotal', $this->mediaEvent->totalMedia());
          $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia());
          
        } else {
           
          $this->view->set('mediaTotal', $this->mediaEvent->totalMedia([$this->mediaEvent->isMediaUser()]));
          $this->view->set('mediaLib', $this->mediaEvent->grabAllMedia('ID', $this->mediaEvent->isMediaUser()));
      
        }
         
        return $this->view->render();
        
      } else {

        $this->mediaEvent->setMediaId($id);
        $this->mediaEvent->removeMedia();
        $_SESSION['status'] = "mediaDeleted";
        direct_page('index.php?load=medialib&status=mediaDeleted', 302);

      }

     } catch (Throwable $th) {
       
      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($th);

     } catch (AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::exceptionHandler($e);

     }

  }
  
}

protected function setView($viewName)
{
  $this->view = new View('admin', 'ui', 'medialib', $viewName);
}

}