<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class PostController extends BaseApp
 *
 * @category  Class PostController extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
class PostController extends BaseApp
{
    private $view;
    private $postService;
    private $topicDao;
    private $mediaDao;

    public function __construct(PostService $postService, TopicDao $topicDao, MediaDao $mediaDao)
    {
        $this->postService = $postService;
        $this->topicDao = $topicDao;
        $this->mediaDao = $mediaDao;
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

        if ($this->postService->postAuthorLevel() == 'administrator') {
            $this->view->set('postsTotal', $this->postService->totalPosts());
            $this->view->set('posts', $this->postService->grabPosts());
        } else {
            $this->view->set('postsTotal', $this->postService->totalPosts([$this->postService->postAuthorId()]));
            $this->view->set('posts', $this->postService->grabPosts('ID', $this->postService->postAuthorId()));
        }

        return $this->view->render();
    }

    public function insert()
    {
        $errors = array();
        $checkError = true;
        $user_level = $this->postService->postAuthorLevel();
        $topics = $this->topicDao;
        $medialib = $this->mediaDao;

        if (isset($_POST['postFormSubmit'])) {
            $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
            $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
            $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
            $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
            $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

            $filters = $this->getPostFilters();
            $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_tags' => 200, 'post_content' => 500000];

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();
                $this->checkPostPayload();

                $checkError = $this->validatePostSubmission($filters, $form_fields, $file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderNewPostForm($errors, $_POST, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                list($width, $height) = ($file_location) ? getimagesize($file_location) : getimagesize(__DIR__ . '/../../' . APP_IMAGE . 'nophoto.jpg');
                $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] === 'publish')) ? 'public' : 'private';
                $filtered = distill_post_request($filters);

                $this->postService->processPostImage($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $width, $height, $media_access, $user_level, $filtered, false, null);
                $this->setPostServiceData($filters, $filtered);

                $this->postService->addPost();
                $_SESSION['status'] = "postAdded";
                direct_page('index.php?load=posts&status=postAdded', 200);
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            }
        }

        $this->renderNewPostForm(null, null, $topics, $medialib, $user_level);
        return $this->view->render();
    }

    private function getPostFilters()
    {
        return [
          'post_title' => isset($_POST['post_title']) ? Sanitize::strictSanitizer($_POST['post_title']) : "",
          'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_date' => isset($_POST['post_date']) ? Sanitize::mildSanitizer($_POST['post_date']) : "",
          'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
          'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
          'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
          'visibility' => isset($_POST['visibility']) ? Sanitize::mildSanitizer($_POST['visibility']) : "",
          'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
          'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
          'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "",
          'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
        ];
    }

    private function checkPostCsrf()
    {
        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . MESSAGE_BADREQUEST, true, 400);
            header('Status: 400 Bad Request');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    private function checkPostPayload()
    {
        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_date', 'image_id', 'catID', 'post_summary', 'post_tags', 'post_status', 'post_headlines', 'visibility', 'comment_status', 'post_password', 'post_locale']) === false) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . ' 413 Payload Too Large', true, 413);
            header('Status: 413 Payload Too Large');
            header('Retry-After: 3600');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    private function validatePostSubmission($filters, $form_fields, $file_location, $file_error, $file_size, $file_name, &$errors, $checkError)
    {
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
            $checkError = $this->validateFileUpload($file_location, $file_error, $file_size, $file_name, $errors, $checkError);
        }

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {
            $checkError = $this->validateProtectedPassword($errors, $checkError);
        }

        return $checkError;
    }

    private function validateFileUpload($file_location, $file_error, $file_size, $file_name, &$errors, $checkError)
    {
        if (is_array($file_error)) {
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

        return $checkError;
    }

    private function validateProtectedPassword(&$errors, $checkError)
    {
        if (check_common_password($_POST['post_password']) === true) {
            $checkError = false;
            array_push($errors, "Your password seems to be the most hacked password, please try another");
        }

        if (false === check_pwd_strength($_POST['post_password'])) {
            $checkError = false;
            array_push($errors, MESSAGE_WEAK_PASSWORD);
        }

        return $checkError;
    }

    private function setPostServiceData($filters, array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (isset($_POST['catID']) && $_POST['catID'] == 0) {
            $this->postService->setTopics(0);
        } else {
            $this->postService->setTopics($filtered['catID']);
        }

        $this->postService->setPostAuthor((int)$this->postService->postAuthorId());

        if (empty($_POST['post_date'])) {
            $this->postService->setPostDate(date_for_database());
        } else {
            $this->postService->setPostDate(date_for_database($filtered['post_date']));
        }

        $this->postService->setPostTitle($filtered['post_title']);
        $this->postService->setPostSlug($filtered['post_title']);

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {
            if (!empty($_POST['post_password'])) {
                $protected = protect_post($filtered['post_content'], $filtered['visibility'], $filtered['post_password']);
                $this->postService->setPostContent($protected['post_content']);
                $this->postService->setProtected($protected['post_password']);
                $this->postService->setPassPhrase($filtered['post_password']);
                $_SESSION['post_protected'] = $filtered['post_password'];
            }
        } else {
            $this->postService->setPostContent($filtered['post_content']);
        }

        $this->postService->setPublish($filtered['post_status']);
        $this->postService->setVisibility($filtered['visibility']);

        if (empty($_POST['post_headlines'])) {
            $this->postService->setHeadlines(0);
        } else {
            $this->postService->setHeadlines($filtered['post_headlines']);
        }

        $this->postService->setComment($filtered['comment_status']);
        $this->postService->setMetaDesc($filtered['post_summary']);
        $this->postService->setPostLocale($filtered['post_locale']);

        if (isset($_POST['post_tags'])) {
            $this->postService->setPostTags($filtered['post_tags']);
        }
    }

    private function renderNewPostForm($errors, $formData, $topics, $medialib, $user_level)
    {
        $this->setView('edit-post');
        $this->setPageTitle(($formData !== null) ? 'Add New Post' : 'Add new post');
        $this->setFormAction(ActionConst::NEWPOST);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());

        if ($formData !== null) {
            $this->view->set('formData', $formData);
        }

        $this->view->set('topics', $topics->setCheckBoxTopic());

        if ($user_level === 'contributor') {
            $this->view->set('medialibs', $medialib->dropDownMediaSelect());
        } else {
            $this->view->set('medialibs', $medialib->imageUploadHandler());
        }

        if (!empty($errors)) {
            $this->view->set('errors', $errors);
        }

        $this->view->set('postStatus', $this->postService->postStatusDropDown());
        $this->view->set('commentStatus', $this->postService->commentStatusDropDown());
        $this->view->set('postVisibility', $this->postService->visibilityDropDown());
        $this->view->set('postLocale', $this->postService->localeDropDown());
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
    }

    /**
     * update()
     *
     * @param num|int $id
     *
     */
    public function update($id)
    {
        $errors = array();
        $checkError = true;
        $user_level = $this->postService->postAuthorLevel();
        $topics = $this->topicDao;
        $medialib = $this->mediaDao;

        $getPost = $this->postService->grabPost($id);
        if (!$getPost) {
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
          'post_status' => $getPost['post_status'],
          'post_visibility' => $getPost['post_visibility'],
          'post_password' => $getPost['post_password'],
          'post_tags' => $getPost['post_tags'],
          'post_headlines' => $getPost['post_headlines'],
          'comment_status' => $getPost['comment_status'],
          'passphrase' => $getPost['passphrase']
        );

        $timezone = function_exists('timezone_identifier') ? timezone_identifier() : "";
        (function_exists('date_default_timezone_set')) ? date_default_timezone_set($timezone) : "";

        if (isset($_POST['postFormSubmit'])) {
            $file_location = isset($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'] : '';
            $file_type = isset($_FILES['media']['type']) ? $_FILES['media']['type'] : '';
            $file_name = isset($_FILES['media']['name']) ? $_FILES['media']['name'] : '';
            $file_size = isset($_FILES['media']['size']) ? $_FILES['media']['size'] : '';
            $file_error = isset($_FILES['media']['error']) ? $_FILES['media']['error'] : '';

            $filters = $this->getPostUpdateFilters();
            $form_fields = ['post_title' => 200, 'post_summary' => 320, 'post_tags' => 200, 'post_content' => 500000];

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();
                $this->checkPostUpdatePayload();

                $checkError = $this->validatePostUpdate($filters, $form_fields, $file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderEditPostForm($errors, $data_post, $getPost, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                $filtered = distill_post_request($filters);
                $this->processPostUpdate($id, $filters, $file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $user_level, $medialib, $data_post['media_id'], $filtered);

                $this->postService->modifyPost();
                $_SESSION['status'] = "postUpdated";
                direct_page('index.php?load=posts&status=postUpdated', 200);
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            }
        }

        $this->renderEditPostForm(null, $data_post, $getPost, $topics, $medialib, $user_level);
        return $this->view->render();
    }

    private function getPostUpdateFilters()
    {
        return [
          'post_id' => FILTER_SANITIZE_NUMBER_INT,
          'post_title' => isset($_POST['post_title']) ? Sanitize::strictSanitizer($_POST['post_title']) : "",
          'post_content' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_modified' => isset($_POST['post_modified']) ? Sanitize::mildSanitizer($_POST['post_modified']) : "",
          'image_id' => isset($_POST['image_id']) ? FILTER_SANITIZE_NUMBER_INT : "",
          'catID' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY],
          'post_summary' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_status' => isset($_POST['post_status']) ? Sanitize::mildSanitizer($_POST['post_status']) : "",
          'visibility' => isset($_POST['visibility']) ? Sanitize::mildSanitizer($_POST['visibility']) : "",
          'post_password' => isset($_POST['post_password']) ? FILTER_SANITIZE_FULL_SPECIAL_CHARS : "",
          'post_tags' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'post_headlines' => FILTER_SANITIZE_NUMBER_INT,
          'comment_status' => isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "",
          'post_locale' => isset($_POST['post_locale']) ? Sanitize::mildSanitizer($_POST['post_locale']) : "en"
        ];
    }

    private function checkPostUpdatePayload()
    {
        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_modified', 'post_date', 'image_id', 'catID', 'post_summary', 'post_status', 'post_headlines', 'visibility', 'comment_status', 'post_password', 'post_tags', 'post_locale']) === false) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 413 Payload Too Large", true, 413);
            header('Status: 413 Payload Too Large');
            header('Retry-After: 3600');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    private function validatePostUpdate($filters, $form_fields, $file_location, $file_error, $file_size, $file_name, &$errors, $checkError)
    {
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

        if (!empty($_POST['post_modified']) && validate_date($_POST['post_modified']) === false) {
            $checkError = false;
            array_push($errors, "Please fix your date format");
        }

        if (!empty($file_location)) {
            $checkError = $this->validateFileUpload($file_location, $file_error, $file_size, $file_name, $errors, $checkError);
        }

        return $checkError;
    }

    /** @SuppressWarnings(PHPMD.ExcessiveParameterList) */
    private function processPostUpdate($id, $filters, $file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $user_level, $medialib, $oldMediaId = null, array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        $this->postService->setPostId((int)$filtered['post_id']);
        $this->postService->setPostAuthor($this->postService->postAuthorId());
        $this->postService->setPostTitle($filtered['post_title']);
        $this->postService->setPostSlug($filtered['post_title']);
        $this->postService->setPublish($filtered['post_status']);

        if (isset($_POST['catID']) && $_POST['catID'] == 0) {
            $this->postService->setTopics(0);
        } else {
            $this->postService->setTopics($filtered['catID']);
        }

        list($width, $height) = (!empty($file_location)) ? getimagesize($file_location) : getimagesize(__DIR__ . '/../../' . APP_IMAGE . 'nophoto.jpg');

        $media_access = (isset($_POST['post_status']) && ($_POST['post_status'] == 'publish')) ? 'public' : 'private';

        $this->postService->processPostImage($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $width, $height, $media_access, $user_level, $filtered, true, $oldMediaId);

        $this->postService->setComment($filtered['comment_status']);
        $this->postService->setMetaDesc($filtered['post_summary']);
        $this->postService->setPostTags($filtered['post_tags']);
        $this->postService->setPostLocale($filtered['post_locale']);

        if (empty($_POST['post_modified'])) {
            $this->postService->setPostModified(date_for_database());
        } else {
            $this->postService->setPostModified(date_for_database($filtered['post_modified']));
        }

        $this->setProtectedPostContent($id, $filters, $filtered);
        $this->setPostHeadlines($filters, $filtered);
    }

    private function setProtectedPostContent($id, $filters, array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (isset($_POST['visibility']) && $_POST['visibility'] == 'protected') {
            if (!empty($_POST['post_password'])) {
                $protected = protect_post($filtered['post_content'], $filtered['visibility'], $filtered['post_password']);
                $this->postService->setProtected($protected['post_password']);
                $this->postService->setPostContent($protected['post_content'], true);
                $this->postService->setPassPhrase($filtered['post_password']);
                $_SESSION['post_protected'] = $filtered['post_password'];
            } else {
                $existing_post = $this->postService->grabPost($id);
                if ($existing_post && !empty($existing_post['passphrase'])) {
                    $reencrypted = encrypt($filtered['post_content'], $existing_post['passphrase']);
                    $this->postService->setPostContent($reencrypted, true);
                } else {
                    $this->postService->setPostContent($filtered['post_content']);
                }
            }
            $this->postService->setVisibility($filtered['visibility']);
        } else {
            $this->postService->setVisibility($filtered['visibility']);
            $this->postService->setPostContent($filtered['post_content']);
        }
    }

    private function setPostHeadlines($filters, array $filtered = null)
    {
        if ($filtered === null) {
            $filtered = distill_post_request($filters);
        }

        if (empty($_POST['post_headlines'])) {
            $this->postService->setHeadlines(0);
        } else {
            $this->postService->setHeadlines($filtered['post_headlines']);
        }
    }

    private function renderEditPostForm($errors, $data_post, $getPost, $topics, $medialib, $user_level)
    {
        $this->setView('edit-post');
        $this->setPageTitle('Edit Post');
        $this->setFormAction(ActionConst::EDITPOST);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());

        if ($errors !== null) {
            $this->view->set('errors', $errors);
        }

        $this->view->set('postData', $data_post);
        $this->view->set('topics', $topics->setCheckBoxTopic($getPost['ID']));

        if ($user_level === 'contributor') {
            $this->view->set('medialibs', $medialib->dropDownMediaSelect($getPost['media_id']));
        } else {
            $this->view->set('medialibs', $medialib->imageUploadHandler($getPost['media_id']));
        }

        if ($data_post['post_visibility'] == 'protected') {
            $decrypted = decrypt_post_admin($getPost['ID']);
            $this->view->set('postContent', $decrypted['post_content']);
        } else {
            $this->view->set('postContent', $data_post['post_content']);
        }

        $this->view->set('postStatus', $this->postService->postStatusDropDown($getPost['post_status']));
        $this->view->set('commentStatus', $this->postService->commentStatusDropDown($getPost['comment_status']));
        $this->view->set('postVisibility', $this->postService->visibilityDropDown($getPost['post_visibility']));
        $this->view->set('postLocale', $this->postService->localeDropDown($getPost['post_locale'] ?? 'en'));
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
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
        $id = abs((int)$id);

        if ($id <= 0) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return;
        }

        $getPost = $this->postService->grabPost($id);

        if (!$getPost) {
            $_SESSION['error'] = "postNotFound";
            direct_page('index.php?load=posts&error=postNotFound', 404);
            return;
        }

        try {
            $this->postService->setPostId($id);
            $this->postService->removePost();
            $_SESSION['status'] = "postDeleted";
            direct_page('index.php?load=posts&status=postDeleted', 200);
        } catch (\Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        }
    }

    /**
     * setView
     *
     * @param string $viewName
     *
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'posts', $viewName);
    }
}
