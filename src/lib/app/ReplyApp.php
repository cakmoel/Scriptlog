<?php
/**
 * Class ReplyApp 
 * 
 * @category Class ReplyApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
class ReplyApp extends BaseApp
{
  
 private $view;

 private $replyEvent;

 public function __construct(ReplyEvent $replyEvent)
 {
    $this->replyEvent = $replyEvent;
 }

 public function listItems()
 {
   #leave an empty
 }

 public function insert()
 {
   
  $errors = array();
  $checkError = true;

  if (isset($_POST['replyFormSubmit'])) {

    $filters = ['comment_id' => FILTER_SANITIZE_NUMBER_INT, 'user_id' => FILTER_SANITIZE_NUMBER_INT, 'reply_content' => FILTER_SANITIZE_SPECIAL_CHARS];

    try {
      
      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
         header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
         throw new AppException("Sorry, unpleasant attempt detected!");
         
     }

     

    } catch (AppException $e) {

      LogError::setStatusCode(http_response_code());
      LogError::newMessage($e);
      LogError::customErrorMessage('admin');

    }

  } else {

    $this->setView('comment-reply');
    $this->setPageTitle('Reply to Comment');
    $this->setFormAction(ActionConst::NEWREPLY);
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('formAction', $this->getFormAction());
    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
   
  }

  return $this->view->render();

 }
 
 public function update($id)
 {

 }

 public function remove($id)
 {

 }

 protected function setView($viewName)
 {
   $this->view = new View('admin', 'ui', 'comments', $viewName);
 }

}