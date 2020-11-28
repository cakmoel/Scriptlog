<?php  
/**
 * Plugin class extends Plugin
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PluginDao extends Dao
{

  protected $accessLevel;
  
  public function __construct()
  {
     parent::__construct();
  }
  
  /**
   * Get list of plugins
   * 
   * @param integer $position
   * @param integer $limit
   * @param string $orderBy
   * @return boolean|array|object
   * 
   */
  public function getPlugins($orderBy = 'ID')
  {
     
    $sql = "SELECT ID, plugin_name, plugin_link, plugin_directory, plugin_desc, 
            plugin_status, plugin_level,
            plugin_sort FROM tbl_plugin ORDER BY :orderBy DESC";
   
    $this->setSQL($sql);
    
    $plugins = $this->findAll([':orderBy' => $orderBy]);
  
    return (empty($plugins)) ?: $plugins;
    
  }
  
  /**
   * Get plugin
   * get single value of plugin
   * 
   * @param integer $id
   * @param object $sanitize
   * @return boolean|array|object
   * 
   */
  public function getPlugin($id, $sanitize)
  {
     $idsanitized = $this->filteringId($sanitize, $id, 'sql');
     
     $sql = "SELECT ID, plugin_name, plugin_link, plugin_directory, plugin_desc, 
             plugin_status, plugin_level, plugin_sort 
             FROM tbl_plugin WHERE ID = :ID"; 
     
     $this->setSQL($sql);
     
     $pluginDetail = $this->findRow([':ID' => $idsanitized]);
     
     return (empty($pluginDetail)) ?: $pluginDetail;
     
  }
  
  /**
   * Insert new plugin
   * 
   * @param array $bind
   * 
   */
  public function insertPlugin($bind)
  {
    $getSort = "SELECT plugin_sort FROM tbl_plugin ORDER BY plugin_sort DESC";
    $this->setSQL($getSort);
    $rows = $this->findColumn();
    $plugin_sorted = $rows['plugin_sort'] + 1;

     // input data plugin
     $this->create("tbl_plugin", [
         'plugin_name' => $bind['plugin_name'],
         'plugin_link' => $bind['plugin_link'],
         'plugin_directory' => $bind['plugin_directory'],
         'plugin_desc' => $bind['plugin_desc'],
         'plugin_level' => $bind['plugin_level'],
         'plugin_sort' => $plugin_sorted
     ]);
     
     $plugin_id = $this->lastId();
     
     $getLink = "SELECT ID, plugin_link FROM tbl_plugin WHERE ID = ?";
     
     $this->setSQL($getLink);
     
     $link = $this->findRow([$plugin_id]);
     
     if ($link['plugin_link'] == '') {
         
        $this->modify("tbl_plugin", ['plugin_link' => '#'], "ID = ".(int)$link['ID']);
         
     }
     
  }
  
  /**
   * Update plugin
   * 
   * @param integer $id
   * @param array $bind
   * 
   */
  public function updatePlugin($sanitize, $bind, $id)
  {

    $cleanId = $this->filteringId($sanitize, $id, 'sql');

    $this->modify("tbl_plugin", [
        'plugin_name' => $bind['plugin_name'],
        'plugin_link' => $bind['plugin_link'],
        'plugin_directory' => $bind['plugin_directory'],
        'plugin_desc' => $bind['plugin_desc'],
        'plugin_status' => $bind['plugin_status'],
        'plugin_sort' => $bind['plugin_sort']
    ],  "ID = ".(int)$cleanId);
    
  }
  
  /**
   * activatePlugin
   * Enable plugin
   * 
   * @param integer $id
   * 
   */
  public function activatePlugin($id, $sanitize)
  {
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    $this->modify("tbl_plugin", ['plugin_status' => 'Y'], "ID = ".(int)$idsanitized);
  }
  
  /**
   * deactivatePlugin
   * Disable plugin
   * 
   * @param integer $id
   * 
   */
  public function deactivatePlugin($id, $sanitize)
  {
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    $this->modify("tbl_plugin", ['plugin_status' => 'N'], "ID = ".(int)$idsanitized);  
  }
  
  /**
   * Delete plugin
   * 
   * @param integer $id
   * @param object $sanitize
   */
  public function deletePlugin($id, $sanitize)
  {
    $clean_id = $this->filteringId($sanitize, $id, 'sql');
    $this->deleteRecord("tbl_plugin", "ID = ".(int)$clean_id);
  }
  
  /**
   * Check plugin Id
   * 
   * @param integer $id
   * @param object $sanitize
   * @return numeric
   * 
   */
  public function checkPluginId($id, $sanitize)
  {

    $sql = "SELECT ID FROM tbl_plugin WHERE ID = ?";

    $idsanitized = $this->filteringId($sanitize, $id, 'sql');

    $this->setSQL($sql);

    $stmt = $this->checkCountValue([$idsanitized]);

    return($stmt > 0);

  }
  
  /**
   * isPluginActived
   * 
   * checking whether plugin enabled or disabled
   * 
   * @param string $plugin_name
   * @return boolean
   * 
   */
  public function isPluginActived($plugin_name)
  {
      if ($this->pluginExists($plugin_name) == true) {
          
         $sql = "SELECT plugin_status FROM tbl_plugin WHERE plugin_name = ?";
         
         $this->setSQL($sql);
         
         $plugin_status = $this->findColumn([$plugin_name]);
         
         return (empty($plugin_status)) ?: $plugin_status;

      } else {
         
          return false;
          
      }
    
  }
   
  /**
   * Set menu plugin
   * 
   * @param UserEvent $userEvent
   * @return string
   * 
   */
  public function setMenuPlugin($module, $user_level)
  {
    $this->accessLevel = $user_level;
    
    $plugins = $this->findPluginsEnabled($this->accessLevel);

    $html = '<li class="header">PLUGIN NAVIGATION</li>';

    if (is_array($plugins)) {

      foreach ($plugins as $plugin) {
        
        $pluginPath = __DIR__ .'/../../'.APP_PLUGIN.safe_html(strtolower($plugin['plugin_name'])).'/'.absolute_path((make_slug(strtolower($plugin['plugin_name'])).'.php'));
        
        if ( $this->accessLevel == 'administrator' || $this->accessLevel == 'manager' ) {
              
            if (is_dir(__DIR__ . '/../../'.APP_PLUGIN.safe_html($plugin['plugin_name'])) && is_readable($pluginPath)) {
                
              $plugin_selected = (isset($module) && $module == $plugin['plugin_name']) ? 'class="active"' : 'class=""';

              $html .= '<li "'.$plugin_selected.'">';

              (isset($plugin['plugin_status']) && $plugin['plugin_status'] == 'Y') ? $html .= '<a href="'.safe_html($plugin['plugin_link']).'">'.clip('clip_'.safe_html($plugin['plugin_name']), null, safe_html($plugin['plugin_name'])).'</a>' : 
              $html .= '<a href="#">'.clip('clip_'.safe_html($plugin['plugin_name']), null, false).'</a>';
               
              $html .= '</li>';
                
            }
             
        } 
        
      }

    }
    
    return $html;
    
  }
  
  /**
   * Drop down plugin level
   * 
   * @param string $selected
   * @return string
   * 
   */
  public function dropDownPluginLevel($selected = '')
  {
     $name = 'plugin_level';

     $plugin_level = array('administrator' => 'Administrator', 'manager' => 'Manager');

     if ($selected != '') {

         $selected = $selected;
         
     }

     return dropdown($name, $plugin_level, $selected);
 
  }
  
  /**
   * Total plugins records
   * 
   * @param array $data
   * @return numeric
   * 
   */
  public function totalPluginRecords($data = null)
  {
    $sql = "SELECT ID FROM tbl_plugin";
    $this->setSQL($sql);
    return $this->checkCountValue($data);
  }
  
  /**
   * SetPlugins
   * 
   * @return boolean|array|object
   * 
   */
  public function findPluginsEnabled($plugin_level)
  {
    $sql = "SELECT ID, plugin_name, plugin_link, plugin_directory, plugin_desc, 
            plugin_status, plugin_level, plugin_sort
            FROM tbl_plugin 
            WHERE plugin_level = :plugin_level 
            AND plugin_status = 'Y' ORDER BY plugin_name";
    
    $this->setSQL($sql);
    
    $privatePlugins = $this->findAll([':plugin_level' => $plugin_level]);
    
    return (empty($privatePlugins)) ?: $privatePlugins;
    
  }
 
  /**
   * pluginExists
   * checking whether plugin names exists 
   * 
   * @param string $plugin_name
   * @return boolean
   * 
   */
  public function pluginExists($plugin_name)
  {
    $sql = "SELECT COUNT(ID) FROM tbl_plugin WHERE plugin_name = ?";
    $this->setSQL($sql);
    $stmt = $this->findColumn([$plugin_name]);
    
    if ($stmt == 1) {
        
        return true;
        
    } else {
        
        return false;
        
    }
    
  }

}