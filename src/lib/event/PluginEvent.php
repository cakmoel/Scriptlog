<?php
/**
 * PluginEvent Class
 *
 * @package   SCRIPTLOG/LIB/EVENT/PluginEvent
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PluginEvent
{
  /**
   * Plugin's ID
   * 
   * @var integer
   */
  private $plugin_id;
  
  /**
   * Plugin's name
   * 
   * @var string
   */
  private $name;
  
  /**
   * Plugin's link
   * 
   * @var string
   */
  private $link;

  /**
   * Plugin's description
   * 
   * @var string
   */
  private $description;
  
  /**
   * Plugin's status
   * 
   * @var string
   */
  private $status;
  
  /**
   * Plugin's level
   * 
   * @var string
   */
  private $level;
  
  /**
   * Sort
   * 
   * @var string
   */
  private $sort;

/**
 * Plugin DAO
 * 
 * @var object
 * 
 */
  private $pluginDao;

/**
 * Validator
 * 
 * @var object
 * 
 */
  private $validator;

/**
 * Sanitize
 * 
 * @var object
 * 
 */
  private $sanitize;
  
  /**
   * Constructor
   * 
   * @param object $pluginDao
   * @param object $validator
   * @param object $sanitize
   * 
   */
  public function __construct(Plugin $pluginDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->pluginDao = $pluginDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  public function setPluginId($pluginId)
  {
    $this->plugin_id = $pluginId;
  }

  public function setPluginName($name)
  {
    $this->name = $name;
  }

  public function setPluginLink($link)
  {
    $this->link = $link;
  }

  public function setPluginDescription($description)
  {
    $this->description = $description;
  }

  public function setPluginStatus($status)
  {
    $this->status = $status;
  }

  public function setPluginLevel($level)
  {
    $this->level = $level;
  }

  public function setPluginSort($sort)
  {
    $this->sort = $sort;
  }

  public function grabPlugins($orderBy = 'ID')
  {
    return $this->pluginDao->getPlugins($orderBy);
  }

  public function grabPlugin($id)
  {
    return $this->pluginDao->getPlugin($id, $this->sanitize);
  }

  public function addPlugin()
  {
    $this->validator->sanitize($this->name, 'string');
    $this->validator->sanitize($this->link, 'url');
    $this->validator->sanitize($this->description, 'string');

    if(empty($this->link)) $this->link = "#";

    return $this->pluginDao->insertPlugin([
      'plugin_name' => $this->name,
      'plugin_link' => $this->link,
      'plugin_desc' => $this->description,
      'plugin_level' => $this->level
    ]);

  }

  public function modifyPlugin()
  {
    $this->validator->sanitize($this->plugin_id, 'int');
    $this->validator->sanitize($this->name, 'string');
    $this->validator->sanitize($this->link, 'string');
    $this->validator->sanitize($this->description, 'string');

    return $this->pluginDao->updatePlugin($this->sanitize, [
      'plugin_name'   => $this->name,
      'plugin_link'   => $this->link,
      'plugin_desc'   => $this->description,
      'plugin_status' => $this->status,
      'plugin_level'  => $this->level,
      'plugin_sort'   => $this->sort
    ], $this->plugin_id);

  }

  public function activateInstalledPlugin()
  {

    $this->validator->sanitize($this->plugin_id, 'int');

    if(!($data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize))) {
      direct_page('index.php?load=plugins&error=pluginNotFound', 404);
    }

    $sql_path = '../library/plugins/'.$data_plugin['plugin_name'].'sql';

    if(file_exists($sql_path)) {

      $sql_contents = file_get_contents($sql_path);
      $sql_contents = explode(";", $sql_contents);
      
      foreach($sql_contents as $sql) {

        $result = '';
        $result = $this->pluginDao->setSQL($sql);

        if(!$result) {

          unlink($sql_path);
          direct_page('index.php?load=plugins&error=tableNotFound', 404);

        } else {

          return $this->pluginDao->activatePlugin($this->plugin_id, $this->sanitize);
          unlink($sql_path);

        }

      }

    } else {

       return $this->pluginDao->activatePlugin($this->plugin_id, $this->sanitize);
       
    }

  }

  public function deactivateInstalledPlugin()
  {
    $this->validator->sanitize($this->plugin_id, 'int');

    if (!$data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize)) {
      direct_page('index.php?load=plugins&error=pluginNotFound', 404);
    }

    return $this->pluginDao->deactivatePlugin($this->plugin_id, $this->sanitize);

  }

  public function removePlugin()
  {
    
    $this->validator->sanitize($this->plugin_id, 'int');

    if (!$data_plugin = $this->pluginDao->getPlugin($this->plugin_id, $this->sanitize)) {
       direct_page('index.php?load=plugins&error=pluginNotFound', 404);
    }

    $plugin_name = $data_plugin['plugin_name'];
    $plugin_link = $data_plugin['plugin_link'];

    if ($plugin_link != '#') {

       if (is_readable("../library/plugins/$plugin_name")) {
          
          delete_directory("../library/plugins/$plugin_name");
          unlink("../library/plugins/$plugin_name.php");

       }
 
    } 

    return $this->pluginDao->deletePlugin($this->plugin_id, $this->sanitize);
    
  }

  /**
   * checking plugin exists or not
   * 
   * @param string $plugin_name
   */
  public function isPluginExists($plugin_name)
  {
    return $this->pluginDao->pluginExists($plugin_name);
  }

  /**
   * Plugin level drop down
   * 
   * @param string $selected
   * @return string
   * 
   */
  public function pluginLevelDropDown($selected = "")
  {
    return $this->pluginDao->dropDownPluginLevel($selected);
  }

  /**
   * Total plugin records
   * 
   * @param array $data
   * @return integer
   */
  public function totalPlugins($data = null)
  {
    return $this->pluginDao->totalPluginRecords($data);
  }
  
}