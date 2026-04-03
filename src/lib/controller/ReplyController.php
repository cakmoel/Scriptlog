<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class ReplyController extends BaseApp
 *
 * @category Class ReplyController extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */
class ReplyController extends BaseApp
{
    private $view;
    private $replyService;

    public function __construct(ReplyService $replyService)
    {
        $this->replyService = $replyService;
    }

    /**
     * List Replies for a Parent Comment
     *
     * @param int $parentId
     * @return string
     */
    public function listItems($parentId = null)
    {
        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_SESSION['error'])) {
            $checkError = false;
            ($_SESSION['error'] == 'replyNotFound') ? array_push($errors, "Error: Reply Not Found!") : "";
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['status'])) {
            $checkStatus = true;
            ($_SESSION['status'] == 'replyAdded') ? array_push($status, "Reply added successfully") : "";
            ($_SESSION['status'] == 'replyUpdated') ? array_push($status, "Reply has been updated") : "";
            ($_SESSION['status'] == 'replyDeleted') ? array_push($status, "Reply deleted") : "";
            unset($_SESSION['status']);
        }

        $this->setView('reply-list');
        $this->setPageTitle('Replies');

        if (!$checkError) {
            $this->view->set('errors', $errors);
        }

        if ($checkStatus) {
            $this->view->set('status', $status);
        }

        if ($parentId !== null) {
            $this->view->set('replies', $this->replyService->grabReplies($parentId));
            $this->view->set('parentId', $parentId);
        }

        $this->view->set('pageTitle', $this->getPageTitle());
        return $this->view->render();
    }

    /**
     * Insert New Reply
     *
     * @return string
     */
    public function insert()
    {
        $errors = array();
        $checkError = true;

        $parentId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;

        if ($parentId === 0) {
            direct_page('index.php?load=comments', 302);
            return;
        }

        $parentComment = $this->replyService->grabParentComment($parentId);

        if (!$parentComment) {
            $_SESSION['error'] = "parentCommentNotFound";
            direct_page('index.php?load=comments&error=parentCommentNotFound', 404);
            return;
        }

        if (isset($_POST['replyFormSubmit'])) {
            $author_name = isset($_POST['author_name']) ? trim(htmlspecialchars($_POST['author_name'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) : "";
            $reply_content = isset($_POST['reply_content']) ? Sanitize::severeSanitizer($_POST['reply_content']) : "";
            $reply_status = isset($_POST['reply_status']) ? Sanitize::mildSanitizer($_POST['reply_status']) : "pending";

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (empty($author_name)) {
                    $checkError = false;
                    array_push($errors, "Please enter author name");
                }

                if (empty($reply_content)) {
                    $checkError = false;
                    array_push($errors, "Please enter reply content");
                }

                if (!$checkError) {
                    $this->setView('reply');
                    $this->setPageTitle("Reply to Comment");
                    $this->setFormAction(ActionConst::REPLY);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('parentComment', $parentComment);
                    $this->view->set('replyStatus', $this->replyService->replyStatementDropDown($reply_status));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $this->replyService->setParentId($parentId);
                    $this->replyService->setPostId($parentComment['comment_post_id']);
                    $this->replyService->setAuthorName($author_name);
                    $this->replyService->setAuthorIP(get_ip_address());
                    $this->replyService->setAuthorEmail(isset($_SESSION['scriptlog_session_email']) ? $_SESSION['scriptlog_session_email'] : "");
                    $this->replyService->setReplyContent($reply_content);
                    $this->replyService->setReplyStatus($reply_status);

                    $this->replyService->addReply();

                    $_SESSION['status'] = "replyAdded";
                    direct_page('index.php?load=comments&action=editComment&Id=' . $parentId . '&status=replyAdded', 200);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            $this->setView('reply');
            $this->setPageTitle("Reply to Comment");
            $this->setFormAction(ActionConst::REPLY);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('parentComment', $parentComment);
            $this->view->set('replyStatus', $this->replyService->replyStatementDropDown('pending'));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * Update Reply
     *
     * @param int $id
     * @return string
     */
    public function update($id)
    {
        $errors = array();
        $checkError = true;

        if (!$getReply = $this->replyService->grabReply($id)) {
            $_SESSION['error'] = "replyNotFound";
            direct_page('index.php?load=comments&error=replyNotFound', 404);
            return;
        }

        $data_reply = array(
          'ID' => $getReply['ID'],
          'comment_post_id' => $getReply['comment_post_id'],
          'comment_parent_id' => $getReply['comment_parent_id'],
          'comment_author_name' => $getReply['comment_author_name'],
          'comment_author_ip' => $getReply['comment_author_ip'],
          'comment_content' => $getReply['comment_content'],
          'comment_status' => $getReply['comment_status'],
          'comment_date' => $getReply['comment_date'],
          'post_title' => $getReply['post_title'],
          'parent_comment_content' => $getReply['parent_comment_content'] ?? '',
          'parent_comment_author' => $getReply['parent_comment_author'] ?? ''
        );

        if (isset($_POST['replyFormSubmit'])) {
            $author_name = isset($_POST['author_name']) ? trim(htmlspecialchars($_POST['author_name'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) : "";
            $reply_content = isset($_POST['reply_content']) ? Sanitize::severeSanitizer($_POST['reply_content']) : "";
            $reply_status = isset($_POST['reply_status']) ? Sanitize::mildSanitizer($_POST['reply_status']) : "";

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (empty($author_name)) {
                    $checkError = false;
                    array_push($errors, "Please enter author name");
                }

                if (empty($reply_content)) {
                    $checkError = false;
                    array_push($errors, "Please enter reply content");
                }

                if (!$checkError) {
                    $this->setView('reply');
                    $this->setPageTitle("Edit Reply");
                    $this->setFormAction(ActionConst::EDITREPLY);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('replyData', $data_reply);
                    $this->view->set('replyStatus', $this->replyService->replyStatementDropDown($getReply['comment_status']));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $this->replyService->setReplyId($id);
                    $this->replyService->setAuthorName($author_name);
                    $this->replyService->setReplyContent($reply_content);
                    $this->replyService->setReplyStatus($reply_status);

                    $this->replyService->modifyReply();

                    $_SESSION['status'] = "replyUpdated";
                    direct_page('index.php?load=reply&action=editReply&Id=' . $id . '&status=replyUpdated', 200);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            $this->setView('reply');
            $this->setPageTitle("Edit Reply");
            $this->setFormAction(ActionConst::EDITREPLY);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('replyData', $data_reply);
            $this->view->set('replyStatus', $this->replyService->replyStatementDropDown($getReply['comment_status']));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * Remove Reply
     *
     * @param int $id
     */
    public function remove($id)
    {
        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                $getReply = $this->replyService->grabReply($id);

                if (!$getReply) {
                    $checkError = false;
                    array_push($errors, 'Error: Reply not found');
                }

                if (!$checkError) {
                    $this->setView('reply-list');
                    $this->setPageTitle('Reply not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                } else {
                    $this->replyService->setReplyId($id);
                    $this->replyService->removeReply();

                    $_SESSION['status'] = "replyDeleted";
                    direct_page('index.php?load=comments', 200);
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
        $this->view = new View('admin', 'ui', 'comments', $viewName);
    }
}
