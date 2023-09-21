<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class PostApp extends BaseApp
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
   * @var object
   */
  private $view;

  /**
   * an instance of PostEvent
   * @var object
   */
  private $postEvent;

  /**
   * Credential
   *
   * @var array
   */
  private $crendential = [];

  /**
   * Initialize instance of object properties and method
   * @param object $postEvent
   */
  public function __construct(PostEvent $postEvent)
  {

    $this->postEvent = $postEvent;
  }

  /**
   * Retrieve all posts
   * {@inheritDoc}
   * @uses BaseApp::listItems()
   */
  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_SESSION['error'])) {
      $checkError = false;
      ($_SESSION['error'] == 'postNotFound') ? array_push($errors, "Error: Post Not Found!") : "";
      unset($_SESSION['error']);
    }

    if (isset($_SESSION['status'])) {
      $checkStatus = true;
      ($_SESSION['status'] == 'postAdded') ? array_push($status, "New post added") : "";
      ($_SESSION['status'] == 'postUpdated') ? array_push($status, "Post updated") : "";
      ($_SESSION['status'] == 'postDeleted') ? array_push($status, "Post deleted") : "";

      unset($_SESSION['status']);

      if (isset($_SESSION['post_protected'])) {
        unset($_SESSION['post_protected']);
      }
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
   * {@inheritDoc}
   * @see BaseApp::insert()
   */
  public function insert()
  {

    $topics = new TopicDao();
    $medialib = new MediaDao();
    $errors = array();
    $checkError = true;
    $user_level = $this->postEvent->postAuthorLevel();
   
    if (isset($_POST['postFormSubmit'])) {

      $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
      $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
      $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
      $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
      $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

      $filters = [
        'post_title' => isset($_POST['post_title']) ? Sanitize::severeSanitizer($_POST['post_title']) : "",
        'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_date' => isset($_POST['post_date']) ? Sanitize::mildSanitizer($_POST['post_date']) : "",
        'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
        'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
        'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_keyword' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
        'visibility' => isset($_POST['visibility']) ? Sanitize::mildSanitizer($_POST['visibility']) : "",
        'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
        'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
        'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : ""
      ];

      $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_tags' => 200, 'post_content' => 50000];

      $new_filename = generate_filename($file_name)['new_filename'];
      $file_extension = generate_filename($file_name)['file_extension'];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

          header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
          header('Status: 400 Bad Request');
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }

        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_date', 'image_id', 'catID', 'post_summary', 'post_keyword', 'post_tags', 'post_status', 'post_headlines', 'visibility', 'comment_status']) === false) {

          header($_SERVER["SERVER_PROTOCOL"] . ' 413 Payload Too Large', true, 413);
          header('Status: 413 Payload Too Large');
          header('Retry-After: 3600');
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }

        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {

          $checkError = false;
          array_push($errors, "Please enter a required field");
        }

        if (true === form_size_validation($form_fields)) {

          $checkError = false;
          array_push($errors, "Form data is longer than allowed");
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['comment_status'], ['open' => 'Open', 'closed' => 'Closed'])) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['visibility'], ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'])) {
          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (!empty($_POST['post_date']) && validate_date($_POST['post_date']) === false) {

          $checkError = false;
          array_push($errors, "Please fix your date format");
        }

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
            array_push($errors, "Exceeded file size limit. Maximum file size is. " . format_size_unit(APP_FILE_SIZE));
          }

          if (false === check_file_name($file_location)) {

            $checkError = false;
            array_push($errors, "File name is not valid");
          }

          if (true === check_file_length($file_location)) {

            $checkError = false;
            array_push($errors, "File name is too long");
          }

          if ((false === check_mime_type(mime_type_dictionary(), $file_location)) || (false === check_file_extension($file_name))) {

            $checkError = false;
            array_push($errors, "Invalid file format");
          }
        }

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {

          if (check_common_password($_POST['post_password']) === true) {

            $checkError = false;
            array_push($errors, "Your password seems to be the most hacked password, please try another");
          }

          if (false === check_pwd_strength($_POST['post_password'])) {

            $checkError = false;
            array_push($errors, MESSAGE_WEAK_PASSWORD);
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

          if ($user_level === 'contributor') {

            $this->view->set('medialibs', $medialib->dropDownMediaSelect());
          } else {

            $this->view->set('medialibs', $medialib->imageUploadHandler());
          }

          $this->view->set('postStatus', $this->postEvent->postStatusDropDown());
          $this->view->set('commentStatus', $this->postEvent->commentStatusDropDown());
          $this->view->set('postVisibility', $this->postEvent->visibilityDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          list($width, $height) = ($file_location) ? getimagesize($file_location) : getimagesize(app_url() . '/public/files/pictures/nophoto.jpg');

          $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] === 'publish')) ? 'public' : 'private';

          if (empty($file_location)) {

            if (isset($_POST['image_id'])) {

              $this->postEvent->setPostImage((int)distill_post_request($filters)['image_id']);
              
            } else {

              clearstatcache();

              $media_metavalue = array(
                'Origin' => "nophoto.jpg",
                'File type' => "image/jpg",
                'File size' => format_size_unit(filesize(__DIR__ . '/../../' . APP_IMAGE . "nophoto.jpg")),
                'Uploaded at' => date("Y-m-d H:i:s"),
                'Dimension' => $width . 'x' . $height
              );

              $bind_media = [
                'media_filename' => "nophoto.jpg",
                'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
                'media_type' => "image/jpg",
                'media_target' => 'blog',
                'media_user' => $user_level,
                'media_access' => $media_access,
                'media_status' => '1'
              ];

              $append_media = $medialib->createMedia($bind_media);

              $mediameta = [
                'media_id' => $append_media,
                'meta_key' => "nophoto.jpg",
                'meta_value' => json_encode($media_metavalue)
              ];

              $medialib->createMediaMeta($mediameta);

              $this->postEvent->setPostImage($append_media);
            }
            
          } else {

            if ($file_extension === "jpeg" || $file_extension === "jpg" || $file_extension === "png" || $file_extension === "gif" || $file_extension === "webp" || $file_extension === "bmp") {

              $media_metavalue = array(
                'Origin' => rename_file($file_name),
                'File type' => $file_type,
                'File size' => format_size_unit($file_size),
                'Uploaded at' => date("Y-m-d H:i:s"),
                'Dimension' => $width . 'x' . $height
              );
            }

            if (is_uploaded_file($file_location)) {

              upload_media($file_location, $file_type, $file_size, basename($new_filename));
            }

            $bind_media = [
              'media_filename' => $new_filename,
              'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
              'media_type' => $file_type,
              'media_target' => 'blog',
              'media_user' => $user_level,
              'media_access' => $media_access,
              'media_status' => '1'
            ];

            $append_media = $medialib->createMedia($bind_media);

            $mediameta = [
              'media_id' => $append_media,
              'meta_key' => $new_filename,
              'meta_value' => json_encode($media_metavalue)
            ];

            $medialib->createMediaMeta($mediameta);

            $this->postEvent->setPostImage($append_media);
          }

          if (isset($_POST['catID']) && $_POST['catID'] == 0) {

            $this->postEvent->setTopics(0);
          } else {

            $this->postEvent->setTopics(distill_post_request($filters)['catID']);
          }

          $this->postEvent->setPostAuthor((int)$this->postEvent->postAuthorId());

          if (empty($_POST['post_date'])) {
             
            $this->postEvent->setPostDate(date_for_database());
          } else {

            $this->postEvent->setPostDate(date_for_database(distill_post_request($filters)['post_date']));
          }

          $this->postEvent->setPostTitle(distill_post_request($filters)['post_title']);
          $this->postEvent->setPostSlug(distill_post_request($filters)['post_title']);

          if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {

            (!empty($_POST['post_password'])) ? $this->postEvent->setProtected(protect_post(distill_post_request($filters)['post_content'], distill_post_request($filters)['visibility'], distill_post_request($filters)['post_password'])) : "";

            $this->postEvent->setPostContent(protect_post(distill_post_request($filters)['post_content'], distill_post_request($filters)['visibility'], distill_post_request($filters)['post_password'])['post_content']);
            $this->postEvent->setPassPhrase(distill_post_request($filters)['post_password']);

            $_SESSION['post_protected'] = (!isset($_SESSION['post_protected'])) ? distill_post_request($filters)['post_password'] : "";
          } else {

            $this->postEvent->setPostContent(distill_post_request($filters)['post_content']);
          }

          $this->postEvent->setPublish(distill_post_request($filters)['post_status']);
          $this->postEvent->setVisibility(distill_post_request($filters)['visibility']);

          if (empty($_POST['post_headlines'])) {

            $this->postEvent->setHeadlines(0);
          } else {

            $this->postEvent->setHeadlines(distill_post_request($filters)['post_headlines']);
          }

          $this->postEvent->setComment(distill_post_request($filters)['comment_status']);
          $this->postEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
          $this->postEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);

          if (isset($_POST['post_tags'])) {

            $this->postEvent->setPostTags(distill_post_request($filters)['post_tags']);
          }

          $postId = $this->postEvent->addPost();

          if (isset($_SESSION['post_protected']) && $postId > 0) {

            $values = [
              'post_id' => $postId,
              'post_author' => $this->postEvent->postAuthorId(),
              'post_date' => date_for_database(distill_post_request($filters)['post_date']),
              'post_password' => distill_post_request($filters)['post_password'],
              'passpharse' => distill_post_request($filters)['post_password']
            ];

            save_post_protected($this->setCredential($values));
          }

          $_SESSION['status'] = "postAdded";
          direct_page('index.php?load=posts&status=postAdded', 200);
        }
      } catch (\Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
      }
    } else {

      $this->setView('edit-post');
      $this->setPageTitle('Add new post');
      $this->setFormAction(ActionConst::NEWPOST);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('topics', $topics->setCheckBoxTopic());

      if ($user_level === 'contributor') {

        $this->view->set('medialibs', $medialib->dropDownMediaSelect());
      } else {

        $this->view->set('medialibs', $medialib->imageUploadHandler());
      }

      $this->view->set('postStatus', $this->postEvent->postStatusDropDown());
      $this->view->set('commentStatus', $this->postEvent->commentStatusDropDown());
      $this->view->set('postVisibility', $this->postEvent->visibilityDropDown());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
    }

    return $this->view->render();
  }

  /**
   * update()
   *
   * @param num|int $id
   * 
   */
  public function update($id)
  {

    $topics = new TopicDao();
    $medialib = new MediaDao();
    $errors = array();
    $checkError = true;
    $user_level = $this->postEvent->postAuthorLevel();

    if (!$getPost = $this->postEvent->grabPost($id)) {

      $_SESSION['error'] = "postNotFound";
      direct_page('index.php?load=posts&error=postNotFound', 404);
    }

    $data_post = array(
      'ID' => $getPost['ID'],
      'media_id' => $getPost['media_id'],
      'post_author' => $getPost['post_author'],
      'post_date' => $getPost['post_date'],
      'post_modified' => $getPost['post_modified'],
      'post_title' => $getPost['post_title'],
      'post_content' => $getPost['post_content'],
      'post_summary' => $getPost['post_summary'],
      'post_keyword' => $getPost['post_keyword'],
      'post_status' => $getPost['post_status'],
      'post_visibility' => $getPost['post_visibility'],
      'post_password' => $getPost['post_password'],
      'post_tags' => $getPost['post_tags'],
      'post_headlines' => $getPost['post_headlines'],
      'comment_status' => $getPost['comment_status'],
      'passphrase' => $getPost['passphrase']
    );
    
    $postId = isset($getPost['ID']) ? $getPost['ID'] : 0;

    $timezone = function_exists('timezone_identifier') ? timezone_identifier() : "";
    
    (function_exists('date_default_timezone_set')) ? date_default_timezone_set($timezone) : "";

    if (isset($_POST['postFormSubmit'])) {

      $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
      $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
      $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
      $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
      $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

      $filters = [
        'post_id' => FILTER_SANITIZE_NUMBER_INT,
        'post_title' => isset($_POST['post_title']) ? FILTER_SANITIZE_SPECIAL_CHARS : "",
        'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_modified' => isset($_POST['post_modified']) ? Sanitize::mildSanitizer($_POST['post_modified']) : "",
        'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
        'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
        'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_keyword' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
        'visibility' => isset($_POST['visibility']) ?  Sanitize::mildSanitizer($_POST['visibility']) : "",
        'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
        'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
        'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "",
      ];

      $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_keyword' => 200, 'post_tags' => 200, 'post_content' => 50000];

      $new_filename = generate_filename($file_name)['new_filename'];
      $file_extension = generate_filename($file_name)['file_extension'];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

          header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }

        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_modified', 'image_id', 'catID', 'post_summary', 'post_keyword', 'post_status', 'post_headlines', 'visibility', 'comment_status']) === false) {

          header($_SERVER["SERVER_PROTOCOL"] . " 413 Payload Too Large", true, 413);
          header('Status: 413 Payload Too Large');
          header('Retry-After: 3600');
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }

        if ((empty($_POST['post_title'])) || (empty($_POST['post_content']))) {

          $checkError = false;
          array_push($errors, "Please enter a required field");
        }

        if (true === form_size_validation($form_fields)) {

          $checkError = false;
          array_push($errors, "Form data is longer than allowed");
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['post_status'], ['publish' => 'Publish', 'draft' => 'Draft'])) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['comment_status'], ['open' => 'Open', 'closed' => 'Closed'])) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['visibility'], ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'])) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if (!empty($_POST['post_modfied']) && validate_date($_POST['post_modified']) === false) {

          $checkError = false;
          array_push($errors, "Please fix your date format");
        }

        if (!empty($file_location)) {

          if ((!isset($file_error)) || (is_array($file_error))) {

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
            array_push($errors, "Exceeded file size limit. Maximum file size is. " . format_size_unit(APP_FILE_SIZE));
          }

          if (true === check_file_length($file_location)) {

            $checkError = false;
            array_push($errors, "File name is too long");
          }

          if (false === check_file_name($file_location)) {

            $checkError = false;
            array_push($errors, "File name is not valid");
          }

          if ((false === check_mime_type(mime_type_dictionary(), $file_location)) || (false === check_file_extension($file_name))) {

            $checkError = false;
            array_push($errors, "Invalid file format");
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

          if ($user_level === 'contributor') {

            $this->view->set('medialibs', $medialib->dropDownMediaSelect($getPost['media_id']));
          } else {

            $this->view->set('medialibs', $medialib->imageUploadHandler($getPost['media_id']));
          }

          $this->view->set('postStatus', $this->postEvent->postStatusDropDown($getPost['post_status']));
          $this->view->set('commentStatus', $this->postEvent->commentStatusDropDown($getPost['comment_status']));
          $this->view->set('postVisibility', $this->postEvent->visibilityDropDown($getPost['post_visibility']));
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->postEvent->setPostId((int)distill_post_request($filters)['post_id']);
          $this->postEvent->setPostAuthor($this->postEvent->postAuthorId());
          $this->postEvent->setPostTitle(distill_post_request($filters)['post_title']);
          $this->postEvent->setPostSlug(distill_post_request($filters)['post_title']);
          $this->postEvent->setPublish(distill_post_request($filters)['post_status']);
          
          if (isset($_POST['catID']) && $_POST['catID'] == 0) {

            $this->postEvent->setTopics(0);

          } else {

            $this->postEvent->setTopics(distill_post_request($filters)['catID']);
          }

          list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : getimagesize(app_url().'/public/files/pictures/nophoto.jpg');

          $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';

          if (empty($file_location)) {

            if (isset($_POST['image_id'])) {

              $this->postEvent->setPostImage((int)distill_post_request($filters)['image_id']);

            } else {
              
              clearstatcache();
            
              $media_metavalue = array(
                'Origin' => "nophoto.jpg",
                'File type' => "image/jpg",
                'File size' => format_size_unit(filesize(__DIR__ . '/../../'.APP_IMAGE."nophoto.jpg")),
                'Uploaded at' => date("Y-m-d H:i:s"),
                'Dimension' => $width . 'x' . $height
              );

              $bind_media = [
                'media_filename' => "nophoto.jpg",
                'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
                'media_type' => "image/jpg",
                'media_target' => 'blog',
                'media_user' => $user_level,
                'media_access' => $media_access,
                'media_status' => '1'
              ];
            
              $append_media = $medialib->createMedia($bind_media);

              $mediameta = [
                'media_id' => $append_media,
                'meta_key' => "nophoto.jpg",
                'meta_value' => json_encode($media_metavalue)
              ];

              $medialib->createMediaMeta($mediameta);

              $this->postEvent->setPostImage($append_media);

            }

          } else {

            if ($file_extension === "jpeg" || $file_extension === "jpg" 
                || $file_extension === "png" || $file_extension === "gif" || $file_extension === "webp" 
                || $file_extension === "bmp") {

              $media_metavalue = array(
                'Origin' => rename_file($file_name),
                'File type' => $file_type,
                'File size' => format_size_unit($file_size),
                'Uploaded at' => date("Y-m-d H:i:s"),
                'Dimension' => $width . 'x' . $height
              );
              
            } 

            if (is_uploaded_file($file_location)) {

              upload_media($file_location, $file_type, $file_size, basename($new_filename));
            }

            $bind_media = [
              'media_filename' => $new_filename,
              'media_caption' => prevent_injection(distill_post_request($filters)['post_title']),
              'media_type' => $file_type,
              'media_target' => 'blog',
              'media_user' => $user_level,
              'media_access' => $media_access,
              'media_status' => '1'
            ];

            $append_media = $medialib->createMedia($bind_media);

            $mediameta = [
              'media_id' => $append_media,
              'meta_key' => $new_filename,
              'meta_value' => json_encode($media_metavalue)
            ];

            $medialib->createMediaMeta($mediameta);

            $this->postEvent->setPostImage($append_media);

          }

          $this->postEvent->setComment(distill_post_request($filters)['comment_status']);
          $this->postEvent->setMetaDesc(distill_post_request($filters)['post_summary']);
          $this->postEvent->setMetaKeys(distill_post_request($filters)['post_keyword']);
          $this->postEvent->setPostTags(distill_post_request($filters)['post_tags']);
         
          if (empty($_POST['post_modified'])) {

            $this->postEvent->setPostModified(date_for_database());

          } else {

            $this->postEvent->setPostModified(date_for_database(distill_post_request($filters)['post_modified']));
          }

          if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {

            if (!empty($_POST['post_password'])) {

              $this->postEvent->setProtected(protect_post(distill_post_request($filters)['post_content'], distill_post_request($filters)['visibility'], distill_post_request($filters)['post_password']));

            }

            $this->postEvent->setVisibility(distill_post_request($filters)['visibility']);
            $this->postEvent->setPostContent(protect_post(distill_post_request($filters)['post_content'], distill_post_request($filters)['visibility'], distill_post_request($filters)['post_password'])['post_content']);
            $this->postEvent->setPassPhrase(distill_post_request($filters)['post_password']);

            $_SESSION['post_protected'] = (!isset($_SESSION['post_protected'])) ? distill_post_request($filters)['post_password'] : "";
            
          } else {

            $this->postEvent->setVisibility(distill_post_request($filters)['visibility']);
            $this->postEvent->setPostContent(distill_post_request($filters)['post_content']);

          }

          if (empty($_POST['post_headlines'])) {

            $this->postEvent->setHeadlines(0);
            
          } else {

            $this->postEvent->setHeadlines(distill_post_request($filters)['post_headlines']);
          }

          $this->postEvent->modifyPost();

          if (isset($_SESSION['post_protected']) && $postId > 0) {

            $values = [
              'post_id' => $postId,
              'post_author' => $this->postEvent->postAuthorId(),
              'post_date' => date_for_database(distill_post_request($filters)['post_date']),
              'post_password' => distill_post_request($filters)['post_password'],
              'passpharse' => distill_post_request($filters)['post_password']
            ];

            save_post_protected($this->setCredential($values));
          }
          
          $_SESSION['status'] = "postUpdated";
          direct_page('index.php?load=posts&status=postUpdated', 200);
          
        }

      } catch (\Throwable $th) {

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

      if ($user_level === 'contributor') {

        $this->view->set('medialibs', $medialib->dropDownMediaSelect($getPost['media_id']));

      } else {

        $this->view->set('medialibs', $medialib->imageUploadHandler($getPost['media_id']));
      }

      if ($data_post['post_visibility'] == 'protected') {

        $this->view->set('postContent', decrypt_post($getPost['ID'], $getPost['post_password']));

      } else {

        $this->view->set('postContent', $data_post['post_content']);

      }

      $this->view->set('postStatus', $this->postEvent->postStatusDropDown($getPost['post_status']));
      $this->view->set('commentStatus', $this->postEvent->commentStatusDropDown($getPost['comment_status']));
      $this->view->set('postVisibility', $this->postEvent->visibilityDropDown($getPost['post_visibility']));
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

          header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }

        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
          throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
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

          if ($this->postEvent->postAuthorLevel() === 'administrator') {

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
          $_SESSION['status'] = "postDeleted";
          direct_page('index.php?load=posts&status=postDeleted', 200);
        }
      } catch (\Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
      }
    }
  }

  /**
   * setView
   * 
   * @param object $viewName
   * 
   */
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'posts', $viewName);
  }

  /**
   * setCredential
   *
   * @param array $values
   * 
   */
  private function setCredential(array $values)
  {
    $this->crendential = [
      'post_id' => $values['post_id'],
      'post_author' => $values['post_author'],
      'post_date' => $values['post_date'],
      'post_password' => $values['post_password'],
      'passphrase' => $values['post_password']
    ];

    return $this->crendential;
  }
}