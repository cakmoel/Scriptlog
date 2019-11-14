<?php
/**
 * Class ConfigurationApp extends BaseApp
 *
 * @package   SCRIPTLOG/LIB/APP/ConfigurationApp
 * @category  App Class
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
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if(isset($_GET['error'])) {
      $checkError = false;
      if($_GET['error'] == 'configNotFound') array_push($errors, "Error: Setting Not Found!");
    }

    if (isset($_GET['status'])) {
       $checkStatus = true;
       if ($_GET['status'] == 'configAdded') array_push($status, "New setting added");
       if ($_GET['status'] == 'configUpdated') array_push($status, "Setting has been updated");
       if ($_GET['status'] == 'configDeleted') array_push($status, "Setting deleted");
    }

      
    $this->setView('all-settings');  
    $this->setPageTitle('Settings');

    if (!$checkError) {
      $this->view->set('errors', $errors);
    }

    if ($checkStatus) {
      $this->view->set('status', $status);
    }

    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('settingsTotal', $this->configEvent->totalSettings());
    $this->view->set('settings', $this->configEvent->grabSettings());
    return $this->view->render();

  }

  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['configFormSubmit'])) {
      
      $setting_name = (isset($_POST['setting_name'])) ? filter_var($_POST['setting_name'], FILTER_SANITIZE_STRING) : "";
      $setting_value = (isset($_POST['setting_value'])) ? filter_var($_POST['setting_value'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : "";
      $setting_desc = prevent_injection($_POST['setting_desc']);

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (empty($setting_name) || empty($setting_value)) {

          $checkError = false;
          array_push($errors, "Column name and value required must be filled");

        }

        if (strlen($setting_name) > 100) {

           $checkError = false;
           array_push($errors, "Exceeded characters limit. Maximum 100 characters are allowed.");

        }

        if (strlen($setting_desc) > 300) {

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
          $this->configEvent->setConfigDesc($setting_desc);
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
      'setting_desc' => $getSetting['setting_desc']
    );

    if (isset($_POST['configFormSubmit'])) {

      $setting_id = isset($_POST['setting_id']) ? abs((int)$_POST['setting_id']) : 0;
      $setting_name = filter_input('INPUT_POST', 'setting_name', FILTER_SANITIZE_STRING);
      $setting_value = filter_input('INPUT_POST', 'setting_value', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
      $setting_desc = prevent_injection($_POST['setting_desc']);
      
      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$checkError) {

          $this->setView('edit-setting');
          $this->setPageTitle('Edit Setting');
          $this->setFormAction('editConfig');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('configData', $data_config);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->configEvent->setConfigId($setting_id);
          $this->configEvent->setConfigName($setting_name);
          $this->configEvent->setConfigValue($setting_value);
          $this->configEvent->setConfigDesc($setting_desc);
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
      $this->setFormAction('editConfig');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('settingData', $data_config);
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