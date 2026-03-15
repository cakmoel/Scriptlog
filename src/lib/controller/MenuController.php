<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class MenuController
 * 
 * @category Class MenuController extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0.0
 * 
 */
class MenuController extends BaseApp
{

  /**
   * view
   *
   * @var object
   * 
   */
  private $view;

  /**
   * menuService
   *
   * @var object
   * 
   */
  private $menuService;

  public function __construct(MenuService $menuService)
  {
    $this->menuService = $menuService;
  }

  /**
   * listItems()
   *
   * retrieving items from table menu
   * 
   */
  public function listItems()
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;

    if (isset($_SESSION['error'])) {

      $checkError = false;
      ($_SESSION['error'] == 'menuNotFound') ? array_push($errors, "Error: Menu not found") : "";
      unset($_SESSION['error']);
    }

    if (isset($_SESSION['status'])) {

      $checkStatus = true;
      ($_SESSION['status'] == 'menuAdded') ? array_push($status, "New menu added") : "";
      ($_SESSION['status'] == 'menuUpdated') ? array_push($status, "Menu updated") : "";
      ($_SESSION['status'] == 'menuDeleted') ? array_push($status, "Menu deleted") : "";
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

    $this->view->set('menusTotal', $this->menuService->totalMenus());
    $this->view->set('menus', $this->menuService->grabMenus());
    return $this->view->render();
  }

  /**
   * insert()
   *
   * inserting new record into table
   * 
   */
  public function insert()
  {
    $errors = array();
    $checkError = true;

    if (isset($_POST['menuFormSubmit'])) {

      $filters = [
        'menu_label' => isset($_POST['menu_label']) ? Sanitize::severeSanitizer($_POST['menu_label']) : "",
        'menu_link' => FILTER_SANITIZE_URL,
        'menu_visibility' => isset($_POST['menu_visibility']) ? Sanitize::mildSanitizer($_POST['menu_visibility']) : "",
        'parent_id' => isset($_POST['parent_id']) ? FILTER_SANITIZE_NUMBER_INT : '0',
        'menu_sort' => isset($_POST['menu_sort']) ? FILTER_SANITIZE_NUMBER_INT : '0'
      ];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

          header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!.");
        }

        if (empty($_POST['menu_label'])) {

          $checkError = false;
          array_push($errors, "Menu name must be filled");
        }

        if ($this->menuService->isMenuExists(distill_post_request($filters)['menu_label']) === true) {

          $checkError = false;
          array_push($errors, "Menu has been used");
        }

        if (sanitize_selection_box(distill_post_request($filters)['menu_visibility'], ['public' => 'Public', 'private' => 'Private']) === false) {

          $checkError = false;
          array_push($errors, MESSAGE_INVALID_SELECTBOX);
        }

        if ((!empty($_POST['menu_sort'])) || ($_POST['menu_sort'] !== '')) {

          if (is_numeric($_POST['menu_sort']) === false) {

            $checkError = false;
            array_push($errors, "Order must be a number");
          }
          
        }

        if (!$checkError) {

          $this->setView('edit-menu');
          $this->setPageTitle('Add New Menu');
          $this->setFormAction(ActionConst::NEWMENU);
          $this->view->set('pageTitle', $this->getPageTitle());
          $this->view->set('formAction', $this->getFormAction());
          $this->view->set('errors', $errors);
          $this->view->set('formData', $_POST);
          $this->view->set('parent', $this->menuService->parentDropDown());
          $this->view->set('visibility', $this->menuService->visibilityDropDown());
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

          $this->menuService->setParentId((int)distill_post_request($filters)['parent_id']);
          $this->menuService->setMenuLabel(prevent_injection(distill_post_request($filters)['menu_label']));
          $this->menuService->setMenuLink(escape_html(trim(distill_post_request($filters)['menu_link'])));
          $this->menuService->setMenuVisibility(prevent_injection(distill_post_request($filters)['menu_visibility']));
          
          if (empty($_POST['menu_sort'])) {
            $this->menuService->setMenuOrder('0');
          } else {
            $this->menuService->setMenuOrder(abs((int)distill_post_request($filters)['menu_sort']));
          }
          
          $this->menuService->addMenu();
          $_SESSION['status'] = "menuAdded";
          direct_page('index.php?load=menu&status=menuAdded', 302);
        }
      } catch (\Throwable $th) {

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
      $this->view->set('parent', $this->menuService->parentDropDown());
      $this->view->set('visibility', $this->menuService->visibilityDropDown());
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
    }

