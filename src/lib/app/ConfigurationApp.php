<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
class ConfigurationApp
{

/**
 * view
 *
 * @var object
 * 
 */
  private $view;

/**
 * configEvent
 *
 * @var object
 * 
 */
  private $configEvent;

  /**
   * pageTitle
   *
   * @var string
   * 
   */
  protected $pageTitle;

  /**
   * formAction
   * 
   * @var string
   * 
   */
  protected $formAction;

  public function __construct(ConfigurationEvent $configEvent)
  {
    $this->configEvent = $configEvent;
  }

  public function setPageTitle($pageTitle)
  {
    $this->pageTitle = $pageTitle;
  }

  public function getPageTitle()
  {
    return $this->pageTitle;
  }

  public function setFormAction($formAction)
  {
    $this->formAction = $formAction;
  }

  public function getFormAction()
  {
    return $this->formAction;
  }

  /**
   * UpdateGeneralSetting
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

      $size = (!empty($_POST['setting_value']) ? count($_POST['setting_value']) : null);
       
      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (isset($_POST['setting_value']['1']) && strcmp($_POST['setting_value']['1'], app_key())) {

          $checkError = false;
          array_push($errors, "Application key does not match with the configuration file");
             
        }
        
        if (!$checkError) {

           $this->setView('general-setting');
           $this->setPageTitle('General Settings');
           $this->setFormAction(ActionConst::GENERAL_CONFIG);
           $this->view->set('pageTitle', $this->getPageTitle());
           $this->view->set('formAction', $this->getFormAction());
           $this->view->set('settings',  $this->configEvent->grabGeneralSettings('ID', 7));
           $this->view->set('errors', $errors);
           $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
           
        } else {

           for ($i=1; $i<=$size; $i++) { 

             $setting_value = purify_dirty_html(distill_post_request($filters)['setting_value'][$i]);
             $setting_id = distill_post_request($filters)['setting_id'][$i];

             $sql = sprintf("UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = %d", (int)$setting_id);
             db_simple_query($sql);

           }

           $_SESSION['status'] = "generalConfigUpdated";
           direct_page('index.php?load=option-general&status=generalConfigUpdated', 302);

        }

      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);

      }

    } else {

       if ((isset($_SESSION['status'])) && ($_SESSION['status'] == 'generalConfigUpdated')) {
         $checkStatus = true;
         array_push($status, "General setting has been updated");
         unset($_SESSION['status']);
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

  /**
   * updateReadingSetting
   *
   * @return array|mixed
   * 
   */
  public function updateReadingSetting()
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

     $size = (!empty($_POST['setting_value']) ? count($_POST['setting_value']) : null);
     
    try {
       
      if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
        throw new AppException("Sorry, unpleasant attempt detected!");

      }

      if (isset($_POST['setting_value']['8']) && is_numeric($_POST['setting_value']['8']) === false) {

        $checkError = false;
        array_push($errors, "Invalid post per page value");
          
      }
      
      if (isset($_POST['setting_value']['9']) && is_numeric($_POST['setting_value']['9']) === false) {

        $checkError = false;
        array_push($errors, 'Invalid post per rss value');
         
      }

