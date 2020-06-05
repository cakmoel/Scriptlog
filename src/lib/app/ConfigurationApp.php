<?php
/**
 * Class ConfigurationApp 
 *
 * @category  Class Configuration extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConfigurationApp extends BaseApp
{
  private $view;

  private $configEvent;

  public function __construct(ConfigurationEvent $configEvent)
  {
    $this->configEvent = $configEvent;
  }

  public function ListItems()
  {
    
  }

  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['configFormSubmit'])) {
      
      $setting_name = (isset($_POST['setting_name'])) ? filter_var($_POST['setting_name'], FILTER_SANITIZE_STRING) : "";
      $setting_value = (isset($_POST['setting_value'])) ? filter_var($_POST['setting_value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : "";
      
      $filters = [ 'setting_name' => FILTER_SANITIZE_STRING, 'setting_value' => FILTER_SANITIZE_FULL_SPECIAL_CHARS];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (empty($_POST['setting_name']) || empty($_POST['setting_value'])) {

          $checkError = false;
          array_push($errors, "Column name and value required must be filled");

        }

        if (isset($_POST['setting_name']) && (strlen($_POST['setting_name']) > 100)) {

           $checkError = false;
           array_push($errors, "Exceeded characters limit. Maximum 100 characters are allowed.");

        }

        if (isset($_POST['setting_value']) && (strlen($_POST['setting_value']) > 300)) {

           $checkError = false;
           array_push($errors, "Exceeded characters limit. Maximum 300 characters are allowed.");

        }

        if (!$checkError) {

           $this->setView('edit-setting');
           $this->setPageTitle('Add New Setting');
           $this->setFormAction('newConfig');
           $this->view->set('pageTitle', $this->getPageTitle());
           $this->view->set('formAction', $this->getFormAction());
           $this->view->set('errors', $errors);
           $this->view->set('formData', $_POST);
           $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->configEvent->setConfigName($setting_name);
          $this->configEvent->setConfigValue($setting_value);
          $this->configEvent->addSetting();
          direct_page('index.php?load=settings&status=configAdded', 200);
           
        }

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {

       $this->setView('edit-setting');
       $this->setPageTitle('Add New Setting');
       $this->setFormAction('newConfig');
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

    if (!$getSetting = $this->configEvent->grabSetting($id)) {

      direct_page('index.php?load=settings&error=configNotFound', 404);

    }

    $data_config = array(
      'ID' => $getSetting['ID'],
      'setting_name' => $getSetting['setting_name'],
      'setting_value' => $getSetting['setting_value'],
      
    );

    if (isset($_POST['configFormSubmit'])) {

      $setting_id = isset($_POST['setting_id']) ? abs((int)$_POST['setting_id']) : 0;
      $setting_name = filter_input('INPUT_POST', 'setting_name', FILTER_SANITIZE_STRING);
      $setting_value = filter_input('INPUT_POST', 'setting_value', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
     
      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$checkError) {

          $this->setView('edit-setting');
          $this->setPageTitle('Edit Setting');
          $this->setFormAction(ActionConst::EDITCONFIG);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('configData', $data_config);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->configEvent->setConfigId($setting_id);
          $this->configEvent->setConfigName($setting_name);
          $this->configEvent->setConfigValue($setting_value);
        
          $this->configEvent->modifySetting();
          direct_page('index.php?load=settings&status=configUpdated', 200);

        }

      } catch (AppException $e) {

         LogError::setStatusCode(http_response_code());
         LogError::newMessage($e);
         LogError::customErrorMessage('admin');

      }

    } else {
      
      $this->setView('edit-setting');
      $this->setPageTitle('Edit Setting');
      $this->setFormAction(ActionConst::EDITCONFIG);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('settingData', $data_config);
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
      
    }

    return $this->view->render();

  }

  /**
   * Update Common setting
   *
   * @param string $args
   * @return void
   * 
   */
  public function updateGeneralSetting()
  {
    
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_POST['configFormSubmit'])) {

       $filters = [

          'setting_id' => [
                'filter' => FILTER_VALIDATE_INT, 
                'flags' => FILTER_REQUIRE_ARRAY],

          'setting_value' => [
                'filter' => FILTER_FLAG_NO_ENCODE_QUOTES, 
                'flags' => FILTER_REQUIRE_ARRAY]

       ];

      $size = (!empty($_POST['setting_value'])) ? count($_POST['setting_value']) : null;
       
      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if(isset($_POST['setting_value']['1']) && strcmp($_POST['setting_value']['1'], app_key())) {

          $checkError = false;
          array_push($errors, "Application key does not match with configuration file");
             
        }
        
        if (!$checkError) {

           $this->setView('general-setting');
           $this->setPageTitle('General Settings');
           $this->setFormAction(ActionConst::GENERAL_CONFIG);
           $this->view->set('pageTitle', $this->getPageTitle());
           $this->view->set('settings',  $this->configEvent->grabGeneralSettings('ID', 7));
           $this->view->set('errors', $errors);
           $this->view->set('formAction', $this->getFormAction());
           $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
           
        } else {

           $i = 0;
           while ($i < $size) {

               $setting_value = prevent_injection(distill_post_request($filters)['setting_value'][$i]);
               $setting_id = distill_post_request($filters)['setting_id'][$i];

               $sql = "UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = ".(int)$setting_id;
               db_simple_query($sql);
               ++$i;

           }

           direct_page('index.php?load=option-general&status=generalConfigUpdated', 200);

        }

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {

       if(isset($_GET['error'])) {
         $checkError = false;
         if($_GET['error'] == 'configTampered') array_push($errors, "Error: Attempt to tampered with our parameters!");
       }
  
       if (isset($_GET['status'])) {
         $checkStatus = true;
         if ($_GET['status'] == 'generalConfigUpdated') array_push($status, "General setting has been updated");
       }

       $this->setView('general-setting');
       $this->setPageTitle('General Settings');
       $this->setFormAction(ActionConst::GENERAL_CONFIG);

       if (!$checkError) {
        $this->view->set('errors', $errors);
       }
  
       if ($checkStatus) {
        $this->view->set('status', $status);
       }

       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('settings', $this->configEvent->grabGeneralSettings('ID', 7));
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  public function updatePermalinkConfig()
  {

    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if(!$getPermalinkValue = $this->configEvent->grabPermalinkSetting('permalink_setting')) {

      direct_page('index.php?load=option-permalink&error=permalinkValueNotFound', 404);
      
    }

    $data_permalink = array(
      'ID' => $getPermalinkValue['ID'],
      'setting_name' => $getPermalinkValue['setting_name'],
      'setting_value' => $getPermalinkValue['setting_value']
    );

    if(isset($_POST['configFormSubmit'])) {

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

      if(isset($_GET['error'])) {
        $checkError = false;
        if($_GET['error'] == 'permalinkValueNotFound') array_push($errors, "Error: Permalink value not found!");
      }
 
      if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'permalinkConfigUpdated') array_push($status, "Permalink setting has been updated");
      }

      $this->setView('permalink-setting');
      $this->setPageTitle('Permalink Setting');
      $this->setFormAction(ActionConst::PERMALINK_CONFIG);

      if (!$checkError) {
       $this->view->set('errors', $errors);
      }
 
      if ($checkStatus) {
       $this->view->set('status', $status);
      }

      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('settingData', $data_permalink);
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }
  
  public function remove($id)
  {
    $this->configEvent->setConfigId($id);
    $this->configEvent->removeSetting();
    direct_page('index.php?load=settings&status=configDeleted', 200);
  }

  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'setting', $viewName);
  }

}