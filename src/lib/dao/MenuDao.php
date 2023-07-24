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

  private $selected;

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
  public function findMenus($orderBy = 'menu_sort')
  {
    $sql = "SELECT ID, menu_label, menu_link, menu_status, menu_visibility, parent_id, menu_sort
            FROM tbl_menu ORDER BY :orderBy";

    $this->setSQL($sql);

    $allMenu = $this->findAll([':orderBy' => $orderBy]);

    return (empty($allMenu)) ?: $allMenu;
  }

  /**
   * findMenu
   * 
   * @param integer $menuId
   * @param object $sanitizing
   * @param static $fetchMode
   * @return boolean|array|object
   *
   */
  public function findMenu($menuId, $sanitizing)
  {
    $idsanitized = $this->filteringId($sanitizing, $menuId, 'sql');

    $grab_menu = medoo_column_where("tbl_menu", [
      "ID", "menu_label", "menu_status", "menu_visibility", "parent_id", "menu_sort"
    ], ["ID" => $idsanitized]);

    return (empty($grab_menu)) ?: $grab_menu; 
  }

  /**
   * findMenuParent
   *
   * @param num|int $parentId
   * @param object $sanitizing
   * 
   */
  public function findMenuParent($parentId, $sanitizing)
  {

    $sql = "SELECT ID, parent_id, menu_label FROM tbl_menu WHERE ID = :parent_id";

    $idsanitized = $this->filteringId($sanitizing, $parentId, 'sql');

    $this->setSQL($sql);

    $menuParent = $this->findRow([':parent_id' => $idsanitized]);

    return (empty($menuParent)) ?: $menuParent;
  }

  /**
   * insertMenu()
   * 
   * @param array $bind
   * 
   */
  public function insertMenu($bind)
  {

    // checking sort is empty or not
    $sorted = isset($bind['menu_sort']) ? abs((int)$bind['menu_sort']) : "0";
    
    // first update all menu_sort greater than or equal to $sorted
    // UPDATE tbl_menu SET menu_sort = menu_sort + 1 WHERE menu_sort >= $sorted

    medoo_update("tbl_menu", [
      "menu_sort[+]" => 1
    ], [
      "menu_sort[>=]" => $sorted
    ]);

    $this->create("tbl_menu", [
      'menu_label' => $bind['menu_label'],
      'menu_link' => $bind['menu_link'],
      'menu_visibility' => $bind['menu_visibility'],
      'parent_id' => $bind['parent_id'],
      'menu_sort' => $sorted
    ]);

    $menu_id = $this->lastId();

    $grablink =  "SELECT ID, menu_link FROM tbl_menu WHERE ID = ?";

    $this->setSQL($grablink);

    $link = $this->findRow([$menu_id]);

    if ($link['menu_link'] === '') {

      $this->modify("tbl_menu", ['menu_link' => '#'], "ID = {$link['ID']}");
    }

    return $menu_id;

  }

  /**
   * Update menu
   * 
   * @param integer $id
   * @param array $bind
   */
  public function updateMenu($sanitize, $bind, $id)
  {

    $cleanid = $this->filteringId($sanitize, $id, 'sql');

    $grab_current_sort = "SELECT ID, menu_sort FROM tbl_menu WHERE ID = :ID";
    $this->setSQL($grab_current_sort);
    $current_sort = $this->findRow([':ID' => $cleanid]);

    // update all menu_sort between $old_sort and $new_sort
    $temp_id = $this->updateMenuSort($current_sort['menu_sort'], $bind['menu_sort']);

    $this->modify("tbl_menu", [
      'menu_label' => $bind['menu_label'],
      'menu_link' => $bind['menu_link'],
      'menu_status' => $bind['menu_status'],
      'menu_visibility' => $bind['menu_visibility'],
      'parent_id' => $bind['parent_id'],
      'menu_sort' => $bind['menu_sort']
    ], "ID = $temp_id");
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
    $cleanid = $this->filteringId($sanitize, $id, 'sql');
    $this->deleteRecord("tbl_menu", "ID = $cleanid");
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

    return $stmt > 0;
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

    return ($stmt === 1) ? true : false;
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

    $dropDown = '<select class="form-control" name="parent_id" id="parent">' . "\n";

    if (is_iterable($menus)) {

      foreach ($menus as $menu) {

        if ((int)$selected === (int)$menu['ID']) {

          $option_selected = ' selected="selected"';
        }

        $dropDown .= '<option value="' . $menu['ID'] . '"' . $option_selected . '>' . $menu['menu_label'] . '</option>' . "\n";

        $option_selected = '';
      }
    }

    if (empty($selected) || empty($menu['ID'])) {
      $dropDown .= '<option value="0" selected>--Parent--</option>';
    }

    $dropDown .= '</select>' . "\n";

    return $dropDown;
  }

  /**
   * dropDownMenuPosition
   *
   * @param string $selected
   * @return string
   * 
   */
  public function dropDownMenuVisibility($selected = '')
  {

    $name = 'menu_visibility';

    $menu_visibility = array('public' => 'Public', 'private' => 'Private');

    if ($selected !== '') {

      $this->selected = $selected;
    }

    return dropdown($name, $menu_visibility, $this->selected);
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
   * updateMenuSort
   *
   * @param object $sanitize
   * @param num|int $old_sort
   * @param num|int $new_sort
   * 
   */
  private function updateMenuSort($old_sort, $new_sort)
  {
    $grab_menu = "SELECT ID, menu_sort FROM tbl_menu WHERE menu_sort = ?";
    $this->setSQL($grab_menu);
    $temp_data = $this->findRow([$old_sort]);
    $temp_id = isset($temp_data['ID']) ? (int)$temp_data['ID'] : "";
    $temp_sort = isset($temp_data['menu_sort']) ? (int)$temp_data['menu_sort'] : "";

    if ($temp_sort < $new_sort) {

      $sclause = " menu_sort = menu_sort - 1";
      $wclause = " menu_sort > $temp_sort AND menu_sort <= $new_sort";

    } else {

      $sclause = " menu_sort = menu_sort + 1";
      $wclause = " menu_sort < $temp_sort AND menu_sort >= $new_sort";

    }

    db_simple_query("UPDATE tbl_menu SET $sclause WHERE $wclause");

    return $temp_id;
  }
}
