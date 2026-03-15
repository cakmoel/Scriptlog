<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class TopicController
 *
 * @category  Class TopicController extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class TopicController extends BaseApp
{
  private $view;

  private $topicService;
  
  public function __construct(TopicService $topicService)
  {
    $this->topicService = $topicService;
  }

/**
 * listItems
 *
 * @inheritDoc
 * @uses BaseApp::listItems
 * 
 */
  public function listItems()
  {
    
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
     
    if (isset($_SESSION['status'])) {
        $checkStatus = true;
        ($_SESSION['status'] == 'topicAdded') ? array_push($status, "New cateogory added") : "";
        ($_SESSION['status'] == 'topicUpdated') ? array_push($status, "Category has been updated") : "";
        ($_SESSION['status'] == 'topicDeleted') ? array_push($status, "Category deleted") : "";
        unset($_SESSION['status']);
    }

    if (isset($_SESSION['error'])) {
      $checkError = false;
      ($_SESSION['error'] == 'topicNotFound') ? array_push($errors, "Error: Topic Not Found!") : "";
      unset($_SESSION['error']);
    }
    
    $this->setView('all-topics');
    $this->setPageTitle('Categories');
    
    if (!$checkError) {
       $this->view->set('error', $errors);
    }
    
    if ($checkStatus) {
        $this->view->set('status', $status);
    }
    
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('topicsTotal', $this->topicService->totalTopics());
    $this->view->set('categories', $this->topicService->grabTopics());
    return $this->view->render();
    
  }
  
/**
 * insert
 * 
 * @inheritDoc
 * @uses BaseApp::insert
 *
 * @return void
 */
  public function insert()
  {
    
    $errors = array();
    $checkError = true;
    
    if (isset($_POST['topicFormSubmit'])) {
       
      $filters = ['topic_title' => isset($_POST['topic_title']) ? Sanitize::severeSanitizer($_POST['topic_title']) : ""];
     
      try {
          
          if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
              header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
              throw new AppException("Sorry, unpleasant attempt detected!");
              
          }
          
          if (empty($_POST['topic_title'])) {
              
              $checkError = false;
              array_push($errors, "Please enter title");
              
          }
          
          if (!$checkError) {
              
             $this->setView('edit-topic');
             $this->setPageTitle('Add New Category');
             $this->setFormAction(ActionConst::NEWTOPIC);
             $this->view->set('pageTitle', $this->getPageTitle());
             $this->view->set('formAction', $this->getFormAction());
             $this->view->set('errors', $errors);
             $this->view->set('formData', $_POST);
             $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
             
          } else {
              
             $this->topicService->setTopicTitle(prevent_injection(trim(distill_post_request($filters)['topic_title'])));
             $this->topicService->setTopicSlug(make_slug(distill_post_request($filters)['topic_title']));
             $this->topicService->addTopic();
             $_SESSION['status'] = "topicAdded";
             direct_page('index.php?load=topics&status=topicAdded', 302);
             
          }
          
      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {
         
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);

      }
      
    } else {
      
       $this->setView('edit-topic');
       $this->setPageTitle('Add New Category');
       $this->setFormAction(ActionConst::NEWTOPIC);
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            
    }
     
    return $this->view->render();
    
  }
  
/**
 * update
 *
 * @inheritDoc
 * @param int|num $id
 */
  public function update($id)
  {
      
    $errors = array();
    $checkError = true;
    
    if (!$getCategory = $this->topicService->grabTopic($id)) {
      
      $_SESSION['error'] = "topicNotFound";
      direct_page('index.php?load=topics&error=topicNotFound', 404);
        
    }
    
    $data_topic = array(
        'ID' => $getCategory['ID'],
        'topic_title' => $getCategory['topic_title'],
        'topic_slug' => $getCategory['topic_slug'],
        'topic_status' => $getCategory['topic_status']
    );
    
    if (isset($_POST['topicFormSubmit'])) {
        
        $filters = ['topic_title' => isset($_POST['topic_title']) ? Sanitize::severeSanitizer($_POST['topic_title']) : "", 
                    'topic_status' => isset($_POST['topic_status']) ? Sanitize::mildSanitizer($_POST['topic_status']) : "", 
                    'topic_id' => FILTER_SANITIZE_NUMBER_INT];
 
        try {
            
            if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new AppException("Sorry, unpleasant attempt detected!");
                
            }
            
            if (empty($_POST['topic_title'])) {
                
                $checkError = false;
                array_push($errors, "Please enter title");
                
            }

            if (false === sanitize_selection_box(distill_post_request($filters)['topic_status'], ['Y', 'N'])) {

               $checkError = false;
               array_push($errors, "Please choose the available value provided!");

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
                
                $this->topicService->setTopicId((int)distill_post_request($filters)['topic_id']);
                $this->topicService->setTopicTitle(prevent_injection(distill_post_request($filters)['topic_title']));
                $this->topicService->setTopicSlug(make_slug(distill_post_request($filters)['topic_title']));
                $this->topicService->setTopicStatus(distill_post_request($filters)['topic_status']);
                $this->topicService->modifyTopic();
                $_SESSION['status'] = "topicUpdated";
                direct_page('index.php?load=topics&status=topicUpdated', 302);
                
            }
            
        } catch (Throwable $th) {

          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($th);
          
        } catch (AppException $e) {
            
          LogError::setStatusCode(http_response_code());
          LogError::exceptionHandler($e);
            
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

/**
 * remove
 *
 * @inheritDoc
 * @uses BaseApp::remove 
 * @param int|num $id
 */
  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getTopic = $this->topicService->grabTopic($id);

      try {
        
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$getTopic) {

            $checkError = false;
            array_push($errors, 'Error: Topic not found');

        }

        if (!$checkError) {

          $this->setView('all-topics');
          $this->setPageTitle('Topic not found');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('errors', $errors);
          $this->view->set('topicsTotal', $this->topicService->totalTopics());
          $this->view->set('topics', $this->topicService->grabTopics());
          return $this->view->render();

        } else {

          $this->topicService->setTopicId($id);
          $this->topicService->removeTopic();
          $_SESSION['status'] = "topicDeleted";
          direct_page('index.php?load=topics&status=topicDeleted', 302);

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