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
   
  }

  public function update($id)
  {
  
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
          array_push($errors, "Application key does not match with the configuration file");
             
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

               $setting_value = purify_dirty_html(distill_post_request($filters)['setting_value'][$i]);
               $setting_id = distill_post_request($filters)['setting_id'][$i];

               $sql = sprintf("UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = %d", (int)$setting_id);
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

    if(!$getPermalinkValue = $this->configEvent->grabSettingByName('permalink_setting')) {

      direct_page('index.php?load=option-permalink&error=permalinkValueNotFound', 404);
      
    }

    $data_permalink = array(
      'ID' => $getPermalinkValue['ID'],
      'setting_name' => $getPermalinkValue['setting_name'],
      'setting_value' => $getPermalinkValue['setting_value']
    );

    $filters = ['permalinks' => FILTER_SANITIZE_STRING, 'setting_id' => FILTER_SANITIZE_NUMBER_INT, 'setting_name' => FILTER_SANITIZE_STRING];
    
    if(isset($_POST['configFormSubmit'])) {

      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
        
        }

        if(false === sanitize_selection_box(distill_post_request($filters)['permalinks'], ['yes', 'no'])) {

           $checkError = false;
           array_push($errors, "Please choose the available value provided!");

        }

        if(!$checkError) {

          $this->setView('permalink-setting');
          $this->setPageTitle('Permalink Setting');
          $this->setFormAction(ActionConst::PERMALINK_CONFIG);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('settingData', $data_permalink);
          $this->view->set('errors', $errors);
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->configEvent->setConfigId((int)distill_post_request($filters)['setting_id']);
          $this->configEvent->setConfigName(prevent_injection(distill_post_request($filters)['setting_name']));
          $this->configEvent->setConfigValue(distill_post_request($filters)['permalinks']);
          $this->configEvent->modifySetting();

          direct_page('index.php?load=option-permalink&status=permalinkConfigUpdated', 200);
           
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