<?php 
/**
 * Class TopicApp extends  BaseApp
 *
 * @package   SCRIPTLOG/LIB/APP/TopicApp
 * @category  App Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class TopicApp extends BaseApp
{
  private $view;

  private $topicEvent;
  
  public function __construct(TopicEvent $topicEvent)
  {
    $this->topicEvent = $topicEvent;
  }
  
  public function listItems()
  {
    
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
    
    if (isset($_GET['error'])) {
        $checkError = false;
        if ($_GET['error'] == 'topicNotFound') array_push($errors, "Error: Topic Not Found!");
    }
    
    if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'topicAdded') array_push($status, "New topic added");
        if ($_GET['status'] == 'topicUpdated') array_push($status, "Topic has been updated");
        if ($_GET['status'] == 'topicDeleted') array_push($status, "Topic deleted");
    }
    
    $this->setView('all-topics');
    $this->setPageTitle('Topics');
    
    if (!$checkError) {
       $this->view->set('error', $errors);
    }
    
    if ($checkStatus) {
        $this->view->set('status', $status);
    }
    
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('topicsTotal', $this->topicEvent->totalTopics());
    $this->view->set('topics', $this->topicEvent->grabTopics());
    return $this->view->render();
    
  }
  
  public function insert()
  {
    
    $errors = array();
    $checkError = true;
    
    if (isset($_POST['topicFormSubmit'])) {
       
      $title = isset($_POST['topic_title']) ? trim($_POST['topic_title']) : "";
      $slug = make_slug($title);
      
      try {
          
          if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
              header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
              throw new AppException("Sorry, unpleasant attempt detected!");
              
          }
          
          if (empty($title)) {
              
              $checkError = false;
              array_push($errors, "Please enter title");
              
          }
          
          if (!$checkError) {
              
             $this->setView('edit-topic');
             $this->setPageTitle('Add New Topic');
             $this->setFormAction('newTopic');
             $this->view->set('pageTitle', $this->getPageTitle());
             $this->view->set('formAction', $this->getFormAction());
             $this->view->set('errors', $errors);
             $this->view->set('formData', $_POST);
             $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
             
          } else {
              
             $this->topicEvent->setTopicTitle($title);
             $this->topicEvent->setTopicSlug($slug);
             $this->topicEvent->addTopic();
             direct_page('index.php?load=topics&status=topicAdded', 200);
             
          }
          
      } catch (AppException $e) {
         
          LogError::setStatusCode(http_response_code());
          LogError::newMessage($e);
          LogError::customErrorMessage('admin');
      }
      
    } else {
      
       $this->setView('edit-topic');
       $this->setPageTitle('Add New Topic');
       $this->setFormAction('newTopic');
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            
    }
     
    return $this->view->render();
    
  }
  
  public function update($id)
  {
      
    $errors = array();
    $checkError = true;
    
    if (!$getCategory = $this->topicEvent->grabTopic($id)) {
        
        direct_page('index.php?load=topics&error=topicNotFound', 404);
        
    }
    
    $data_topic = array(
        'ID' => $getCategory['ID'],
        'topic_title' => $getCategory['topic_title'],
        'topic_slug' => $getCategory['topic_slug'],
        'topic_status' => $getCategory['topic_status']
    );
    
    if (isset($_POST['topicFormSubmit'])) {
        
        $topic_title = isset($_POST['topic_title']) ? trim($_POST['topic_title']) : "";
        $topic_slug = make_slug($topic_title);
        $topic_status = isset($_POST['topic_status']) ? $_POST['topic_status'] : "";
        $topic_id = isset($_POST['topic_id']) ? abs((int)$_POST['topic_id']) : 0;
        
        try {
            
            if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                throw new AppException("Sorry, unpleasant attempt detected!");
                
            }
            
            if (empty($topic_title)) {
                
                $checkError = false;
                array_push($errors, "Please enter title");
                
            }
            
            if (!$checkError) {
                
                $this->setView('edit-topic');
                $this->setPageTitle('Edit Topic');
                $this->setFormAction('editTopic');
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('topicData', $data_topic);
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                
            } else {
                
                $this->topicEvent->setTopicId($topic_id);
                $this->topicEvent->setTopicTitle($topic_title);
                $this->topicEvent->setTopicSlug($topic_slug);
                $this->topicEvent->setTopicStatus($topic_status);
                $this->topicEvent->modifyTopic();
                direct_page('index.php?load=topics&status=topicUpdated', 200);
                
            }
            
        } catch (AppException $e) {
            
            LogError::setStatusCode(http_response_code());
            LogError::newMessage($e);
            LogError::customErrorMessage('admin');
            
        }
        
    } else {
          
      $this->setView('edit-topic');
      $this->setPageTitle('Edit Topic');
      $this->setFormAction('editTopic');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('topicData', $data_topic);
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
      
    }
    
    return $this->view->render();
    
  }
  
  public function remove($id)
  {
    $this->topicEvent->setTopicId($id);
    $this->topicEvent->removeTopic();
    direct_page('index.php?load=topics&status=topicDeleted', 200);
  }
  
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'posts', $viewName);
  }
  
}