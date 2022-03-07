<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class MenuApp 
 * 
 * @category Class MenuApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0.0
 * 
 */
class MenuApp extends BaseApp
{
  
/**
 * view
 *
 * @var object
 * 
 */
  private $view;

/**
 * menuEvent
 *
 * @var object
 * 
 */
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
    
    if (isset($_SESSION['error'])) {
      
      $checkError = false;
      if ($_SESSION['error'] == 'menuNotFound') array_push($errors, "Error: Menu not found");
      unset($_SESSION['error']);

    }

    if (isset($_SESSION['status'])) {
      
      $checkStatus = true;
      if ($_SESSION['status'] == 'menuAdded') array_push($status, "New menu added");
      if ($_SESSION['status'] == 'menuUpdated') array_push($status, "Menu updated");
      if ($_SESSION['status'] == 'menuDeleted') array_push($status, "Menu deleted");
      unset($_SESSION['status']);

    }

    $this->setView('all-menus');
    $this->setPageTitle('Menus');
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
      
      $filters = ['parent_id' => FILTER_SANITIZE_NUMBER_INT, 
                  'menu_label' => isset($_POST['menu_label']) ? Sanitize::severeSanitizer($_POST['menu_label']) : "", 
                  'menu_link' => FILTER_SANITIZE_URL, 
                  'menu_position'=> isset($_POST['menu_position']) ? Sanitize::mildSanitizer($_POST['menu_position']) : ""
                ];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($_POST['menu_label'])) {
          
          $checkError = false;
          array_push($errors, "Menu name must be filled");

        }

        if ($this->menuEvent->isMenuExists(distill_post_request($filters)['menu_label']) === true) {
          
          $checkError = false;
          array_push($errors, "Menu has been used");
          
        }

        if ( false === sanitize_selection_box(distill_post_request($filters)['menu_position'], ['header'=>'Header', 'footer'=>'Footer'] ) ) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");

        }

        if (!$checkError) {

          $this->setView('edit-menu');
          $this->setPageTitle('Add New Menu');
          $this->setFormAction(ActionConst::NEWMENU);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('parent', $this->menuEvent->parentDropDown());
          $this->view->set('position', $this->menuEvent->positionDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

           $this->menuEvent->setParentId((int)distill_post_request($filters)['parent_id']);
           $this->menuEvent->setMenuLabel(prevent_injection(distill_post_request($filters)['menu_label']));
           $this->menuEvent->setMenuLink(escape_html(trim(distill_post_request($filters)['menu_link'])));
           $this->menuEvent->setMenuPosition(prevent_injection(distill_post_request($filters)['menu_position']));
           $this->menuEvent->addMenu();
           $_SESSION['status'] = "menuAdded";
           direct_page('index.php?load=menu&status=menuAdded', 302);

        }

      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
        
      }

    } else {

      $this->setView('edit-menu');
      $this->setPageTitle('Add New Menu');
      $this->setFormAction(ActionConst::NEWMENU);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('parent', $this->menuEvent->parentDropDown());
      $this->view->set('position', $this->menuEvent->positionDropDown());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }
  
  public function update($id)
  {
    $errors = array();
    $checkError = true;

    if (!$getMenu = $this->menuEvent->grabMenu($id)) {

      $_SESSION['error'] = "menuNotFound";
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

      $filters = ['parent_id' => FILTER_SANITIZE_NUMBER_INT, 
                  'menu_label' => isset($_POST['menu_label']) ? Sanitize::severeSanitizer($_POST['menu_label']) : "", 
                  'menu_link' => FILTER_SANITIZE_URL, 
                  'menu_sort' => FILTER_SANITIZE_NUMBER_INT, 
                  'menu_status' => isset($_POST['menu_status']) ? Sanitize::mildSanitizer($_POST['menu_status']) : "", 
                  'menu_id' => FILTER_SANITIZE_NUMBER_INT, 
                  'menu_position' => isset($_POST['menu_position']) ? Sanitize::mildSanitizer($_POST['menu_position']) : ""
                ];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
         
          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
          throw new AppException("Sorry, unpleasant attempt detected!.");
          
        }

        if (empty($_POST['menu_label'])) {

           $checkError = false;
           array_push($errors, "Menu name must be filled");

        }

        if (false === sanitize_selection_box(distill_post_request($filters)['menu_status'], ['Y', 'N'])) {

           $checkError = false;
           array_push($errors, "Please choose the available value provided!");

        }

        if (false === sanitize_selection_box(distill_post_request($filters)['menu_position'], ['header'=>'Header', 'footer'=>'Footer'] ) ) {

          $checkError = false;
          array_push($errors, "Please choose the available value provided!");
          
        }

        if (!$checkError) {

          $this->setView('edit-menu');
          $this->setPageTitle('Edit Menu');
          $this->setFormAction(ActionConst::EDITMENU);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('menuData', $data_menu);
          $this->view->set('parent', $this->menuEvent->parentDropDown($getMenu['parent_id']));
          $this->view->set('position', $this->menuEvent->positionDropDown($getMenu['menu_position']));
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->menuEvent->setParentId((int)distill_post_request($filters)['parent_id']);
          $this->menuEvent->setMenuId((int)distill_post_request($filters)['menu_id']);
          $this->menuEvent->setMenuLabel(prevent_injection(distill_post_request($filters)['menu_label']));
          $this->menuEvent->setMenuLink(escape_html(distill_post_request($filters)['menu_link']));
          $this->menuEvent->setMenuOrder((is_int($_POST['menu_sort']) ? distill_post_request($filters)['menu_sort'] : 0 ));
          $this->menuEvent->setMenuStatus(distill_post_request($filters)['menu_status']);
          $this->menuEvent->modifyMenu();
          $_SESSION['status'] = "menuUpdated";
          direct_page('index.php?load=menu&status=menuUpdated', 200);

        }

      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

      } catch (AppException $e) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
        
      }

    } else {

      $this->setView('edit-menu');
      $this->setPageTitle('Edit Menu');
      $this->setFormAction(ActionConst::EDITMENU);
      $this->view->set('pageTitle', $this->getPageTitle());
      $this->view->set('formAction', $this->getFormAction());
      $this->view->set('menuData', $data_menu);
      $this->view->set('parent', $this->menuEvent->parentDropDown($getMenu['parent_id']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();

  }
  
  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

       $getMenu = $this->menuEvent->grabMenu($id);

      try {
        
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }
      
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (!$getMenu) {

           $checkError = false;
           array_push($errors, 'Error: Menu not found');

        }

        if (!$checkError) {

          $this->setView('all-menus');
          $this->setPageTitle('Menu not found');
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('errors', $errors);
          $this->view->set('menusTotal', $this->menuEvent->totalMenus());
          $this->view->set('menus', $this->menuEvent->grabMenus());
          return $this->view->render();

        } else {

          $this->menuEvent->setMenuId($id);
          $this->menuEvent->removeMenu();
          $_SESSION['status'] = "menuDeleted";
          direct_page('index.php?load=menu&status=menuDeleted', 200);

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
    $this->view = new View('admin', 'ui', 'appearance', $viewName);
  }
  
}