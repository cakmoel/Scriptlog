<?php 
/**
 * Class PageApp
 *
 * @category  Class PageApp extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PageApp extends BaseApp
{

/**
 * view
 *
 * @var object
 * 
 */
 private $view;

/**
 * pageEvent
 *
 * @var object
 * 
 */
 private $pageEvent;

 public function __construct(PageEvent $pageEvent)
 {
   $this->pageEvent = $pageEvent;
 }
 
 public function listItems()
 {
   
   $errors = array();
   $status = array();
   $checkError = true;
   $checkStatus = false;
   
   if (isset($_GET['error'])) {
       
       $checkError = false;
       if ($_GET['error'] == 'pageNotFound') array_push($errors, "Error: Page Not Found");
       
   }
   
   if (isset($_GET['status'])) {
       $checkStatus = true;
       if ($_GET['status'] == 'pageAdded') array_push($status, "New page added");
       if ($_GET['status'] == 'pageUpdated') array_push($status, "Page has been updated");
       if ($_GET['status'] == 'pageDeleted') array_push($status, "Page deleted");
   }
   
   $this->setView('all-pages');
   $this->setPageTitle('Pages');
   $this->view->set('pageTitle', $this->getPageTitle());
   
   if (!$checkError) {
       $this->view->set('errors', $errors);
   }
   
   if ($checkStatus) {
       $this->view->set('status', $status);
   }
   
   $this->view->set('pagesTotal', $this->pageEvent->totalPages());
   $this->view->set('pages', $this->pageEvent->grabPages('page'));
   return $this->view->render();
   
 }
 
