<?php

namespace Scriptlog\Controller;
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

use Scriptlog\Core\ActionConst;
use Scriptlog\Core\AppException;
use Scriptlog\Core\BaseApp;
use Scriptlog\Core\LogError;
use Scriptlog\Core\View;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\TopicDao;
use Scriptlog\Dto\PostRequestDto;
use Scriptlog\Dto\UploadedFileDto;
use Scriptlog\Service\PostApplicationService;
use Scriptlog\Service\PostService;
use Scriptlog\Validator\FileUploadValidator;
use Scriptlog\Validator\PostValidator;
use Scriptlog\Validator\ProtectedPostValidator;

class PostController extends BaseApp
{
    private $view;

    private $postService;

    private $topicDao;

    private $mediaDao;

    private $appService;

    public function __construct(PostService $postService, TopicDao $topicDao, MediaDao $mediaDao, PostApplicationService $appService)
    {
        $this->postService = $postService;
        $this->topicDao = $topicDao;
        $this->mediaDao = $mediaDao;
        $this->appService = $appService;
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

    /**
     * Create a new post.
     *
     * @return string
     */
    public function insert()
    {
        $errors = array();
        $checkError = true;
        $user_level = $this->postService->postAuthorLevel();
        $topics = $this->topicDao;
        $medialib = $this->mediaDao;

        if (isset($_POST['postFormSubmit'])) {
            $mediaFile = UploadedFileDto::fromGlobals();
            $file_location = $mediaFile->tmpName;
            $file_type = $mediaFile->type;
            $file_name = $mediaFile->name;
            $file_size = $mediaFile->size;
            $file_error = $mediaFile->error;

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();
                $this->checkPostPayload();

                $checkError = $this->validatePostSubmission($file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderNewPostForm($errors, $_POST, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                $this->appService->createPost($file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $user_level);

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

    /**
     * Update an existing post.
     *
     * @param int $id
     * @return string
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
            $mediaFile = UploadedFileDto::fromGlobals();
            $file_location = $mediaFile->tmpName;
            $file_type = $mediaFile->type;
            $file_name = $mediaFile->name;
            $file_size = $mediaFile->size;
            $file_error = $mediaFile->error;

            $new_filename = generate_filename($file_name)['new_filename'];
            $file_extension = generate_filename($file_name)['file_extension'];

            try {
                $this->checkPostCsrf();
                $this->checkPostUpdatePayload();

                $checkError = $this->validatePostUpdate($file_location, $file_error, $file_size, $file_name, $errors, $checkError);

                if (!$checkError) {
                    $this->renderEditPostForm($errors, $data_post, $getPost, $topics, $medialib, $user_level);
                    return $this->view->render();
                }

                $this->appService->updatePost((int)$id, $file_location, $file_type, $file_name, $file_size, $file_extension, $new_filename, $user_level, $data_post['media_id']);

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

    /**
     * Remove a post.
     *
     * {@inheritDoc}
     * @see BaseApp::remove()
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

    // ─── Security ──────────────────────────────────────────────

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

    private function checkPostUpdatePayload()
    {
        if (check_form_request($_POST, ['post_id', 'post_title', 'post_content', 'post_modified', 'post_date', 'image_id', 'catID', 'post_summary', 'post_status', 'post_headlines', 'visibility', 'comment_status', 'post_password', 'post_tags', 'post_locale']) === false) {
            header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 413 Payload Too Large", true, 413);
            header('Status: 413 Payload Too Large');
            header('Retry-After: 3600');
            throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
        }
    }

    // ─── Validation ───────────────────────────────────────────

    private function validatePostSubmission($file_location, $file_error, $file_size, $file_name, &$errors, $checkError)
    {
        $dto = PostRequestDto::fromGlobals();
        $result = (new PostValidator())->validate($dto);
        if (!$result->isValid()) {
            $checkError = false;
            $errors = array_merge($errors, $result->getErrors());
        }

        $uploadedFile = UploadedFileDto::fromGlobals();
        if ($uploadedFile->isValid()) {
            $fileResult = (new FileUploadValidator())->validate($uploadedFile);
            if (!$fileResult->isValid()) {
                $checkError = false;
                $errors = array_merge($errors, $fileResult->getErrors());
            }
        }

        if ($dto->isProtected()) {
            $pwdResult = (new ProtectedPostValidator())->validate($dto);
            if (!$pwdResult->isValid()) {
                $checkError = false;
                $errors = array_merge($errors, $pwdResult->getErrors());
            }
        }

        return $checkError;
    }

    private function validatePostUpdate($file_location, $file_error, $file_size, $file_name, &$errors, $checkError)
    {
        $dto = PostRequestDto::fromGlobals();
        $result = (new PostValidator())->validate($dto);
        if (!$result->isValid()) {
            $checkError = false;
            $errors = array_merge($errors, $result->getErrors());
        }

        $uploadedFile = UploadedFileDto::fromGlobals();
        if ($uploadedFile->isValid()) {
            $fileResult = (new FileUploadValidator())->validate($uploadedFile);
            if (!$fileResult->isValid()) {
                $checkError = false;
                $errors = array_merge($errors, $fileResult->getErrors());
            }
        }

        return $checkError;
    }

    // ─── Rendering ────────────────────────────────────────────

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
     * Set view.
     *
     * @param string $viewName
     * @return void
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'posts', $viewName);
    }
}
