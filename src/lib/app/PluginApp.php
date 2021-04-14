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

/**
 * view
 *
 * @var object
 * 
 */
  private $view;

/**
 * pluginEvent
 *
 * @var object
 * 
 */
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
       
      // get extension
      $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
      $validate_ext = (strtolower($file_extension) == 'zip' ? true : false);
      $plugin_ini = null;
      
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

        if (($file_size > scriptlog_upload_filesize()) || (format_size_unit(filesize($file_location)) == '0 bytes')) {

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

        if ((is_dir(__DIR__ .'/../../'.APP_PLUGIN.current(explode(".",$file_name)).DS))) {
           
          $checkError = false;
          array_push($errors, "Sorry you have installed this plugin before.");

        } 
       
        if ($this->pluginEvent->isPluginExists(current(explode(".",$file_name))) == true) {

          $checkError = false;
          array_push($errors, "Sorry you have installed this plugin before.");

        }

        if (is_uploaded_file($file_location)) {

          if ( (!$validate_ext) || (false === check_mime_type(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'], $file_location))) {

               $checkError = false;
               array_push($errors, "Invalid file format");

          } else {

            if (upload_plugin($file_location, basename($file_name)) === false) {

                $checkError = false;
                array_push($errors, "Zip file corrupted");

            }

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

          if (file_exists(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip').DS.'plugin.ini')) {

            $plugin_ini = parse_ini_file(__DIR__ . '/../../'.APP_PLUGIN.basename($file_name, '.zip').DS.'plugin.ini');

            $plugin_link = generate_request('index.php', 'get', [$plugin_ini['plugin_loader'], $plugin_ini['plugin_action'], 0])['link'];

            $this->pluginEvent->setPluginName($plugin_ini['plugin_name']);
            $this->pluginEvent->setPluginLink($plugin_link);
            $this->pluginEvent->setPluginDirectory($plugin_ini['plugin_directory']);
            $this->pluginEvent->setPluginDescription($plugin_ini['plugin_description']);
            $this->pluginEvent->setPluginLevel($plugin_ini['plugin_level']);
            $this->pluginEvent->addPlugin();

            direct_page("index.php?load=plugins&status=pluginInstalled", 200);

          } else {

            direct_page("index.php?load=plugins", 302);

          }
          
        }

      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch(AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);

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
    
  }

  public function enablePlugin($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

       $getPlugin = $this->pluginEvent->grabPlugin($id);

       try {
         
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$getPlugin) {

          $checkError =false;
          array_push($errors, 'Error: Plugin not found');

        }

        if (!$checkError) {

           $this->setView('all-plugins');
           $this->setPageTitle('Plugin not found');
           $this->view->set('pageTitle', $this->getPageTitle());
           $this->view->set('errors', $errors);
           $this->view->set('pluginsTotal', $this->pluginEvent->totalPlugins());
           $this->view->set('plugins', $this->pluginEvent->grabPlugins());
           return $this->view->render();

        } else {

          $this->pluginEvent->setPluginId($id);
    
          if ( $this->pluginEvent->activateInstalledPlugin() === true ) {

          direct_page('index.php?load=plugins&status=pluginActivated', 200);

          }

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

  public function disablePlugin($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getPlugin = $this->pluginEvent->grabPlugin($id); 

      try {
      
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$getPlugin) {

           $checkError = false;
           array_push($errors, 'Error: Plugin not found');

        }

        if (!$checkError) {

           $this->setView('all-plugins');
           $this->setPageTitle('Plugin not found');
           $this->view->set('pageTitle', $this->getPageTitle());
           $this->view->set('errors', $errors);
           $this->view->set('pluginsTotal', $this->pluginEvent->totalPlugins());
           $this->view->set('plugins', $this->pluginEvent->grabPlugins());
           return $this->view->render();

        } else {

          $this->pluginEvent->setPluginId($id);
          $this->pluginEvent->deactivateInstalledPlugin();
          direct_page('index.php?load=plugins&status=pluginDeactivated', 200);

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

  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getPlugin = $this->pluginEvent->grabPlugin($id);

       try {
      
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
        
        if (!$getPlugin) {

          $checkError = false;
          array_push($errors, 'Error: Plugin not found');

        }

        if (!$checkError) {

          $this->setView('all-plugins');
          $this->setPageTitle('Plugin not found');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('errors', $errors);
          $this->view->set('pluginsTotal', $this->pluginEvent->totalPlugins());
          $this->view->set('plugins', $this->pluginEvent->grabPlugins());
          return $this->view->render();
 
        } else {

          $this->pluginEvent->setPluginId($id);
          $this->pluginEvent->removePlugin();
          direct_page('index.php?load=plugins&status=pluginDeleted', 200);

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
    $this->view = new View('admin', 'ui', 'plug-in', $viewName);
  }
  
}