    return $this->view->render();
  }

  /**
   * update()
   *
   * @param int|num $id
   * 
   */
  public function update($id)
  {
    $errors = array();
    $checkError = true;

    if (!$getMenu = $this->menuService->grabMenu($id)) {

      $_SESSION['error'] = "menuNotFound";
      direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    $data_menu = array(
      'ID' => $getMenu['ID'],
      'menu_label' => $getMenu['menu_label'],
      'menu_link' => $getMenu['menu_link'],
      'menu_status' => $getMenu['menu_status'],
      'menu_visibility' => $getMenu['menu_visibility'],
      'paret_id' => $getMenu['parent_id'],
      'menu_sort' => $getMenu['menu_sort']
    );

    if (isset($_POST['menuFormSubmit'])) {

      $filters = [
        'menu_label' => isset($_POST['menu_label']) ? Sanitize::severeSanitizer($_POST['menu_label']) : "",
        'menu_link' => FILTER_SANITIZE_URL,
        'menu_status' => isset($_POST['menu_status']) ? Sanitize::mildSanitizer($_POST['menu_status']) : "",
        'menu_id' => FILTER_SANITIZE_NUMBER_INT,
        'menu_visibility' => isset($_POST['menu_visibility']) ? Sanitize::mildSanitizer($_POST['menu_visibility']) : "",
        'parent_id' => FILTER_SANITIZE_NUMBER_INT,
        'menu_sort' => FILTER_SANITIZE_NUMBER_INT
      ];

      try {

        if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

          header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
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

        if (sanitize_selection_box(distill_post_request($filters)['menu_visibility'], ['public' => 'Public', 'private' => 'Private']) === false) {

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
          $this->view->set('parent', $this->menuService->parentDropDown($getMenu['parent_id']));
          $this->view->set('visibility', $this->menuService->visibilityDropDown($getMenu['menu_visibility']));
          $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        } else {

          $this->menuService->setParentId((int)distill_post_request($filters)['parent_id']);
          $this->menuService->setMenuId((int)distill_post_request($filters)['menu_id']);
          $this->menuService->setMenuLabel(prevent_injection(distill_post_request($filters)['menu_label']));
          $this->menuService->setMenuLink(escape_html(distill_post_request($filters)['menu_link']));
          $this->menuService->setMenuStatus(distill_post_request($filters)['menu_status']);
          $this->menuService->setMenuVisibility(distill_post_request($filters)['menu_visibility']);
          $this->menuService->setMenuOrder(abs((int)distill_post_request($filters)['menu_sort']));
          $this->menuService->modifyMenu();
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
      $this->view->set('parent', $this->menuService->parentDropDown($getMenu['parent_id']));
      $this->view->set('visibility', $this->menuService->visibilityDropDown($getMenu['menu_visibility']));
      $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
    }

    return $this->view->render();
  }

  /**
   * remove()
   *
   * @param int|num $id
   * @return void
   */
  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

      $getMenu = $this->menuService->grabMenu($id);

      try {

        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

          header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
          throw new AppException("Sorry, unpleasant attempt detected!");
        }

        if (!filter_var($id, FILTER_VALIDATE_INT)) {

          header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
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
          $this->view->set('menusTotal', $this->menuService->totalMenus());
          $this->view->set('menus', $this->menuService->grabMenus());
          return $this->view->render();
        } else {

          $this->menuService->setMenuId($id);
          $this->menuService->removeMenu();
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

  /**
   * setView
   *
   * @param string $viewName
   * 
   */
  protected function setView($viewName)
  {
    $this->view = new View('admin', 'ui', 'appearance', $viewName);
  }
}