/**
 * Undocumented function
 *
 * @return void
 */
 public function insert()
 {
     
  $medialib = new MediaDao();
  $errors = array();
  $checkError = true;
  
  if (isset($_POST['pageFormSubmit'])) {
      
     $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
     $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
     $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
     $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
     $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';
      
     $filters = [
         'post_title'=>FILTER_SANITIZE_SPECIAL_CHARS,
         'post_content'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
         'post_summary'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
         'post_keyword'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
         'post_status'=>FILTER_SANITIZE_STRING,
         'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
         'comment_status'=>FILTER_SANITIZE_STRING
     ];

     $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_content' => 50000];

      try {
          
          if(!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
              header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
              throw new AppException("Sorry, unpleasant attempt detected!");
              
          }

          if( check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_summary', 'post_keyword', 'post_status', 'comment_status']) == false) {

             header(APP_PROTOCOL.' 413 Payload Too Large');
             header('Status: 413 Payload Too Large');
             header('Retry-After: 3600');
             throw new AppException("Sorry, Unpleasant attempt detected");

          }
          
          if((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
              
              $checkError = false;
              array_push($errors, "Please enter a required field");
              
          }
          
          if( true === form_size_validation($form_fields) ) {

             $checkError = false;
             array_push($errors, "Form data is longer than allowed");

          }
          
          if(!empty($file_location)) {

              if(!isset($file_error) || is_array($file_error)) {

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

              if($file_size >  APP_FILE_SIZE) {

                  $checkError = false;
                  array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(APP_FILE_SIZE));

              }

              if(false === check_file_name($file_location)) {

                 $checkError = false;
                 array_push($errors, "file name is not valid");

              }

              if(true === check_file_length($file_location)) {

                  $checkError = false;
                  array_push($errors, "File name is too long");

              }

              if((false === check_mime_type(mime_type_dictionary(), $file_location)) || (false === check_file_extension($file_name))) {

                 $checkError = false;
                 array_push($errors, "Invalid file format");

              }

          }
          
          if (!$checkError) {
              
              $this->setView('edit-page');
              $this->setPageTitle('Add New Page');
              $this->setFormAction(ActionConst::NEWPAGE);
              $this->view->set('pageTitle', $this->getPageTitle());
              $this->view->set('formAction', $this->getFormAction());
              $this->view->set('errors', $errors);
              $this->view->set('formData', $_POST);
              $this->view->set('medialibs', $medialib->imageUploadHandler());
              $this->view->set('postStatus', $this->pageEvent->postStatusDropDown());
              $this->view->set('commentStatus', $this->pageEvent->commentStatusDropDown());
              $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
              
          } else {
              
            if(!empty($file_location)) {

                $new_filename = generate_filename($file_name)['new_filename'];
                $file_extension = generate_filename($file_name)['file_extension'];
                list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : null;

                if( $file_extension == "jpeg" || $file_extension == "jpg" || $file_extension == "png" || $file_extension == "gif" || $file_extension == "webp" ) {

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

                // upload image
                if( is_uploaded_file($file_location)) {

                     upload_media($file_location, $file_type, $file_size, basename($new_filename));

                }

                $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';

                $bind_media = [
                    'media_filename' => $new_filename, 
                    'media_caption' => rename_file($file_name), 
                    'media_type' => $file_type, 
                    'media_target' => 'page', 
                    'media_user' => $this->postEvent->postAuthorLevel(), 
                    'media_access' => $media_access, 
                    'media_status' => '1'];

                $append_media = $medialib->createMedia($bind_media);

                $mediameta = [
                    'media_id' => $append_media,
                    'meta_key' => $new_filename,
                    'meta_value' => json_encode($media_metavalue)
                ];

                $medialib->createMediaMeta($mediameta);

                $this->pageEvent->setPageImage($append_media);

            } 
              
              $this->pageEvent->setPageAuthor((int)$this->pageEvent->pageAuthorId());
              $this->pageEvent->setPageTitle(distill_post_request($filters)['post_title']);
              $this->pageEvent->setPageSlug(distill_post_request($filters)['post_title']);
              $this->pageEvent->setPageContent(distill_post_request($filters)['post_content']);
              $this->pageEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
              $this->pageEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);
              $this->pageEvent->setPublish(distill_post_request($filters)['post_status']);
              $this->pageEvent->setSticky(distill_post_request($filters)['post_sticky']);
              $this->pageEvent->setComment(distill_post_request($filters)['comment_status']);
              $this->pageEvent->setPostType('page');
              
              $this->pageEvent->addPage();
              direct_page('index.php?load=pages&status=pageAdded', 200);

        }
          
      } catch (AppException $e) {
          
          LogError::setStatusCode(http_response_code());
          LogError::newMessage($e);
          LogError::customErrorMessage('admin');
          
      }
      
  } else {
      
      $this->setView('edit-page');
      $this->setPageTitle('Add New Page');
      $this->setFormAction(ActionConst::NEWPAGE);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('medialibs', $medialib->imageUploadHandler());
      $this->view->set('postStatus', $this->pageEvent->postStatusDropDown());
      $this->view->set('commentStatus', $this->pageEvent->commentStatusDropDown());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
      
  }
  
  return $this->view->render();
  
 }
 
 public function update($id)
 {
   
   $medialib = new MediaDao();
   $errors = array();
   $checkError = true;
   
   if (!$getPage = $this->pageEvent->grabPage($id, 'page')) {
       direct_page('index.php?load=pages&error=pageNotFound', 404);
   }
   
   $data_page = array(
       'ID' => $getPage['ID'],
       'media_id' => $getPage['media_id'],
       'post_title' => $getPage['post_title'],
       'post_content' => $getPage['post_content'],
       'post_summary' => $getPage['post_summary'],
       'post_keyword' => $getPage['post_keyword'],
       'post_status' => $getPage['post_status'],
       'post_sticky' => $getPage['post_sticky'],
       'comment_status' => $getPage['comment_status']
   );
   
   if (isset($_POST['pageFormSubmit'])) {
       
      $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
      $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
      $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
      $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
      $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

      $filters = [
        'post_id' => FILTER_SANITIZE_NUMBER_INT,
        'post_title'=>FILTER_SANITIZE_SPECIAL_CHARS,
        'post_content'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_summary'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_keyword'=>FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_status'=>FILTER_SANITIZE_STRING,
        'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
        'comment_status'=>FILTER_SANITIZE_STRING
      ];

      $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_content' => 50000];

       try {
           
           if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
               
               header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
               throw new AppException("Sorry, unpleasant attempt detected!");
               
           }

           if( check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_summary', 'post_keyword', 'post_status', 'comment_status']) == false) {

              header(APP_PROTOCOL.' 413 Payload Too Large');
              header('Status: 413 Payload Too Large');
              header('Retry-After: 3600');
              throw new AppException("Sorry, Unpleasant attempt detected");

           }
           
           if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
               
               $checkError = false;
               array_push($errors, "Please enter a required field");
               
           }
           
           if(!empty($file_location)) {

              if(!isset($file_error) || is_array($file_error)) {

                  $checkError = false;
                  array_push($errors, "Invalid parameter");

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

              if($file_size > APP_FILE_SIZE) {

                  $checkError = false;
                  array_push($errors, "Exceeded filesize limit. Maximum file size is. ".format_size_unit(APP_FILE_SIZE));

              }

              if(true === check_file_length($file_location)) {

                  $checkError = false;
                  array_push($errors, "File name is too long");

              }

              if(false === check_file_name($file_location)) {

                  $checkError = false;
                  array_push($errors, "File name is not valid");

              }

              if((false === check_mime_type(mime_type_dictionary(), $file_location)) || (false === check_file_extension($file_name))) {

                  $checkError = false;
                  array_push($errors, "Invalid file format");

              }

           }

           if (!$checkError) {
               
               $this->setView('edit-page');
               $this->setPageTitle('Edit Page');
               $this->setFormAction(ActionConst::EDITPAGE);
               $this->view->set('pageTitle', $this->getPageTitle());
               $this->view->set('formAction', $this->getFormAction());
               $this->view->set('errors', $errors);
               $this->view->set('pageData', $data_page);
               $this->view->set('medialibs', $medialib->imageUploadHandler($getPage['media_id']));
               $this->view->set('postStatus', $this->pageEvent->postStatusDropDown($getPage['post_status']));
               $this->view->set('commentStatus', $this->pageEvent->commentStatusDropDown($getPage['comment_status']));
               $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
               
           } else {
               
               if(!empty($file_location)) {

                $new_filename = generate_filename($file_name)['new_filename'];
                $file_extension = generate_filename($file_name)['file_extension'];

                list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : null;

                if($file_extension == "jpeg" || $file_extension == "jpg" || $file_extension == "png" || $file_extension == "gif" || $file_extension == "webp") {

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

                if(is_uploaded_file($file_location)) {

                    upload_media($file_location, $file_type, $file_size, basename($new_filename));

                }

                $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';

                $bind_media = [
                  'media_filename' => $new_filename, 
                  'media_caption' => prevent_injection(distill_post_request($filters)['post_title']), 
                  'media_type' => $file_type, 
                  'media_target' => 'page', 
                  'media_user' => $this->postEvent->postAuthorId(), 
                  'media_access' => $media_access, 
                  'media_status' => '1'];

                $append_media = $medialib->createMedia($bind_media);

                $mediameta = [
                    'media_id' => $append_media,
                    'meta_key' => $new_filename,
                    'meta_value' => json_encode($media_metavalue)
                ];

                $medialib->createMediaMeta($mediameta);

                $this->pageEvent->setPageImage($append_media);
                  
               } 

                $this->pageEvent->setPageId((int)distill_post_request($filters)['post_id']);
                $this->pageEvent->setPageTitle(distill_post_request($filters)['post_title']);
                $this->pageEvent->setPageSlug(distill_post_request($filters)['post_title']);
                $this->pageEvent->setPageContent(distill_post_request($filters)['post_content']);
                $this->pageEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
                $this->pageEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);
                $this->pageEvent->setPublish(distill_post_request($filters)['post_status']);
                $this->pageEvent->setPostType('page');
                $this->pageEvent->setComment(distill_post_request($filters)['comment_status']);

               $this->pageEvent->modifyPage();
               direct_page('index.php?load=pages&status=pageUpdated', 200);
               
           }
           
       } catch (AppException $e) {
           
           LogError::setStatusCode(http_response_code());
           LogError::newMessage($e);
           LogError::customErrorMessage('admin');
           
       }
       
   } else {
    
      $this->setView('edit-page');
      $this->setPageTitle('Edit Page');
      $this->setFormAction(ActionConst::EDITPAGE);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('pageData', $data_page);
      $this->view->set('medialibs', $medialib->imageUploadHandler($getPage['media_id']));
      $this->view->set('postStatus', $this->pageEvent->postStatusDropDown($getPage['post_status']));
      $this->view->set('commentStatus', $this->pageEvent->commentStatusDropDown($getPage['comment_status']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
       
   }
   
   return $this->view->render();
   
 }
 
 public function remove($id)
 {
   $this->pageEvent->setPageId($id);
   $this->pageEvent->removePage();
   direct_page('index.php?load=pages&status=pageDeleted', 200);
 }
 
 protected function setView($viewName)
 {
   $this->view = new View('admin', 'ui', 'pages', $viewName);
 }
 
}