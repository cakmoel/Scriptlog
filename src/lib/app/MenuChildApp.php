<?php
/**
 * Class MenuChildApp
 * 
 * @category Class MenuChildApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0.0
 * 
 */
class MenuChildApp extends BaseApp
{
  private $view;

  private $menuChildEvent;

  public function __construct(MenuChildEvent $menuChildEvent)
  {
    $this->menuChildEvent = $menuChildEvent;
  }

  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_GET['error'])) {
       $checkError = false;
       if ($_GET['error'] == 'submenuNotFound') array_push($errors, "Errors: Sub menu not found");
    }

    if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'submenuAdded') array_push($status, "New Sub Menu Added");
        if ($_GET['status'] == 'submenuUpdated') array_push($status, "Sub Menu Updated");
        if ($_GET['status'] == 'submenuDeleted') array_push($status, "Sub Menu Deleted");
    }

    $this->setView('all-submenus');
    $this->setPageTitle('Sub Menu');
    $this->view->set('pageTitle', $this->getPageTitle());

    if (!$checkError) {
      $this->view->set('errors', $errors);
    }

    if ($checkStatus) {
      $this->view->set('status', $status);
    }

    $this->view->set('subMenusTotal', $this->menuChildEvent->totalMenuChilds());
    $this->view->set('subMenus', $this->menuChildEvent->grabMenuChilds());

    return $this->view->render();

  }

  public function insert()
  {
    $menus = new Menu(); 
    $errors = [];
    $checkError = true;
    
    if (isset($_POST['childFormSubmit'])) {

      $child_label = (isset($_POST['child_label']) ? prevent_injection($_POST['child_label']) : "");
      $child_link = prevent_injection($_POST['child_link']);
      $ascendent_id = (isset($_POST['parent']) ? (int)$_POST['parent'] : 0);
      $descendent_id = (isset($_POST['child']) ? (int)$_POST['child'] : 0);

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($child_label)) {

          $checkError = false;
          array_push($errors, "All columns required must be filled");

        }

        if ($this->menuChildEvent->isMenuChildExists($child_label) === true) {

          $checkError = false;
          array_push($errors, "Submenu has been used");

        }

        if (!$checkError) {

          $this->setView('edit-submenu');
          $this->setPageTitle('Add New Submenu');
          $this->setFormAction('newSubmenu');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('menus', $menus->dropDownMenu());
          $this->view->set('submenus', $this->menuChildEvent->descMenuDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          # insert new submenu
          $this->menuChildEvent->setMenuChildLabel($child_label);
          $this->menuChildEvent->setMenuChildLink($child_link);
          $this->menuChildEvent->setAscendent($ascendent_id);
          $this->menuChildEvent->setDescendent($descendent_id);
          $this->menuChildEvent->addMenuChild();
          direct_page('index.php?load=menu-child&status=submenuAdded', 200);

        }

      } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {

      $this->setView('edit-submenu');
      $this->setPageTitle('Add New Submenu');
      $this->setFormAction('newSubmenu');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('menus', $menus->dropDownMenu());
      $this->view->set('submenus', $this->menuChildEvent->descMenuDropDown());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  public function update($id)
  {
    $menus = new Menu();
    $errors = [];
    $checkError = true;

    if (!$getMenuChild = $this->menuChildEvent->grabMenuChild($id)) {
       direct_page('index.php?load=menu-child&error=submenuNotFound', 404);
    }

    $data_submenu = array(
      'ID' => $getMenuChild['ID'],
      'menu_child_label' => $getMenuChild['menu_child_label'],
      'menu_child_link' => $getMenuChild['menu_child_link'],
      'menu_id' => $getMenuChild['menu_id'],
      'menu_sub_child' => $getMenuChild['menu_sub_child'],
      'menu_child_sort' => $getMenuChild['menu_child_sort'],
      'menu_child_status' => $getMenuChild['menu_child_status']
    );

    if (isset($_POST['childFormSubmit'])) {

      $child_label = (isset($_POST['child_label']) ? prevent_injection($_POST['child_label']) : "");
      $child_link = prevent_injection($_POST['child_link']);
      $ascendent_id = (isset($_POST['parent']) ? (int)$_POST['parent'] : 0);
      $descendent_id = (isset($_POST['child']) ? (int)$_POST['child'] : 0);
      $child_id = abs((int)$_POST['child_id']);
      $child_sort = abs($_POST['child_sort']);
      $child_status = $_POST['child_status'];
      
      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($child_label)) {

          $checkError = false;
          array_push($errors, "All columns required must be filled");

        }

        if (!$checkError) {

          $this->setView('edit-submenu');
          $this->setPageTitle('Edit Submenu');
          $this->setFormAction('editSubmenu');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('subMenuData', $data_submenu);
          $this->view->set('menus', $menus->dropDownMenu($getMenuChild['menu_id']));
          $this->view->set('submenus', $this->menuChildEvent->descMenuDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->menuChildEvent->setMenuChildId($child_id);
          $this->menuChildEvent->setMenuChildLabel($child_label);
          $this->menuChildEvent->setMenuChildLink($child_link);
          $this->menuChildEvent->setAscendent($ascendent_id);
          $this->menuChildEvent->setDescendent($descendent_id);
          $this->menuChildEvent->setMenuChildSort($child_sort);
          $this->menuChildEvent->setMenuChildStatus($child_status);
          $this->menuChildEvent->modifyMenuChild();
          direct_page('index.php?load=menu-child&status=submenuUpdated', 200);

        }
        
      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::newMessage($e);
        LogError::customErrorMessage('admin');

      }

    } else {

      $this->setView('edit-submenu');
      $this->setPageTitle('edit Submenu');
      $this->setFormAction('editSubmenu');
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('subMenuData', $data_submenu);
      $this->view->set('menus', $menus->dropDownMenu($getMenuChild['menu_id']));
      $this->view->set('submenus', $this->menuChildEvent->descMenuDropDown($getMenuChild['menu_child_id']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }

  public function remove($id)
  {
    $this->menuChildEvent->setMenuChildId($id);
    $this->menuChildEvent->removeMenuChild();
    direct_page('index.php?load=menu-child&status=submenuDeleted', 200);
  }

  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'appearance', $viewName);
  }
  
}