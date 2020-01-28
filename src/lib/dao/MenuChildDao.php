<?php 
/**
 * Menu Child class extends Dao
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class MenuChildDao extends Dao
{

/**
 * 
 */
 public function __construct()
 {
    parent::__construct();
    
 }

/**
 * Retrieve all menuchilds record
 * 
 * @method public findMenuChilds()
 * @param mixed $orderBy
 * @return array
 * 
 */
 public function findMenuChilds($orderBy = 'ID')
 {
    
    $sql = "SELECT mc.ID, mc.menu_child_label, mc.menu_child_link, 
           mc.menu_id, mc.menu_sub_child, mc.menu_child_sort, 
           mc.menu_child_status, mp.menu_label
           FROM tbl_menu_child AS mc
           INNER JOIN  tbl_menu AS mp ON mc.menu_id = mp.ID
           ORDER BY :orderBy DESC";
             
    $this->setSQL($sql);
         
    $menuChilds = $this->findAll([':orderBy' => $orderBy]);
         
    return (empty($menuChilds)) ?: $menuChilds;
   
 }
 
/**
 * Retrieve menuChild record by Id
 * 
 * @method public findMenuChild()
 * @param integer|numeric $id
 * @param object $sanitize
 * @return array
 * 
 */
 public function findMenuChild($id, $sanitize)
 {
   $sql = "SELECT mc.ID, mc.menu_child_label, mc.menu_child_link, 
           mc.menu_id, mc.menu_sub_child, mc.menu_child_sort, 
           mc.menu_child_status, mp.menu_label
           FROM tbl_menu_child AS mc
           INNER JOIN  tbl_menu AS mp ON mc.menu_id = mp.ID
           WHERE mc.ID = ?";
   
   $idsanitized = $this->filteringId($sanitize, $id, 'sql');
   
   $this->setSQL($sql);
   
   $menuChildDetails = $this->findRow([$idsanitized]);
   
   return (empty($menuChildDetails)) ?: $menuChildDetails;
   
 }
 
/**
 * Find sort menu by Ascending Sortable
 * 
 * @param integer|numeric $id
 * @param object $sanitize
 * @return array
 * 
 */
 public function findAscMenu($id, $sanitize)
 {
   
  $sql = "SELECT menu_id FROM tbl_menu_child WHERE ID = ?";
   
  $idsanitized = $this->filteringId($sanitize, $id, 'sql');
   
  $this->setSQL($sql);
   
  $ascendentMenu = $this->findColumn([$idsanitized]);
   
  return (empty($ascendentMenu)) ?: $ascendentMenu;

 }

/**
 * Insert new menu child record
 * 
 * @method public insertMenuChild()
 * @param array $bind
 * 
 */
 public function insertMenuChild($bind)
 {
     
  $menuChildSorted = $this->findSortMenuChild();
  
  $this->create("tbl_menu_child", [
      'menu_child_label' => $bind['menu_child_label'],
      'menu_child_link'  => $bind['menu_child_link'],
      'menu_id' => $bind['menu_id'],
      'menu_sub_child' => $bind['menu_sub_child'],
      'menu_child_sort' => $menuChildSorted
  ]);
 
  $sql = "SELECT ID, menu_child_link FROM tbl_menu_child WHERE ID = ?";
  
  $this->setSQL($sql);
  
  $getChildLink = $this->findColumn([$this->lastId()]);
  
  $data_child = array("menu_child_link" => ['#']);

  $menuChildLink = (!empty($getChildLink['menu_child_link'])) ?: $this->modify("tbl_menu_child", $data_child, "ID = ".(int)$getChildLink['ID']);
  
 }
 
/**
 * Updating an exist menu child record
 * 
 * @method public updateMenuChild()
 * @param object $sanitize
 * @param array $bind
 * @param integer $ID
 * 
 */
 public function updateMenuChild($sanitize, $bind, $ID)
 {
   
   $cleanId = $this->filteringId($sanitize, $ID, 'sql');
   $this->modify("tbl_menu_child", [
       'menu_child_label' => $bind['menu_child_label'],
       'menu_child_link'  => $bind['menu_child_link'],
       'menu_id' => $bind['menu_id'],
       'menu_sub_child' => $bind['menu_sub_child'],
       'menu_child_sort' => $bind['menu_child_sort'],
       'menu_child_status' => $bind['menu_child_status']
   ], "`ID` = {$cleanId}");

 }
 
/**
 * Activate menu child
 * 
 * @method public activateMenuChild()
 * @param integer $id
 * @param object $sanitize
 * 
 */
 public function activateMenuChild($id, $sanitize)
 {
   $idsanitized = $this->filteringId($sanitize, $id, 'sql');
   $this->modify("tbl_menu_child", ['menu_child_status' => 'Y'], "`ID` => {$idsanitized}");
 }

 public function deactivateMenuChild($id, $sanitize)
 {
   $idsanitized = $this->filteringId($sanitize, $id, 'sql');
   $this->modify("tbl_menu_child", ['menu_child_status' => 'N'], "`ID` => {$idsanitized}");
 }

 public function deleteMenuChild($id, $sanitize)
 {
   $clean_id = $this->filteringId($sanitize, $id, 'sql');
   $this->deleteRecord("tbl_menu_child", "ID = ".(int)$clean_id);    
 }
 
 public function menuChildExists($menu_child_label)
 {
   $sql = "SELECT COUNT(ID) FROM tbl_menu_child WHERE menu_child_label = ?";
   
   $this->setSQL($sql);
   
   $stmt = $this->findColumn([$menu_child_label]);
   
   if ($stmt == 1) {
       
    return true;

   } else {
       
    return false;

   }
   
 }
 
 public function checkMenuChildId($id, $sanitizing)
 {
     $sql = "SELECT ID FROM tbl_menu_child WHERE ID = ?";
     
     $idsanitized = $this->filteringId($sanitizing, $id, 'sql');
     
     $this->setSQL($sql);
     
     $stmt = $this->checkCountValue([$idsanitized]);
     
     return($stmt > 0);
 }
 
 public function dropDownMenuChild($selected = '') 
 {
   $option_selected = '';

   if (!$selected) {
      $option_selected = ' selected="selected"';
   }

   $subMenus = $this->findMenuChilds();

   $dropDown = '<select class="form-control" name="child" id="child">'."\n";

   if (!empty($subMenus)) {

    foreach ($subMenus as $subMenu) {

      if ((int)$selected === (int)$subMenu['ID']) {

          $option_selected = ' selected="selected"';

      }

      $dropDown .= '<option value="'.$subMenu['ID'].'"'.$option_selected.'>'.$subMenu['menu_child_label'].'</option>'."\n";

      $option_selected = '';

    }
   
  }

   if (empty($selected) || empty($menu['ID'])) {
      $dropDown .= '<option value="0" selected>--Sub Menu--</option>';
   }

   $dropDown .= '</select>'."\n";

   return $dropDown;
   
 }

 private function findSortMenuChild()
 {
   $sql = "SELECT menu_child_sort FROM tbl_menu_child ORDER BY menu_child_sort DESC";
   
   $this->setSQL($sql);
   
   $field = $this->findColumn();
   
   return  $field->menu_child_sort + 1;
   
 }
 
 public function totalMenuChildRecords($data = null)
 {
   $sql = "SELECT ID FROM tbl_menu_child";
   $this->setSQL($sql);
   return $this->checkCountValue($data);  
 }
 
}