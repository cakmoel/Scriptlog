<?php
/**
 * Class MenuApp extends BaseApp
 * 
 * @package  SCRIPTLOG/LIB/APP/MenuApp
 * @category App Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0.0
 * 
 */
class MenuApp extends BaseApp
{
  private $view;

  private $menuEvent;

  public function __construct(MenuEvent $menuEvent)
  {
    $this->menuEvent = $menuEvent;
  }
  
  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
    
    if (isset($_GET['error'])) {
      
       $checkError = false;
       if ($_GET['error'] == 'menuNotFound') array_push($errors, "Error: Menu not found");

    }

    if (isset($_GET['status'])) {
      
       $checkStatus = true;
       if ($_GET['status'] == 'menuAdded') array_push($status, "New menu added");
       if ($_GET['status'] == 'menuUpdated') array_push($status, "Menu updated");
       if ($_GET['status'] == 'menuDeleted') array_push($status, "Menu deleted");

    }

    $this->setView('all-menus');
    $this->setPageTitle('Menu');
    $this->view->set('pageTitle', $this->getPageTitle());

    if (!$checkError) {
       $this->view->set('errors', $errors);
    }

    if ($checkStatus) {
      $this->view->set('status', $status);
    }

    $this->view->set('menusTotal', $this->menuEvent->totalMenus());
    $this->view->set('menus', $this->menuEvent->grabMenus());
    return $this->view->render();

  }
  
  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['menuFormSubmit'])) {
      
      $menu_label = isset($_POST['menu_label']) ? prevent_injection($_POST['menu_label']) : "";
      $menu_link = isset($_POST['menu_link']) ? trim($_POST['menu_link']) : "";

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($menu_label)) {
          
          $checkError = false;
          array_push($errors, "All columns required must be filled");

        }

        if ($this->menuEvent->isMenuExists($menu_label) === true) {
          
          $checkError = false;
          array_push($errors, "Menu has been used");
          
        }
      
        if (!$checkError) {

          $this->setView('edit-menu');
          $this->setPageTitle('Add New Menu');
          $this->setFormAction('newMenu');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

           $this->menuEvent->setMenuLabel($menu_label);
           $this->menuEvent->setMenuLink($menu_link);
           $this->menuEvent->addMenu();
           direct_page('index.php?load=menu&status=menuAdded', 200);

        }

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');
        
      }

    } else {

      $this->setView('edit-menu');
      $this->setPageTitle('Add New Menu');
      $this->setFormAction('newMenu');
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

    if (!$getMenu = $this->menuEvent->grabMenu($id)) {
       direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    $data_menu = array(
      'ID' => $getMenu['ID'],
      'menu_label' => $getMenu['menu_label'],
      'menu_link' => $getMenu['menu_link'],
      'menu_sort' => $getMenu['menu_sort'],
      'menu_status' => $getMenu['menu_status']
    );

    if (isset($_POST['menuFormSubmit'])) {

      $menu_label = prevent_injection($_POST['menu_label']);
      $menu_link = trim($_POST['menu_link']);
      $menu_sort = (is_int($_POST['menu_sort']) ? abs((int)$_POST['menu_sort']) : 0);
      $menu_status = $_POST['menu_status'];
      $menu_id = abs((int)$_POST['menu_id']);

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($menu_label)) {
          $checkError = false;
          array_push($errors, "All columns required must be filled");
        }

        if (!$checkError) {

          $this->setView('edit-menu');
          $this->setPageTitle('Edit Menu');
          $this->setFormAction('editMenu');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('menuData', $data_menu);
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->menuEvent->setMenuId($menu_id);
          $this->menuEvent->setMenuLabel($menu_label);
          $this->menuEvent->setMenuLink($menu_link);
          $this->menuEvent->setMenuOrder($menu_sort);
          $this->menuEvent->setMenuStatus($menu_status);
          $this->menuEvent->modifyMenu();
          direct_page('index.php?load=menu&status=menuUpdated', 200);

        }

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');
        
      }

    } else {

      $this->setView('edit-menu');
      $this->setPageTitle('Edit Menu');
      $this->setFormAction('editMenu');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('menuData', $data_menu);
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }
  
  public function remove($id)
  {
    $this->menuEvent->setMenuId($id);
    $this->menuEvent->removeMenu();
    direct_page('index.php?load=menu&status=menuDeleted', 200);
  }
  
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'appearance', $viewName);
  }
  
}