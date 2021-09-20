<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class PostApp
 *
 * @category  Class PostApp extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PostApp extends BaseApp
{
  
/**
 * an instance of View
 * 
 * @var object
 * 
 */
  private $view;

/**
 * an instance of PostEvent
 * 
 * @var object
 * 
 */
  private $postEvent;
    
/**
 * Initialize instance of object properties and method
 * 
 * @param object $postEvent
 * 
 */
  public function __construct(PostEvent $postEvent)
  {
    
    $this->postEvent = $postEvent;
   
  }
  
  /**
   * Retrieve all posts
   *  
   * {@inheritDoc}
   * @see BaseApp::listItems()
   */
  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
    
    if (isset($_GET['error'])) {
        $checkError = false;
        if ($_GET['error'] == 'postNotFound') array_push($errors, "Error: Post Not Found!");
    }
    
    if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'postAdded') array_push($status, "New post added");
        if ($_GET['status'] == 'postUpdated') array_push($status, "Post updated");
        if ($_GET['status'] == 'postDeleted') array_push($status, "Post deleted");
    }
   
    $this->setView('all-posts');
    $this->setPageTitle('Posts');
    $this->view->set('pageTitle', $this->getPageTitle());
    
    if (!$checkError) {
        $this->view->set('errors', $errors);
    } 
    
    if ($checkStatus) {
        $this->view->set('status', $status);
    }
    
    if ($this->postEvent->postAuthorLevel() == 'administrator') {

      $this->view->set('postsTotal', $this->postEvent->totalPosts());
      $this->view->set('posts', $this->postEvent->grabPosts());

    } else {

      $this->view->set('postsTotal', $this->postEvent->totalPosts([$this->postEvent->postAuthorId()]));
      $this->view->set('posts', $this->postEvent->grabPosts('ID', $this->postEvent->postAuthorId()));
      
    }
    
    return $this->view->render();
    
  }
  
  /**
   * Insert new post
   * 
   * {@inheritDoc}
   * @see BaseApp::insert()
   * 
   */
  public function insert()
  {
    
    $topics = new TopicDao();
    $medialib = new MediaDao();
    $errors = array();
    $checkError = true;
    
    if (isset($_POST['postFormSubmit'])) {

        $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
        $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
        $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
        $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
        $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

        $filters = [
          'post_title' => FILTER_SANITIZE_SPECIAL_CHARS,
          'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_date' => FILTER_SANITIZE_STRING,
          'image_id' => FILTER_SANITIZE_NUMBER_INT,
          'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
          'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_keyword' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_status' => FILTER_SANITIZE_STRING,
          'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
          'comment_status' => FILTER_SANITIZE_STRING
       ];

       $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_content' => 50000];

       $new_filename = generate_filename($file_name)['new_filename'];
       $file_extension = generate_filename($file_name)['file_extension'];
       
      try {

         if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
            header($_SERVER["SERVER_PROTOCOL"].' 400 Bad Request', true, 400);
            header('Status: 400 Bad Request');
            throw new AppException("Sorry, unpleasant attempt detected!");
             
         } 
        
         if ( check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_date', 'image_id', 'catID', 'post_summary', 'post_keyword', 'post_tags', 'post_status', 'post_sticky', 'comment_status']) == false) {

            header($_SERVER["SERVER_PROTOCOL"].' 413 Payload Too Large', true, 413);
            header('Status: 413 Payload Too Large');
            header('Retry-After: 3600');
            throw new AppException("Sorry, Unpleasant attempt detected");

         }

         if ( ( empty($_POST['post_title']) ) || ( empty($_POST['post_content'] ) ) ) {
           
            $checkError = false;
            array_push($errors, "Please enter a required field");
            
         }

         if (true === form_size_validation($form_fields)) {

            $checkError = false;
            array_push($errors, "Form data is longer than allowed");

         }

         if (false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {

            $checkError = false;
            array_push($errors, "Please choose the available value provided");

         }

         if (false === sanitize_selection_box(distill_post_request($filters)['comment_status'], ['open' => 'Open', 'closed' => 'Closed'])) {

            $checkError = false;
            array_push($errors, "Please choose the available value provided");

         }
         
         if (!empty($_POST['post_date']) && validate_date($_POST['post_date']) === false) {

           $checkError = false;
           array_push($errors, "Please fix your date format");
           
         }

         if( !empty($file_location) ) {

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
              array_push($errors, "File name is not valid");
   
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
            
          $this->setView('edit-post');
          $this->setPageTitle('Add New Post');
          $this->setFormAction(ActionConst::NEWPOST);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('topics', $topics->setCheckBoxTopic());
       
         if ($this->postEvent->postAuthorLevel() == 'contributor') {

           $this->view->set('medialibs', $medialib->dropDownMediaSelect());

          } else {

           $this->view->set('medialibs', $medialib->imageUploadHandler());

          }

          $this->view->set('postStatus', PostEvent::postStatusDropDown());
          $this->view->set('commentStatus', PostEvent::commentStatusDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
        } else {

           if (empty($file_location)) {

              if ( (isset($_POST['image_id'])) && (!empty($_POST['image_id'])) ) {

                  $this->postEvent->setPostImage((int)distill_post_request($filters)['image_id']);

              }

           } else {

              list($width, $height) = ( !empty($file_location) ) ? getimagesize($file_location) : null;
       
              if ($file_extension == "jpeg" || $file_extension == "jpg" || $file_extension == "png" || $file_extension == "gif" || $file_extension == "webp") {
       
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

               if (is_uploaded_file($file_location)) {

                upload_media($file_location, $file_type, $file_size, basename($new_filename));
      
               }

              $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';
       
              $bind_media = [
                 'media_filename' => $new_filename, 
                 'media_caption' => prevent_injection(distill_post_request($filters)['post_title']), 
                 'media_type' => $file_type, 
                 'media_target' => 'blog', 
                 'media_user' => $this->postEvent->postAuthorLevel(), 
                 'media_access' => $media_access, 
                 'media_status' => '1'];
       
               $append_media = $medialib->createMedia($bind_media);

               $mediameta = [
                'media_id' => $append_media,
                'meta_key' => $new_filename,
                'meta_value' => json_encode($media_metavalue)];

               $medialib->createMediaMeta($mediameta);

               $this->postEvent->setPostImage($append_media);

          }

            if(isset($_POST['catID']) && $_POST['catID'] == 0) {

              $this->postEvent->setTopics(0);
              
            } else {

              $this->postEvent->setTopics(distill_post_request($filters)['catID']);
              
            }

            $this->postEvent->setPostAuthor((int)$this->postEvent->postAuthorId());
            
            if ( empty($_POST['post_date']) ) {

                $this->postEvent->setPostDate(date("Y-m-d H:i:s"));

            } else {

                $this->postEvent->setPostDate(distill_post_request($filters)['post_date']);

            }

            $this->postEvent->setPostTitle(distill_post_request($filters)['post_title']);
            $this->postEvent->setPostSlug(distill_post_request($filters)['post_title']);
            $this->postEvent->setPostContent(distill_post_request($filters)['post_content']);
            $this->postEvent->setPublish(distill_post_request($filters)['post_status']);

            if ( empty($_POST['post_sticky']) ) {
            
              $this->postEvent->setSticky(0);

            } else {

              $this->postEvent->setSticky(distill_post_request($filters)['post_sticky']);

            }
            
            $this->postEvent->setComment(distill_post_request($filters)['comment_status']);
            $this->postEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
            $this->postEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);
            $this->postEvent->setPostTags(distill_post_request($filters)['post_tags']);

            $this->postEvent->addPost();
      
            direct_page('index.php?load=posts&status=postAdded', 200);

        }
      
      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
         
      } catch (AppException $e) {
          
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
         
      }
    
    } else {
        
        $this->setView('edit-post');
        $this->setPageTitle('Add New Post');
        $this->setFormAction(ActionConst::NEWPOST);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('topics', $topics->setCheckBoxTopic());
        
        if ($this->postEvent->postAuthorLevel() == 'contributor') { 

            $this->view->set('medialibs', $medialib->dropDownMediaSelect());

        } else {

            $this->view->set('medialibs', $medialib->imageUploadHandler());

        }
        
        $this->view->set('postStatus', PostEvent::postStatusDropDown());
        $this->view->set('commentStatus', PostEvent::commentStatusDropDown());
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
    }
   
    return $this->view->render();
   
  }
  
  /**
   * update
   * 
   * {@inheritDoc}
   * @see BaseApp::update()
   * @param int|num $id
   * 
   */
  public function update($id)
  {
  
    $topics = new TopicDao();
    $medialib = new MediaDao();
    $errors = array();
    $checkError = true;
    
    if (!$getPost = $this->postEvent->grabPost($id)) {
        
        direct_page('index.php?load=posts&error=postNotFound', 404);
        
    }
    
    $data_post = array(
        'ID' => $getPost['ID'],
        'media_id' => $getPost['media_id'],
        'post_date' => $getPost['post_date'],
        'post_modified' => $getPost['post_modified'],
        'post_title' => $getPost['post_title'],
        'post_content' => $getPost['post_content'],
        'post_summary' => $getPost['post_summary'],
        'post_keyword' => $getPost['post_keyword'],
        'post_tags' => $getPost['post_tags'],
        'post_status' => $getPost['post_status'], 
        'post_sticky' => $getPost['post_sticky'],
        'comment_status' => $getPost['comment_status']
    );

    if (isset($_POST['postFormSubmit'])) {

       $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
       $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
       $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
       $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
       $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

       $filters = [
        'post_id' => FILTER_SANITIZE_NUMBER_INT,
        'post_title' => FILTER_SANITIZE_SPECIAL_CHARS,
        'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_modified' => FILTER_SANITIZE_STRING,
        'image_id' => FILTER_SANITIZE_NUMBER_INT,
        'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
        'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_keyword' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_status' => FILTER_SANITIZE_STRING,
        'post_sticky' => FILTER_SANITIZE_NUMBER_INT,
        'comment_status' => FILTER_SANITIZE_STRING
      ];

      $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_content' => 50000];

      $new_filename = generate_filename($file_name)['new_filename'];
      $file_extension = generate_filename($file_name)['file_extension'];

      try {

            if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Sorry, unpleasant attempt detected!");
                
            } 
            
            if( check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_modified', 'image_id', 'catID', 'post_summary', 'post_keyword', 'post_status', 'post_sticky', 'comment_status']) == false) {

                header($_SERVER["SERVER_PROTOCOL"]." 413 Payload Too Large", true, 413);
                header('Status: 413 Payload Too Large');
                header('Retry-After: 3600');
                throw new AppException("Sorry, Unpleasant attempt detected");

            }
            
            if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {
           
               $checkError = false;
               array_push($errors, "Please enter a required field");
              
            }
            
            if( true === form_size_validation($form_fields)) {

              $checkError = false;
              array_push($errors, "Form data is longer than allowed");
  
            }

            if ( false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {

              $checkError = false;
              array_push($errors, "Please choose the available value provided");
  
            }
  
            if ( false === sanitize_selection_box(distill_post_request($filters)['comment_status'], ['open' => 'Open', 'closed' => 'Closed'])) {
  
              $checkError = false;
              array_push($errors, "Please choose the available value provided");
              
            }

            if (!empty($_POST['post_modfied']) && validate_date($_POST['post_modified']) === false) {

              $checkError = false;
              array_push($errors, "Please fix your date format");
              
            }
            
            if( !empty($file_location) ) {

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

                if (true === check_file_length($file_location)) {
           
                   $checkError = false;
                   array_push($errors, "File name is too long");
        
                }

                if (false === check_file_name($file_location)) {
           
                   $checkError = false;
                   array_push($errors, "File name is not valid");
        
                }

                if (is_uploaded_file($file_location)) {

                  if ((false === check_mime_type(mime_type_dictionary(), $file_location)) || (false === check_file_extension($file_name))) {
           
                    $checkError = false;
                    array_push($errors, "Invalid file format");
                   
                  } else {
  
                    upload_media($file_location, $file_type, $file_size, basename($new_filename));

                  }

                }
                
            }

            if (!$checkError) {
                
                $this->setView('edit-post');
                $this->setPageTitle('Edit Post');
                $this->setFormAction(ActionConst::EDITPOST);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('postData', $data_post);
                $this->view->set('topics', $topics->setCheckBoxTopic($getPost['ID']));

                if ($this->postEvent->postAuthorLevel() == 'contributor') {

                  $this->view->set('medialibs', $medialib->dropDownMediaSelect($getPost['media_id']));
       
                } else {
       
                   $this->view->set('medialibs', $medialib->imageUploadHandler($getPost['media_id']));
       
                }

                $this->view->set('postStatus', PostEvent::postStatusDropDown($getPost['post_status']));
                $this->view->set('commentStatus', PostEvent::commentStatusDropDown($getPost['comment_status']));
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                
            } else {
              
              if( empty($file_location) ) {

                 if (  (isset($_POST['image_id'])) && (!empty($_POST['image_id'])) ) {

                    $this->postEvent->setPostImage((int)distill_post_request($filters)['image_id']);
   
                 }

              } else {

                list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : null;

                if ($file_extension == "jpeg" || $file_extension == "jpg" || $file_extension == "png" || $file_extension == "gif" || $file_extension == "webp") {

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

               $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';

               $bind_media = [
                  'media_filename' => $new_filename, 
                  'media_caption' => prevent_injection(distill_post_request($filters)['post_title']), 
                  'media_type' => $file_type, 
                  'media_target' => 'blog', 
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

                $this->postEvent->setPostImage($append_media);
               
              }
            
              $this->postEvent->setPostId((int)distill_post_request($filters)['post_id']);
              $this->postEvent->setTopics(distill_post_request($filters)['catID']);
              
              if ( empty($_POST['post_modified']) ) {

                $this->postEvent->setPostModified(date("Y-m-d H:i:s"));

              } else {

                $this->postEvent->setPostModified(distill_post_request($filters)['post_modified']);
                 
              }

              $this->postEvent->setPostAuthor($this->postEvent->postAuthorId());
              $this->postEvent->setPostTitle(distill_post_request($filters)['post_title']);
              $this->postEvent->setPostSlug(distill_post_request($filters)['post_title']);
              $this->postEvent->setPostContent(distill_post_request($filters)['post_content']);
              $this->postEvent->setPublish(distill_post_request($filters)['post_status']);

              if ( empty($_POST['post_sticky']) ) {

                $this->postEvent->setSticky(0);

              } else {

                $this->postEvent->setSticky(distill_post_request($filters)['post_sticky']);

              }
              
              $this->postEvent->setComment(distill_post_request($filters)['comment_status']);
              $this->postEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
              $this->postEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);
              $this->postEvent->setPostTags(distill_post_request($filters)['post_tags']);
               
              $this->postEvent->modifyPost();
                
              direct_page('index.php?load=posts&status=postUpdated', 200);
                
            }
            
        } catch (Throwable $th) {

          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($th);

        } catch (AppException $e) {
   
          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($e);
            
        }
        
    } else {
   
        $this->setView('edit-post');
        $this->setPageTitle('Edit Post');
        $this->setFormAction(ActionConst::EDITPOST);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('postData', $data_post);
        $this->view->set('topics', $topics->setCheckBoxTopic($getPost['ID']));

        if ($this->postEvent->postAuthorLevel() == 'contributor') {

          $this->view->set('medialibs', $medialib->dropDownMediaSelect($getPost['media_id']));

        } else {

           $this->view->set('medialibs', $medialib->imageUploadHandler($getPost['media_id']));

        }

        $this->view->set('postStatus', PostEvent::postStatusDropDown($getPost['post_status']));
        $this->view->set('commentStatus', PostEvent::commentStatusDropDown($getPost['comment_status']));
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
    }
      
    return $this->view->render();
    
  }
  
  /**
   * remove
   * 
   * {@inheritDoc}
   * @see BaseApp::remove()
   * 
   */
  public function remove($id)
  {
    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getPost = $this->postEvent->grabPost($id);

      try {
        
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$getPost) {

           $checkError = false;
           array_push($errors, "Error: Post not found!");

        }

        if (!$checkError) {

          $this->setView('all-posts');
          $this->setPageTitle('Post not found');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('errors', $errors);

          if ($this->postEvent->postAuthorLevel() == 'administrator') {

            $this->view->set('postsTotal', $this->postEvent->totalPosts());
            $this->view->set('posts', $this->postEvent->grabPosts());
      
          } else {
      
            $this->view->set('postsTotal', $this->postEvent->totalPosts([$this->postEvent->postAuthorId()]));
            $this->view->set('posts', $this->postEvent->grabPosts('ID', $this->postEvent->postAuthorId()));
            
          }

          return $this->view->render();

        } else {

          $this->postEvent->setPostId($id);
          $this->postEvent->removePost();  
          direct_page('index.php?load=posts&status=postDeleted', 200);
           
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
    
/**
 * Set View
 * 
 * @param object $viewName
 * 
 */
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'posts', $viewName);
  }
  
}