<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class CommentApp
 *
 * @category  Class CommentApp extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class CommentApp extends BaseApp
{

/**
 * view
 *
 * @var object
 * 
 */
  private $view;

/**
 * commentEvent
 *
 * @var object
 * 
 */
  private $commentEvent;
  
/**
 * __constructor
 *
 * @param CommentEvent $commentEvent
 * 
 */
  public function __construct(CommentEvent $commentEvent)
  {
    $this->commentEvent = $commentEvent;
  }
  
  public function listItems()
  {
      
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
    
    if (isset($_SESSION['error'])) {
        $checkError = false;
        ($_SESSION['error'] == 'commentNotFound') ?: array_push($errors, "Error: Comment Not Found!"); 
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['status'])) {
        $checkStatus = true;
        ($_SESSION['status'] == 'commentAdded') ?: array_push($status, "New comment added");
        ($_SESSION['status'] == 'commentUpdated') ?: array_push($status, "Comment has been updated");
        ($_SESSION['status'] == 'commentDeleted') ?: array_push($status, "Comment deleted");
        unset($_SESSION['status']);
    }
    
    $this->setView('all-comments');
    
    if (!$checkError) {
        $this->view->set('errors', $errors);
    }
    
    if ($checkStatus) {
        $this->view->set('status', $status);
    }

    $this->setPageTitle('Comments');
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('commentsTotal', $this->commentEvent->totalComments());
    $this->view->set('comments', $this->commentEvent->grabComments());
    
    return $this->view->render();
    
  }
  
  public function insert()
  {
    #leave empty  
  }
  
  public function update($id)
  {
    $errors = array();
    $checkError = true;
    
    if (!$getComment = $this->commentEvent->grabComment($id)) {
      
      $_SESSION['error'] = "commentNotFound";
      direct_page('index.php?load=comments&error=commentNotFound', 404);
        
    }
    
    $data_comment = array(
        
        'ID' => $getComment['ID'],
        'comment_post_id' => $getComment['comment_post_id'],
        'comment_author_name' => $getComment['comment_author_name'],
        'comment_author_ip' => $getComment['comment_author_ip'],
        'comment_content' => $getComment['comment_content'],
        'comment_status' => $getComment['comment_status'],
        'comment_date' => $getComment['comment_date']
        
    );
    
    if (isset($_POST['commentFormSubmit'])) {
        
        $author_name = isset($_POST['author_name']) ? trim(htmlspecialchars($_POST['author_name'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) : "";
        $comment_content = isset($_POST['comment_content']) ? Sanitize::severeSanitizer($_POST['comment_status']) : "";
        $comment_id = isset($_POST['comment_id']) ? abs((int)$_POST['comment_id']) : 0;
        $comment_status = isset($_POST['comment_status']) ? Sanitize::mildSanitizer($_POST['comment_status']) : "";
        
        try {
            
            if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
              header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
              throw new AppException("Sorry, unpleasant attempt detected!");
                
            }
            
            if (empty($author_name)) {
                
              $checkError = false;
              array_push($errors, "Please enter author name");
                
            }
            
            if (empty($comment_content)) {
                
                $checkError = false;
                array_push($errors, "Please enter comment content");
                
            }
            
            if (!$checkError) {
                
                $this->setView('edit-comment');
                $this->setPageTitle("Edit Comment");
                $this->setFormAction(ActionConst::EDITCOMMENT);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('commentData', $data_comment);
                $this->view->set('commentStatus', $this->commentEvent->commentStatementDropDown($getComment['comment_status']));
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                
            } else {
              
                $this->commentEvent->setCommentId($comment_id);
                $this->commentEvent->setCommentContent($comment_content);
                $this->commentEvent->setCommentStatus($comment_status);
                $this->commentEvent->modifyComment();
                $_SESSION['status'] = "commentUpdated";
                direct_page('index.php?load=comments&status=commentUpdated', 200);
                
            }
            
        } catch (Throwable $th) {

          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($th);

        } catch (AppException $e) {
            
          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($e);
            
        }
        
    } else {
        
        $this->setView('edit-comment');
        $this->setPageTitle("Edit Comment");
        $this->setFormAction(ActionConst::EDITCOMMENT);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('commentData', $data_comment);
        $this->view->set('commentStatus', $this->commentEvent->commentStatementDropDown($getComment['comment_status']));
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
    }
    
    return $this->view->render();
    
  }
  
  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getComment = $this->commentEvent->grabComment($id);

      try {
          
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
            throw new AppException("Sorry, unpleasant attempt detected!");
    
        }
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
    
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
            throw new AppException("Sorry, unpleasant attempt detected!");
    
        }

        if (!$getComment) {

            $checkError = false;
            array_push($errors, 'Error: Comment not found');

        }

        if (!$checkError) {

            $this->setView('all-comments');
            $this->setPageTitle('Comment not found');
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('errors', $errors);
            
        } else {

         $this->commentEvent->setCommentId($id);
         $this->commentEvent->removeComment();
         $_SESSION['status'] = "commentDeleted";
         direct_page('index.php?load=comments&status=commentDeleted', 200);

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