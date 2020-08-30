<?php
/**
 * Class PluginApp
 * 
 * @category Class PluginApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 * 
 */
class PluginApp extends BaseApp
{
  private $view;

  private $pluginEvent;

  public function __construct(PluginEvent $pluginEvent)
  {
    $this->pluginEvent = $pluginEvent;
  }

  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_GET['error'])) {
       
       $checkError = false;
       if ($_GET['error'] == 'pluginNotFound') array_push($errors, "Error: Plugin Not Found!");
       if ($_GET['error'] == 'tableNotFound') array_push($errors, "Error: Table Plugin Not Found");

    }

    if (isset($_GET['status'])) {

      $checkStatus = true;
      if ($_GET['status'] == 'pluginAdded') array_push($status, "New plugin added");
      if ($_GET['status'] == 'pluginInstalled') array_push($status, "New plugin installed");
      if ($_GET['status'] == 'pluginUpdated') array_push($status, "Plugin updated");
      if ($_GET['status'] == 'pluginActivated') array_push($status, "Plugin actived");
      if ($_GET['status'] == 'pluginDeactivated') array_push($status, "Plugin deactivated");
      if ($_GET['status'] == 'pluginDeleted') array_push($status, "Plugin deleted");
      
    }

    $this->setView('all-plugins');
    $this->setPageTitle('Plugins');
    $this->view->set('pageTitle', $this->getPageTitle());

    if (!$checkError) {
      $this->view->set('errors', $errors);
    }

    if ($checkStatus) {
       $this->view->set('status', $status);
    }

    $this->view->set('pluginsTotal', $this->pluginEvent->totalPlugins());
    $this->view->set('plugins', $this->pluginEvent->grabPlugins());
    return $this->view->render();

  }

  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['pluginFormSubmit'])) {

      $plugin_name = trim($_POST['plugin_name']);
      $plugin_link = (isset($_POST['plugin_link']) ? filter_var($_POST['plugin_link'], FILTER_SANITIZE_URL) : "");
      $plugin_desc = prevent_injection($_POST['description']);
      $plugin_level = $_POST['plugin_level'];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($plugin_name) || empty($plugin_desc)) {
           $checkError = false;
           array_push($errors, "All columns required must be filled.");
        }

        if($this->pluginEvent->isPluginExists($plugin_name) === true) {
          $checkError = false;
          array_push($errors, "Sorry you have installed this plugin before.");
        }

        if($plugin_link != '') {

          $parseOutQuery = parse_query($plugin_link);

           if(!filter_var($plugin_link, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
             
             $checkError = false;
             array_push($errors, "Invalid plugin link.");

           } elseif($plugin_link !== unparse_url(parse_url($plugin_link))) {
             
             $checkError = false;
             array_push($errors, "Not match!.");

           } elseif($parseOutQuery['load'] !== $plugin_name) {

             $checkError = false;
             array_push($errors, "Invalid query string. Query string must be same with Plugin name");

           }

        }

        if (!$checkError) {

          $this->setView('edit-plugin');
          $this->setPageTitle('Add New Plugin');
          $this->setFormAction('newPlugin');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

           $this->pluginEvent->setPluginName($plugin_name);
           $this->pluginEvent->setPluginLink($plugin_link);
           $this->pluginEvent->setPluginDescription($plugin_desc);
           $this->pluginEvent->setPluginLevel($plugin_level);
           $this->pluginEvent->addPlugin();
           direct_page('index.php?load=plugins&status=pluginAdded', 200);

        }
        
      } catch(AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');
            
      }
      
    } else {
       
       $this->setView('edit-plugin');
       $this->setPageTitle('Add New Plugin');
       $this->setFormAction('newPlugin');
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('pluginLevel', $this->pluginEvent->pluginLevelDropDown());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  public function installPlugin()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['pluginFormSubmit'])) {

      $file_name = isset($_FILES['zip_file']['name']) ? $_FILES['zip_file']['name']  : null;
      $file_size = isset($_FILES['zip_file']['size']) ? $_FILES['zip_file']['size'] : null;
      $file_location = isset($_FILES['zip_file']['tmp_name']) ? $_FILES['zip_file']['tmp_name'] : null;
      $file_type = isset($_FILES['zip_file']['type']) ? $_FILES['zip_file']['type'] : null;
      $file_error = isset($_FILES['zip_file']['error']) ? $_FILES['zip_file']['error'] : null;
      
      $plugin_desc = prevent_injection($_POST['description']);
      $plugin_level = (isset($_POST['plugin_level']) ? $_POST['plugin_level'] : "");
      $plugin_name = current(explode(".", $file_name));
      $extension = [];
      $split = explode(".", $file_name);
      $extension = (array_key_exists(1, $split) ? $split[1] : null);
      $field = array('load', $plugin_name);
      $plugin_link = app_url().'/admin/index.php?'.http_build_query($field);
      $validate_format = (strtolower($extension) == 'zip' ? true : false);
      
      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
          
        }

        if (!isset($file_error) || is_array($file_error)) {

          $checkError = false;
          array_push($errors, "Invalid paramenter");
          
        }

        switch ($file_error) {
  
          case UPLOAD_ERR_OK:
              break;
          case UPLOAD_ERR_NO_FILE:
             
             $checkError = false;
             array_push($errors, "No file uploaded");
  
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

        if ($file_size > scriptlog_upload_filesize()) {

          $checkError = false;
          array_push($errors, "Exceeded file size limit. Maximum file size is. ".format_size_unit(scriptlog_upload_filesize()));

        }

        if (false === check_file_name($file_location)) {

           $checkError = false;
           array_push($errors, "file name is not valid");

        }

        if (true == check_file_length($file_location)) {

            $checkError = false;
            array_push($errors, "file name is too long");

        }

        if ( empty($plugin_desc) ) {

           $checkError = false;
           array_push($errors, "The description columns must be filled.");

        }
        
        if (!is_writable(__DIR__ .'/../'.APP_LIBRARY.DS.'plugins'.DS)) {
           
           $checkError = false;
           array_push($errors, "Permission denied.");

        } elseif ((is_dir(__DIR__ .'/../'.APP_LIBRARY.DS.'plugins'.DS.$plugin_name.DS)) || (is_readable(__DIR__ .'/../'.APP_LIBRARY.DS.'plugins'.DS.basename($plugin_name).'.php'))) {
           
           $checkError = false;
           array_push($errors, "Sorry you have installed this plugin before.");

        } elseif ($this->pluginEvent->isPluginExists($plugin_name) == true) {

           $checkError = false;
           array_push($errors, "Sorry you have installed this plugin before.");

        }

        if (is_uploaded_file($file_location)) {

            if ((!$validate_format) || false === check_mime_type(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'], $file_location)) {

                 $checkError = false;
                 array_push($errors, "Invalid file format.Make sure you have a .zip format");

            } else {

              upload_plugin($file_name, $file_location, ["..", ".git", ".svn", "composer.json", "composer.lock", "framework_config.yaml", ".php", ".html", ".phtml", ".php5", ".php4", ".pl", ".py", ".sh", ".htaccess"]);

            }

        }

        if (!$checkError) {
         
          $this->setView('install-plugin');
          $this->setPageTitle('Upload Plugin');
          $this->setFormAction(ActionConst::INSTALLPLUGIN);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('pluginLevel', $this->pluginEvent->pluginLevelDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {
          
          $this->pluginEvent->setPluginName($plugin_name);
          $this->pluginEvent->setPluginLink($plugin_link);
          $this->pluginEvent->setPluginDescription($plugin_desc);
          $this->pluginEvent->setPluginLevel($plugin_level);
          $this->pluginEvent->addPlugin();
          
          direct_page("index.php?load=plugins&status=pluginInstalled", 200);
          
        }

      } catch(AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {
       
       $this->setView('install-plugin');
       $this->setPageTitle('Upload Plugin');
       $this->setFormAction(ActionConst::INSTALLPLUGIN);
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('pluginLevel', $this->pluginEvent->pluginLevelDropDown());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();
  }

  public function update($id)
  {
    $errors = array();
    $checkError = true;

    if(!$getPlugin = $this->pluginEvent->grabPlugin($id)) {
      direct_page('index.php?load=plugins&error=pluginNotFound', 404);
    }

    $data_plugin = array(
      'ID' => $getPlugin['ID'],
      'plugin_name' => $getPlugin['plugin_name'],
      'plugin_link' => $getPlugin['plugin_link'],
      'plugin_desc' => $getPlugin['plugin_desc'],
      'plugin_status' => $getPlugin['plugin_status'],
      'plugin_level' => $getPlugin['plugin_level'],
      'plugin_sort' => $getPlugin['plugin_sort']
    );
    
    if(isset($_POST['pluginFormSubmit'])) {

      $plugin_name = trim($_POST['plugin_name']);
      $plugin_link = (isset($_POST['plugin_link']) ? filter_var($_POST['plugin_link'], FILTER_SANITIZE_URL) : "");
      $plugin_desc = prevent_injection($_POST['description']);
      $plugin_status = $_POST['plugin_status'];
      $plugin_level = (isset($_POST['plugin_level']) ? $_POST['plugin_level'] : "");
      $plugin_sort = (int)$_POST['plugin_sort'];
      $plugin_id = abs((int)$_POST['plugin_id']);

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
          
        }

        if (empty($plugin_name) || empty($plugin_desc)) {
          $checkError = false;
          array_push($errors, "All columns required must be filled");
        }

        if($plugin_link != '') {

          $parseOutQuery = parse_query($plugin_link);

           if(!filter_var($plugin_link, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
             
             $checkError = false;
             array_push($errors, "Invalid plugin link");

           } elseif($plugin_link !== unparse_url(parse_url($plugin_link))) {
             
             $checkError = false;
             array_push($errors, "Not match!");

           } elseif($parseOutQuery['load'] !== $plugin_name) {

             $checkError = false;
             array_push($errors, "Invalid query string. Query string must be same with Plugin name");

           }

        }
        
        if(!$checkError) {

          $this->setView('edit-plugin');
          $this->setPageTitle('Edit Plugin');
          $this->setFormAction('editPlugin');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('pluginData', $data_plugin);
          $this->view->set('pluginLevel', $this->pluginEvent->pluginLevelDropDown($getPlugin['plugin_level']));
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->pluginEvent->setPluginId($plugin_id);
          $this->pluginEvent->setPluginName($plugin_name);
          $this->pluginEvent->setPluginLink($plugin_link);
          $this->pluginEvent->setPluginDescription($plugin_desc);
          $this->pluginEvent->setPluginStatus($plugin_status);
          $this->pluginEvent->setPluginLevel($plugin_level);
          $this->pluginEvent->setPluginSort($plugin_sort);
          $this->pluginEvent->modifyPlugin();
          direct_page('index.php?load=plugins&status=pluginUpdated', 200);
          
        }
                
      } catch(AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');
        
      }

    } else {

      $this->setView('edit-plugin');
      $this->setPageTitle('Edit Plugin');
      $this->setFormAction('editPlugin');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('pluginData', $data_plugin);
      $this->view->set('pluginLevel', $this->pluginEvent->pluginLevelDropDown($getPlugin['plugin_level']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();
    
  }

  public function enablePlugin($id)
  {
    $this->pluginEvent->setPluginId($id);
    $this->pluginEvent->activateInstalledPlugin();
    direct_page('index.php?load=plugins&status=pluginActivated', 200);
  }

  public function disablePlugin($id)
  {
   $this->pluginEvent->setPluginId($id);
   $this->pluginEvent->deactivateInstalledPlugin();
   direct_page('index.php?load=plugins&status=pluginDeactivated', 200);
  }

  public function remove($id)
  {
    $this->pluginEvent->setPluginId($id);
    $this->pluginEvent->removePlugin();
    direct_page('index.php?load=plugins&status=pluginDeleted', 200);
  }

  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'plug-in', $viewName);
  }
  
}