      if (!$checkError) {

         $this->setView('reading-setting');
         $this->setPageTitle('Reading Settings');
         $this->setFormAction(ActionConst::READING_CONFIG);
         $this->view->set('pageTitle', $this->getPageTitle());
         $this->view->set('formAction', $this->getFormAction());
         $this->view->set('errors', $errors);
         $this->view->set('settings', $this->configEvent->grabReadingSettings());
         $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

      } else {

        for ($i=0; $i<$size; $i++) { 

          $setting_value = purify_dirty_html(distill_post_request($filters)['setting_value'][$i]);
          $setting_id = distill_post_request($filters)['setting_id'][$i];

          $sql = sprintf( "UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = %d", (int)$setting_id );
          db_simple_query($sql);

        }

        $_SESSION['status'] = "readingConfigUpdated";
        direct_page('index.php?load=option-reading&status=readingConfigUpdated', 302);

      }
      
    } catch (\Throwable $th) {
       
       LogError::setStatusCode(http_response_code());
       LogError::exceptionHandler($th);

    } catch (AppException $e) {

       LogError::setStatusCode(http_response_code());
       LogError::exceptionHandler($e);

     }

    } else {

      if (isset($_SESSION['status'])) {

        $checkStatus = true;
        ($_SESSION['status'] == 'readingConfigUpdated') ? array_push($status, "Reading setting has been updated") : "";
        unset($_SESSION['status']);
         
      }

      $this->setView('reading-setting');
      $this->setPageTitle('Reading Settings');
      $this->setFormAction(ActionConst::READING_CONFIG);
      
      if (!$checkError) {
        
        $this->view->set('errors', $errors);
       
      }
  
      if ($checkStatus) {
        
        $this->view->set('status', $status);
       
      }

      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('settings', $this->configEvent->grabReadingSettings());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
      
    }

    return $this->view->render();

  }

  /**
   * updatePermalinkConfig
   *
   * @return mixed
   * 
   */
  public function updatePermalinkConfig()
  {

    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (!$getPermalinkValue = $this->configEvent->grabSettingByName('permalink_setting')) {

      $_SESSION['error'] = "permalinkValueNotFound";
      direct_page('index.php?load=option-permalink&error=permalinkValueNotFound', 404);
      
    }

    $data_permalink = array(
      'ID' => $getPermalinkValue['ID'],
      'setting_name' => $getPermalinkValue['setting_name'],
      'setting_value' => $getPermalinkValue['setting_value']
    );

    $server_software = json_decode($data_permalink['setting_value'], true);

    $filters = ['permalinks' => isset($_POST['permalinks']) ? Sanitize::severeSanitizer($_POST['permalinks']) : "", 
                'setting_id' => FILTER_SANITIZE_NUMBER_INT, 
                'setting_name' => isset($_POST['setting_name']) ? Sanitize::severeSanitizer($_POST['setting_name']) : ""
              ];
    
    if (isset($_POST['configFormSubmit'])) {

      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");
        
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['permalinks'], ['yes', 'no'])) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");

        }

        if (!$checkError) {

          $this->setView('permalink-setting');
          $this->setPageTitle('Permalink Setting');
          $this->setFormAction(ActionConst::PERMALINK_CONFIG);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('permalinkData', $data_permalink);
          $this->view->set('errors', $errors);
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {
        
          $this->configEvent->setConfigId((int)distill_post_request($filters)['setting_id']);

          $this->configEvent->setConfigName(prevent_injection(distill_post_request($filters)['setting_name']));

          $permalink_value = array(
            'rewrite' => distill_post_request($filters)['permalinks'],
            'server_software' => find_webserver_name()
          );

          $updated_permalink_value = json_encode($permalink_value);
          
          $this->configEvent->setConfigValue($updated_permalink_value);

          if (($server_software['server_software'] == 'Apache') || ($server_software['server_software'] == 'LiteSpeed')) {

            write_htaccess(distill_post_request($filters)['permalinks'], Session::getInstance()->scriptlog_session_level, read_htaccess_config(distill_post_request($filters)['permalinks']));

          }
          
          $this->configEvent->modifySetting();
          $_SESSION['status'] = "permalinkConfigUpdated";
          direct_page('index.php?load=option-permalink&status=permalinkConfigUpdated', 200);
           
        }

      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
        
      }
      
    } else {

      if (isset($_SESSION['error'])) {
        $checkError = false;
        ($_SESSION['error'] == 'permalinkValueNotFound') ? array_push($errors, "Error: Permalink value not found!") : "";
        unset($_SESSION['error']);
      }
 
      if (isset($_SESSION['status'])) {
        $checkStatus = true;
        ($_SESSION['status'] == 'permalinkConfigUpdated') ? array_push($status, "Permalink setting has been updated") : "";
        unset($_SESSION['status']);
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
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('permalinkData', $data_permalink);
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }
  
  /**
   * updateTimezoneConfig
   *
   */
  public function updateTimezoneConfig()
  {

    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (!$getTimezoneValue = $this->configEvent->grabSettingByName('timezone_setting')) {

      $_SESSION['error'] = "timezoneValueNotFound";
      direct_page('index.php?load=option-timezone&error=timezoneValueNotFound', 404);
      
    }

    $data_timezone = array(
      'ID' => $getTimezoneValue['ID'],
      'setting_name' => $getTimezoneValue['setting_name'],
      'setting_value' => $getTimezoneValue['setting_value']
    );

    $timezone_identifier = json_decode($data_timezone['setting_value'], true);

    $filters = [
      'timezone' => isset($_POST['timezone']) ? Sanitize::severeSanitizer($_POST['timezone']) : "",
      'setting_id' => FILTER_SANITIZE_NUMBER_INT,
      'setting_name' => isset($_POST['setting_name']) ? Sanitize::severeSanitizer($_POST['setting_name']) : ""
    ];

    if (isset($_POST['configFormSubmit'])) {

      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");
        
        }

        if (false === sanitize_selection_box(distill_post_request($filters)['timezone'], print_timezone_list())) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");

        }

        if (!$checkError) {

          $this->setView('timezone-setting');
          $this->setPageTitle('Timezone Setting');
          $this->setFormAction(ActionConst::TIMEZONE_CONFIG);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('timezoneData', $data_timezone);
          $this->view->set('errors', $errors);
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('timezoneIdentifier', $this->configEvent->timezoneIdentifierDropDown($timezone_identifier['timezone_identifier']));
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->configEvent->setConfigId((int)distill_post_request($filters)['setting_id']);

          $this->configEvent->setConfigName(prevent_injection(distill_post_request($filters)['setting_name']));

          $timezone_value = array(
            'timezone_identifier' => distill_post_request($filters)['timezone']
          );

          $updated_timezone_value = json_encode($timezone_value);

          $this->configEvent->setConfigValue($updated_timezone_value);

          $this->configEvent->modifySetting();

          $_SESSION['status'] = "timezoneConfigUpdated";
          direct_page('index.php?load=option-timezone&status=timezoneConfigUpdated', 200);

        }
        
      } catch (\Throwable $th) {
        
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
      }

    } else {

      if (isset($_SESSION['error'])) {
        $checkError = false;
        ($_SESSION['error'] == 'timezoneValueNotFound') ? array_push($errors, "Error: Timezone value not found!") : "";
        unset($_SESSION['error']);
      }
 
      if (isset($_SESSION['status'])) {
        $checkStatus = true;
        ($_SESSION['status'] == 'timezoneConfigUpdated') ? array_push($status, "Timezone setting has been updated") : "";
        unset($_SESSION['status']);
      }

      $this->setView('timezone-setting');
      $this->setPageTitle('Timezone Setting');
      $this->setFormAction(ActionConst::TIMEZONE_CONFIG);

      if (!$checkError) {
       $this->view->set('errors', $errors);
      }
 
      if ($checkStatus) {
       $this->view->set('status', $status);
      }

      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('timezoneData', $data_timezone);
      $this->view->set('timezoneIdentifier', $this->configEvent->timezoneIdentifierDropDown($timezone_identifier['timezone_identifier']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  /**
   * setView
   *
   * @param string $viewName
   * @return object
   * 
   */
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'setting', $viewName);
  }

}