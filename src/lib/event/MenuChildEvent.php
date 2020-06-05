<?php
/**
 * Menu Child Event Class
 * 
 * @category Event Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class MenuChildEvent
{
  private $child_id;

  private $child_label;

  private $child_link;

  private $ascendent_id;

  private $descendent_id;

  private $child_sort;

  private $child_status;

  private $menuChildDao;

  private $validator;

  private $sanitize;

  public function __construct(MenuchildDao $menuChildDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->menuChildDao = $menuChildDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  public function setMenuChildId($child_id)
  {
    $this->child_id = $child_id;
  }

  public function setMenuChildLabel($child_label)
  {
    $this->child_label = $child_label;
  }

  public function setMenuChildLink($child_link)
  {
    $this->child_link = $child_link;
  }

  public function setAscendent($ascendent_id)
  {
    $this->ascendent_id = $ascendent_id;
  }

  public function setDescendent($descendent_id)
  {
    $this->descendent_id = $descendent_id;
  }

  public function setMenuChildSort($child_sort)
  {
    $this->child_sort = $child_sort;
  }

  public function setMenuChildStatus($child_status)
  {
    $this->child_status = $child_status;
  }

  public function grabMenuChilds($orderBy = 'ID')
  {
    return $this->menuChildDao->findMenuChilds($orderBy);
  }

  public function grabMenuChild($id)
  {
    return $this->menuChildDao->findMenuChild($id, $this->sanitize);
  } 

  public function addMenuChild()
  {
    $this->validator->sanitize($this->child_label, 'string');
    $this->validator->sanitize($this->child_link, 'url');

    $data_submenu = $this->menuChildDao->findAscMenu($this->descendent_id, $this->sanitize);

    if ($this->ascendent_id == 0) {
      $this->ascendent_id = $data_submenu['menu_id'];
    }

    return $this->menuChildDao->insertMenuChild([
      'menu_child_label' => $this->child_label,
      'menu_child-link' => $this->child_link,
      'menu_id' => $this->ascendent_id,
      'menu_sub_child' => $this->descendent_id,
      'menu_child_sort' => $this->child_sort
    ]);
  }

  public function modifyMenuChild()
  {
    $this->validator->sanitize($this->child_id, 'int');
    $this->validator->sanitize($this->child_label, 'string');
    $this->validator->sanitize($this->child_link, 'url');

    return $this->menuChildDao->updateMenuChild($this->sanitize, [
      'menu_child_label' => $this->child_label,
      'menu_child_link' => $this->child_link,
      'menu_id' => $this->ascendent_id,
      'menu_sub_child' => $this->descendent_id,
      'menu_child_sort' => $this->child_sort,
      'menu_child_status' => $this->child_status
    ], $this->child_id);
    
  }

  public function enableMenuChild()
  {
    $this->validator->sanitize($this->child_id, 'int');
    
    if (!$data_menu_child = $this->menuChildDao->findMenuChild($this->child_id, $this->sanitize)) {
       direct_page('index.php?load=menu-child&error=menuChildNotFound', 404);
    }

    return $this->menuChildDao->activateMenuChild($this->child_id, $this->sanitize);

  }

  public function disableMenuChild($id)
  {
    $this->validator->sanitize($this->child_id, 'int');

    if (!$data_menu_child = $this->menuChildDao->findMenuChild($this->child_id, $this->sanitize)) {
      direct_page('index.php?load=menu-child&error=menuChildNotFound', 404);
    }

    return $this->menuChildDao->deactivateMenuChild($this->child_id, $this->sanitize);

  }

  public function removeMenuChild()
  {
    $this->validator->sanitize($this->child_id, 'int');

    if (!$data_menu_child = $this->menuChildDao->findMenuChild($this->child_id, $this->sanitize)) {
       direct_page('index.php?load=menu-child&error=menuChildNotFound', 404);
    }

    return $this->menuChildDao->deleteMenuChild($this->child_id, $this->sanitize);
    
  }

  public function isMenuChildExists($child_label)
  {
    return $this->menuChildDao->menuChildExists($child_label);
  }

  public function descMenuDropDown($selected = "")
  {
    return $this->menuChildDao->dropDownMenuChild($selected);
  }
  
  public function totalMenuChilds($data = null)
  {
   return $this->menuChildDao->totalMenuChildRecords($data); 
  }
  
}