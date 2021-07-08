<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Menu extends Dao 
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class MenuDao extends Dao
{
 
/**
 * 
 */
 public function __construct()
 {
	parent::__construct();	
 }

/**
 * Find all menus
 * 
 * @method public findMenus()
 * @param integer $orderBy
 * 
 */
 public function findMenus($orderBy = 'ID')
 {
    $sql = "SELECT ID, parent_id, menu_label, menu_link, menu_sort, menu_status, menu_position
            FROM tbl_menu ORDER BY :orderBy DESC";

    $this->setSQL($sql);

    $allMenu = $this->findAll([':orderBy' => $orderBy]);

    return (empty($allMenu)) ?: $allMenu;
     
 }

 /**
  * Find Menu
  * 
  * @param integer $menuId
  * @param object $sanitizing
  * @param static $fetchMode
  * @return boolean|array|object
  *
  */
 public function findMenu($menuId, $sanitizing)
 {
     
     $sql = "SELECT ID, parent_id, menu_label, menu_link, menu_sort, menu_status, menu_position
             FROM tbl_menu WHERE ID = ?";
     
     $idsanitized = $this->filteringId($sanitizing, $menuId, 'sql');
     
     $this->setSQL($sql);
     
     $menuDetail = $this->findRow([$idsanitized]);
     
     return (empty($menuDetail)) ?: $menuDetail;
     
 }
 
 public function findMenuParent($parentId, $sanitizing)
 {
   
   $sql = "SELECT ID, parent_id, menu_label FROM tbl_menu WHERE ID = :parent_id";
   
   $idsanitized = $this->filteringId($sanitizing, $parentId, 'sql');

   $this->setSQL($sql);

   $menuParent = $this->findRow([':parent_id' => $idsanitized]);

   return (empty($menuParent)) ?: $menuParent;

 }

 /**
  * Insert new menu
  * 
  * @param array $bind
  */
 public function insertMenu($bind)
 {
     
   $this->create("tbl_menu", [
       'parent_id' => $bind['parent_id'],
       'menu_label' => $bind['menu_label'],
       'menu_link' => $bind['menu_link'],
       'menu_sort' => $this->findSortMenu()
   ]);
   
   $menu_id = $this->lastId();

   $getLink = "SELECT ID, menu_link FROM tbl_menu WHERE ID = ?";

   $this->setSQL($getLink);

   $link = $this->findRow([$menu_id]);

   if ($link['menu_link'] == '') {
     
      $this->modify("tbl_menu", ['menu_link' => '#'], "ID = {$link['ID']}");
       
   }
   
 }
 
 /**
  * Update menu
  * 
  * @param integer $id
  * @param array $bind
  */
 public function updateMenu($sanitize, $bind, $ID)
 {
  
  $cleanId = $this->filteringId($sanitize, $ID, 'sql');
  $this->modify("tbl_menu", [
      'parent_id' => $bind['parent_id'],
      'menu_label' => $bind['menu_label'],
      'menu_link' => $bind['menu_link'],
      'menu_sort' => $bind['menu_sort'],
      'menu_status' => $bind['menu_status'], 
      'menu_position' => $bind['menu_position']
  ], "ID = {$cleanId}");
  
 }
 
 /**
  * Activate menu
  * 
  * @param integer $id
  * @param object $sanitize
  *
  */
 public function activateMenu($id, $sanitize)
 {
   $idsanitized = $this->filteringId($sanitize, $id, 'sql');
   $this->modify("tbl_menu", ['menu_status' => 'Y'], "ID => {$idsanitized}");
 }

 /**
  * Deactivate menu
  *
  * @param integer $id
  * @param object $sanitize
  *
  */
 public function deactivateMenu($id, $sanitize)
 {
  $idsanitized = $this->filteringId($sanitize, $id, 'sql');
  $this->modify("tbl_menu", ['menu_status' => 'N'], "ID => {$idsanitized}");
 }

 /**
  * Delete menu
  * 
  * @param integer $id
  * @param object $sanitizing
  *
  */
 public function deleteMenu($id, $sanitize)
 {
  $clean_id = $this->filteringId($sanitize, $id, 'sql');
  $this->deleteRecord("tbl_menu", "ID = ".(int)$clean_id);
 }

 /**
  * Check menu id
  * 
  * @param integer $id
  * @param object $sanitizing
  * @return numeric
  *
  */
 public function checkMenuId($id, $sanitizing)
 {
  
  $sql = "SELECT ID FROM tbl_menu WHERE ID = ?";
  
  $idsanitized = $this->filteringId($sanitizing, $id, 'sql');
  
  $this->setSQL($sql);
  
  $stmt = $this->checkCountValue([$idsanitized]);
  
  return($stmt > 0);
  
 }

/**
 * Menu parent does exists or not
 * 
 * @method public menuExists()
 * @param string $menu_label
 */
 public function menuExists($menu_label)
 {
   $sql = "SELECT COUNT(ID) FROM tbl_menu WHERE menu_label = ?";
   $this->setSQL($sql);
   $stmt = $this->findColumn([$menu_label]);

   if ($stmt == 1) {

      return true;

   } else {

      return false;

   }

 }

/**
 * dropDownMenuParent
 * 
 * Retrieving menu ID to drop down menu parent
 *
 * @param string $selected
 * @return void
 * 
 */
 public function dropDownMenuParent($selected = '')
 {

   $option_selected = '';

   if (!$selected) {
     $option_selected = ' selected="selected"';
   }

   $menus = $this->findMenus();
   
   $dropDown = '<select class="form-control" name="parent_id" id="parent">'."\n"; 

   if (is_array($menus)) {

   foreach ($menus as $menu) {
      
      if ((int)$selected === (int)$menu['ID']) {

          $option_selected = ' selected="selected"';
          
      }

      $dropDown .= '<option value="'.$menu['ID'].'"'.$option_selected.'>'.$menu['menu_label'].'</option>'."\n";

      $option_selected = '';

   }

  }

   if (empty($selected) || empty($menu['ID'])) {
      $dropDown .= '<option value="0" selected>--Parent--</option>';
   }
   
   $dropDown .= '</select>'."\n";

   return $dropDown;
   
 }

/**
 * dropDownMenuPosition
 *
 * @param string $selected
 * @return string
 * 
 */
public function dropDownMenuPosition($selected = '')
{

$name = 'menu_position';
$menu_position = array('header'=>'Header', 'footer'=> 'Footer');

if ($selected != '') {

  $selected = $selected;

}

return dropdown($name, $menu_position, $selected);

}

/**
 * Total menu records
 * 
 * @method public totalMenuRecords()
 * @param array $data = null
 * @return integer|numeric
 * 
 */
 public function totalMenuRecords($data = null)
 {
   $sql = "SELECT ID FROM tbl_menu";
   $this->setSQL($sql);
   return $this->checkCountValue($data);
 }
 
 /**
  * Find menu sorted
  * 
  * @return number
  */
 private function findSortMenu()
 {
 
  $sql = "SELECT menu_sort FROM tbl_menu ORDER BY menu_sort DESC";
 
  $this->setSQL($sql);
  
  $field = $this->findColumn();
  
  return $field['menu_sort'] + 1;
  
 }

}