<?php 
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
    $sql = "SELECT ID, menu_label, menu_link, menu_sort, menu_status
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
     
     $sql = "SELECT ID, menu_label, menu_link, menu_sort, menu_status
             FROM tbl_menu WHERE ID = ?";
     
     $idsanitized = $this->filteringId($sanitizing, $menuId, 'sql');
     
     $this->setSQL($sql);
     
     $menuDetail = $this->findRow([$idsanitized]);
     
     return (empty($menuDetail)) ?: $menuDetail;
     
 }
 
 /**
  * Insert new menu
  * 
  * @param array $bind
  */
 public function insertMenu($bind)
 {
     
   $this->create("tbl_menu", [
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
      'menu_label' => $bind['menu_label'],
      'menu_link' => $bind['menu_link'],
      'menu_sort' => $bind['menu_sort'],
      'menu_status' => $bind['menu_status']
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
  $cleanId = $this->filteringId($sanitize, $id, 'sql');
  $this->deleteRecord("tbl_menu", "ID = {$cleanId}");
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
  * Drop down menu
  *
  * @param string $selected
  *
  */
 public function dropDownMenu($selected = '')
 {
   $option_selected = '';

   if (!$selected) {
     $option_selected = ' selected="selected"';
   }

   $menus = $this->findMenus();
   
   $dropDown = '<select class="form-control" name="parent" id="parent">'."\n"; 

   if (!empty($menus)) {

   foreach ($menus as $menu) {
      
      if ((int)$selected === (int)$menu['ID']) {

          $option_selected = ' selected="selected"';
          
      }

      $dropDown .= '<option value="'.$menu['ID'].'"'.$option_selected.'>'.$menu['menu_label'].'</option>'."\n";

      $option_selected = '';

   }

  }

   if (empty($selected) || empty($menu['ID'])) {
      $dropDown .= '<option value="0" selected>--Menu--</option>';
   }
   
   $dropDown .= '</select>'."\n";

   return $dropDown;
   
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
  * Find Front Navigation
  * @param string $uri
  *
  */
 public function findFrontNavigation($uri)
 {
  
    $parent = "SELECT ID, menu_label, menu_link, menu_sort, menu_status 
               FROM tbl_menu WHERE menu_status = 'Y'";
    $stmt = $this->dbc->dbQuery($parent);

    $html = '';
    $html = '<ul class="navbar-nav ml-auto">';

    while ($p = $stmt -> fetch()) {
      
      if ($uri == $r['menu_label'])  { 
        $active = "active"; 
      }

       $html .= '<li class="nav-item">
                 <a href="'.transform_html($p['menu_link']).'" class="nav-link '.$active.' ">Home</a>';
       
       $active = '';

       $child = "SELECT mc.ID, mc.menu_child_label, mc.menu_child_link,
                        mc.menu_id, mc.menu_sub_child, mc.menu_child_sort, 
                        mc.menu_child_status,
                        mp.menu_label, mp.menu_link, 
                        mp.menu_sort, mp.menu_status
                 FROM tbl_menu_child AS mc
                 INNER JOIN tbl_menu AS mp ON mc.menu_id = mp.ID
                 AND mc.menu_id = {$r['ID']}
                 AND mc.menu_sub_child = 0 
                 AND mc.menu_child_status = 'Y'";

      $stmt2 = $this->dbc->dbQuery($child);
      $total = $stmt2 -> rowCount();

      // if submenu found
      if ($total > 0) {
      
        $dropdown = "dropdown";
        $dropdown_toggle = "dropdown-toggle";
        $dropdown_menu = "dropdown-menu";

        $html .= '<li class="nav-item  '.$dropdown.'">
                  <a class="nav-link  ' .$dropdown_toggle.' '.$active.'" href="'.transform_html($p['menu_link']).'" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.htmlspecialchars($p['menu_label']).'</a>';
      
        $html .= '<ul class="'.$dropdown_menu.'" aria-labelledby="navbarDropdownMenuLink">';

        while ($c = $stmt2 -> fetch()) {

             $html .= '<li><a class="dropdown-item" href="'.transform_html($c['menu_child_link']).'">'.$c['menu_child_label'].'</a>';
             
             $sub_child = "SELECT ID, menu_child_label, menu_child_link, menu_id, menu_sub_child, 
                           menu_child_sort, menu_child_status 
                           FROM tbl_menu_child 
                           WHERE menu_sub_child = {$c['ID']}
                           AND menu_sub_child != 0";

              $stmt3 = $this->dbc->dbQuery($sub_child);
              $total_sub = $stmt3 -> rowCount();

              if ($total_sub > 0) {

                 $html .= '<ul class="'.$dropdown_menu.'">';

                 while ($sc = $stmt3->fetch()) {

                   $html .= '<li><a class="dropdown-item" href=""></a></li>';

                 }

                   $html .= '</ul></li>';

              }
             
        }

        $html .= '</li></ul></li>';

      } else {

         $html .= '</li>';
         
      }

    }
   
    $html .= '</ul>';

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