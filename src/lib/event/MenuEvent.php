<?php
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
  private $menu_id;
  
  private $label;
  
  private $link;
  
  private $order;
  
  private $status;
 
  public function __construct(MenuDao $menuDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->menuDao = $menuDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  public function setMenuId($menu_id)
  {
    $this->menu_id = $menu_id;
  }

  public function setMenuLabel($menu_label)
  {
    $this->label = $menu_label;
  }

  public function setMenuLink($menu_link)
  {
    $this->link = $menu_link;
  }

  public function setMenuOrder($menu_order)
  {
    $this->order = $menu_order;
  }

  public function setMenuStatus($menu_status)
  {
    $this->status = $menu_status;
  }

  public function grabMenus($orderBy = "ID")
  {
    return $this->menuDao->findMenus($orderBy);
  }

  public function grabMenu($id)
  {
    return $this->menuDao->findMenu($id, $this->sanitize);
  }

  public function addMenu()
  {
    $this->validator->sanitize($this->label, 'string');
    
    if (!empty($this->link)) {

      $this->validator->sanitize($this->link, 'url');

    }
    
    return $this->menuDao->insertMenu([
      'menu_label' => $this->label,
      'menu_link' => $this->link
    ]);

  }

  public function modifyMenu()
  {
    $this->validator->sanitize($this->menu_id, 'int');
    $this->validator->sanitize($this->link, 'url');
    $this->validator->sanitize($this->label, 'string');

    return $this->menuDao->updateMenu($this->sanitize, [
      'menu_label' => $this->label,
      'menu_link' => $this->link,
      'menu_sort' => $this->order,
      'menu_status' => $this->status
    ], $this->menu_id);

  }

  public function enableMenu()
  {
    $this->validator->sanitize($this->menu_id, 'int');

    if (!$data_menu = $this->menuDao->findMenu($this->menu_id, $this->sanitize)) {
       direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->activateMenu($this->menu_id, $this->sanitize);

  }

  public function disableMenu()
  {
    $this->validator->sanitize($this->menu_id, $this->sanitize);

    if (!$data_menu = $this->menuDao->findMenu($this->menu_id, $this->sanitize)) {
      direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->deactivateMenu($this->menu_id, $this->sanitize);
    
  }

  public function removeMenu()
  {
    $this->validator->sanitize($this->menu_id, 'int');

    if (!$data_menu = $this->menuDao->findMenu($this->menu_id, $this->sanitize)) {
       direct_page('index.php?load=menu&error=menuNotFound', 404);
    }

    return $this->menuDao->deleteMenu($this->menu_id, $this->sanitize);

  }
  
  public function isMenuExists($menu_label)
  {
    return $this->menuDao->menuExists($menu_label);
  }

  public function totalMenus($data = null)
  {
    return $this->menuDao->totalMenuRecords($data);
  }

}