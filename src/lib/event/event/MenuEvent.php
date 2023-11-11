<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Menu Event Class
 * 
 * @category Event Class
 * @author  M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since   Since Release 1.0
 * 
 */
class MenuEvent
{
  /**
   * menu_id
   *
   * @var int
   * 
   */
  private $menu_id;
  
  /**
   * parent_id
   *
   * @var int
   * 
   */
  private $parent_id;
  
  /**
   * label
   *
   * @var string
   * 
   */
  private $label;
  
  /**
   * link
   *
   * @var string
   * 
   */
  private $link;
  
  /**
   * status
   *
   * @var string
   * 
   */
  private $status;

  /**
   * visibility
   *
   * @var string
   * 
   */
  private $visibility;

  /**
   * Sort or order of menu navigation
   *
   * @var string
   * 
   */
  private $sort;

  /**
   * menuDao
   *
   * @var object
   * 
   */
  private $menuDao;

  /**
   * validator
   *
   * @var object
   * 
   */
  private $validator;

  /**
   * sanitize
   *
   * @var object
   * 
   */
  private $sanitize;
 
  public function __construct(MenuDao $menuDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->menuDao = $menuDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  /**
   * setMenuId
   *
   * @param int $menu_id
   * @return int
   * 
   */
  public function setMenuId($menu_id)
  {
    $this->menu_id = $menu_id;
  }

  /**
   * setParentId
   *
   * @param int $parent_id
   * @return int
   * 
   */
  public function setParentId($parent_id)
  {
    $this->parent_id = $parent_id;
  }

  /**
   * setMenuLabel
   *
   * @param string $menu_label
   * @return int
   * 
   */
  public function setMenuLabel($menu_label)
  {
    $this->label = $menu_label;
  }

  /**
   * setMenuLink
   *
   * @param string $menu_link
   * @return string
   * 
   */
  public function setMenuLink($menu_link)
  {
    $this->link = $menu_link;
  }

  /**
   * setMenuVisibility
   *
   * @param string $menu_visibility
   * @return string
   * 
   */
  public function setMenuVisibility($menu_visibility)
  {
    $this->visibility = $menu_visibility;
  }

  /**
   * setMenuOrder
   *
   * @param int|num $sort
   * 
   */
  public function setMenuOrder($sort)
  {
    $this->sort = $sort;
  }

  /**
   * setMenuStatus
   * 
   * @param string $menu_status
   * @return string
   * 
   */
  public function setMenuStatus($menu_status)
  {
    $this->status = $menu_status;
  }

  /**
   * grabMenus
   *
   * @param string $orderBy
   * @return mixed
   * 
   */
  public function grabMenus($orderBy = "ID")
  {
    return $this->menuDao->findMenus($orderBy);
  }

  /**
   * grabMenu
   *
   * @param int|string $id
   * @return mixed
   * 
   */
  public function grabMenu($id)
  {
    return $this->menuDao->findMenu($id, $this->sanitize);
  }

  /**
   * grabMenuParent
   *
   * @param int|string $parent_id
   * @return mixed
   * 
   */
  public function grabMenuParent($parent_id)
  {
    return $this->menuDao->findMenuParent($parent_id, $this->sanitize);
  }

  /**
   * addMenu
   *
   * @return mixed
   * 
   */
  public function addMenu()
  {
    
    $this->validator->sanitize($this->parent_id, 'int');
    $this->validator->sanitize($this->sort, 'int');
    $this->validator->sanitize($this->label, 'string');
    
    if (!empty($this->link)) {

      $this->validator->sanitize($this->link, 'url');

    }
    
    return $this->menuDao->insertMenu([
      'menu_label' => $this->label,
      'menu_link' => $this->link,
      'menu_visibility' => $this->visibility,
      'parent_id' => $this->parent_id,
      'menu_sort' => $this->sort
    ]);

  }

  /**
   * modifyMenu
   *
   */
  public function modifyMenu()
  {
    $this->validator->sanitize($this->parent_id, 'int');
    $this->validator->sanitize($this->menu_id, 'int');
    $this->validator->sanitize($this->link, 'url');
    $this->validator->sanitize($this->label, 'string');

    return $this->menuDao->updateMenu($this->sanitize, [

          'menu_label' => $this->label,
          'menu_link' => $this->link,
          'menu_status' => $this->status,
          'menu_visibility' => $this->visibility,
          'parent_id' => $this->parent_id,
          'menu_sort' => $this->sort
    
        ], $this->menu_id);

  }

  /**
   * enableMenu()
   * 
   */
  public function enableMenu()
  {
    $this->validator->sanitize($this->menu_id, 'int');

    $menu_enabled = $this->menuDao->findMenu($this->menu_id, $this->sanitize);
    
    if (! $menu_enabled ) {
       direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->activateMenu($this->menu_id, $this->sanitize);

  }

  /**
   * disableMenu()
   *
   */
  public function disableMenu()
  {
    $this->validator->sanitize($this->menu_id, $this->sanitize);

    $menu_disabled = $this->menuDao->findMenu($this->menu_id, $this->sanitize);
    if (! $menu_disabled) {
      direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->deactivateMenu($this->menu_id, $this->sanitize);
    
  }

  /**
   * removeMenu()
   * 
   */
  public function removeMenu()
  {
    $this->validator->sanitize($this->menu_id, 'int');

    if (!$this->menuDao->findMenu($this->menu_id, $this->sanitize)) {
       direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->deleteMenu($this->menu_id, $this->sanitize);

  }

  /**
   * parentDropDown
   *
   * @param string $selected
   * 
   */
  public function parentDropDown($selected = "")
  {
    return $this->menuDao->dropDownMenuParent($selected);
  }

  /**
   * visibilityDropDown()
   * 
   * @param string $selected
   * 
   */
  public function visibilityDropDown($selected = "")
  {
    return $this->menuDao->dropDownMenuVisibility($selected);
  }

  /**
   * isMenuExists
   *
   * @param string $menu_label
   * 
   */
  public function isMenuExists($menu_label)
  {
    return $this->menuDao->menuExists($menu_label);
  }

  /**
   * totalMenus
   *
   * @param mixed|array $data
   *  
   */
  public function totalMenus($data = null)
  {
    return $this->menuDao->totalMenuRecords($data);
  }

}