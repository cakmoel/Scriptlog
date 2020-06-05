<?php
/**
 * Class ThemeApp
 * 
 * @category Class ThemeApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0
 * 
 */
class ThemeApp extends BaseApp
{
  private $view;

  private $themeEvent;

  public function __construct(ThemeEvent $themeEvent)
  {
    $this->themeEvent = $themeEvent;
  }

  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_GET['error'])) {
       if ($_GET['error'] == "themeNotFound") array_push($errors, "Error: Theme is not found");
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "themeAdded") array_push($errors, "New theme added");
        if ($_GET['status'] == "themeInstalled") array_push($errors, "Theme installation process is successful, please activate it first to see it works!");
        if ($_GET['status'] == "themeUpdated") array_push($errors, "Theme updated");
        if ($_GET['status'] == "themeActivated") array_push($status, "Theme activated");
        if ($_GET['status'] == "themeDeleted") array_push($status, "Theme deleted");
    }

    $this->setView('all-templates');
    $this->setPageTitle('Themes');
    $this->view->set('pageTitle', $this->getPageTitle());

    if (!$checkError) {
      $this->view->set('errors', $errors); 
    }

    if ($checkStatus) {
      $this->view->set('status', $status);
    }

    $this->view->set('themesTotal', $this->themeEvent->totalThemes());
    $this->view->set('themes', $this->themeEvent->grabThemes());
    return $this->view->render();

  }

  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['themeFormSubmit'])) {

      $theme_title = (isset($_POST['theme_title']) ? preg_replace('/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/', '', $_POST['theme_title']) : "");
      $theme_desc = (isset($_POST['theme_description']) ? prevent_injection($_POST['theme_description']) : "");
      $theme_designer = (isset($_POST['theme_designer']) ? preg_replace('/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/', '', $_POST['theme_designer']) : "");
      $theme_dir = (isset($_POST['theme_directory']) ? $_POST['theme_directory'] : "");

      try {
        
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
          
        }

        if (empty($theme_title) || empty($theme_designer) || empty($theme_dir)) {
          $checkError = false;
          array_push($errors, "All columns required must be filled");
        }

        if ($this->themeEvent->isThemeExists($theme_title) === true) {
          $checkError = false;
          array_push($errors, "Sorry, you have installed this theme before.");
        }

        if (!$checkError) {

          $this->setView('edit-template');
          $this->setPageTitle('Add New Theme');
          $this->setFormAction('newTheme');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->themeEvent->setThemeTitle($theme_title);
          $this->themeEvent->setThemeDescription($theme_desc);
          $this->themeEvent->setThemeDesigner($theme_designer);
          $this->themeEvent->setThemeDirectory($theme_dir);
          $this->themeEvent->addTheme();
          direct_page('index.php?load=templates&status=themeAdded', 200);

        }

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {
       
       $this->setView('edit-template');
       $this->setPageTitle('Add New Theme');
       $this->setFormAction('newTheme');
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
       
    }

    return $this->view->render();

  }

  public function setupTheme()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['themeFormSubmit'])) {

      $file_name = (isset($_FILES['zip_file']['name']) ? $_FILES['zip_file']['name']  : "");
      $file_size = (isset($_FILES['zip_file']['size']) ? $_FILES['zip_file']['size'] : null);
      $file_location = (isset($_FILES['zip_file']['tmp_name']) ? $_FILES['zip_file']['tmp_name'] : "");
      $max_filesize = $_POST['MAX_FILE_SIZE'];

      $theme_title = current(explode(".",$file_name));
      $extension = [];
      $split = explode(".",$file_name);
      $extension = (array_key_exists(1, $split) ? $split[1] : null);
      $validate_format = (strtolower($extension) == 'zip' ? true : false);
      $theme_dir = "public/themes/{$theme_title}";

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
          
        }

        if ((empty($file_location))) {
           
          $checkError = false;
          array_push($errors, "Please upload your selected file");

        }

        if (!$validate_format) {

          $checkError = false;
          array_push($errors, "Invalid file format.Make sure you have a .zip format");
          
        }

        if (!(is_writable('../public/themes/'))) {
          
          $checkError = false;
          array_push($errors, "Permission denied.");

        } elseif ((is_dir('../public/themes/'.$theme_title.'/')) || (is_readable('../public/themes/'.$theme_title.'/theme.ini'))) {

          $checkError = false;
          array_push($errors, "Sorry, you have installed this theme before.");

        }

        if (!$checkError) {
           
          $this->setView('install-template');
          $this->setPageTitle('Upload Theme');
          $this->setFormAction('installTheme');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

           # upload theme and populate database
           upload_theme($file_name, $file_location, $max_filesize, ["..", ".git", ".svn", "composer.json", "composer.lock", "framework_config.yaml", ".php", ".html", ".phtml", ".php5", ".php4", ".pl", ".py", ".sh", ".htaccess"]);
           
           if (file_exists(APP_ROOT.'public/themes/'.$theme_title.'/theme.ini'))
              $theme_ini = parse_ini_file(APP_ROOT.'public/themes/'.$theme_title.'/theme.ini');
              
              $this->themeEvent->setThemeTitle($theme_ini['theme_name']);
              $this->themeEvent->setThemeDescription($theme_ini['theme_description']);
              $this->themeEvent->setThemeDesigner($theme_ini['theme_designer']);
              $this->themeEvent->setThemeDirectory($theme_dir);
              $this->themeEvent->addTheme();
              direct_page('index.php?load=templates&status=themeAdded', 200);
              
        }

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {

      $this->setView('install-template');
      $this->setPageTitle('Upload Theme');
      $this->setFormAction('installTheme');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    $this->view->render();

  }

  public function update($id)
  {
    $errors = array();
    $checkError = true;
    
    if (!($getTheme = $this->themeEvent->grabTheme($id))) {
      direct_page('index.php?load=templates&error=themeNotFound', 404);
    }

    $data_theme = array(
      'ID' => $getTheme['ID'],
      'theme_title' => $getTheme['theme_title'],
      'theme_desc' => $getTheme['theme_desc'],
      'theme_designer' => $getTheme['theme_designer'],
      'theme_directory' => $getTheme['theme_directory'],
      'theme_status' => $getTheme['theme_status']
    );

    if (isset($_POST['themeFormSubmit'])) {

      $theme_title = (isset($_POST['theme_title']) ? preg_replace('/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/', '', $_POST['theme_title']) : "");
      $theme_desc = (isset($_POST['theme_description']) ? prevent_injection($_POST['theme_description']) : "");
      $theme_designer = (isset($_POST['theme_designer']) ? preg_replace('/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/', '', $_POST['theme_designer']) : "");
      $theme_dir = (isset($_POST['theme_directory']) ? $_POST['theme_directory'] : "");
      $theme_status = $_POST['theme_status'];
      $theme_id = abs((int)$_POST['theme_id']);

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!");
          
        }

        if (empty($theme_title) || empty($theme_designer) || empty($theme_dir)) {
          $checkError = false;
          array_push($errors, "All columns required must be filled");
        }

        if (!$checkError) {

          $this->setView('edit-template');
          $this->setPageTitle('Edit Theme');
          $this->setFormAction('editTheme');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('themeData', $data_theme);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->themeEvent->setThemeId($theme_id);
          $this->themeEvent->setThemeTitle($theme_title);
          $this->themeEvent->setThemeDescription($theme_desc);
          $this->themeEvent->setThemeDesigner($theme_designer);
          $this->themeEvent->setThemeDirectory($theme_dir);
          $this->themeEvent->setThemeStatus($theme_status);
          $this->themeEvent->modifyTheme();
          direct_page('index.php?load=templates&status=themeUpdated', 200);

        }

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');
        
      }

    } else {
       
       $this->setView('edit-template');
       $this->setPageTitle('Edit Theme');
       $this->setFormAction('editTheme');
       $this->view->set('pageTitle', $this->getPageTitle());
       $this->view->set('formAction', $this->getFormAction());
       $this->view->set('themeData', $data_theme);
       $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  public function remove($id)
  {
    $this->themeEvent->setThemeId($id);
    $this->themeEvent->removeTheme();
    direct_page('index.php?load=templates&status=themeDeleted', 200);
  }

  public function enableTheme($id)
  {
    $this->themeEvent->setThemeId($id);
    $this->themeEvent->activateInstalledTheme();
    direct_page('index.php?load=templates&status=themeActived', 200);
  }

  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'appearance', $viewName);
  }

}