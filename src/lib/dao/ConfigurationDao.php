<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class Configuration extends Dao
 * 
 * @category  Dao Class
 * @author    Maoelana Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConfigurationDao extends Dao
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
 * Update configuration
 * 
 * @method public updateConfig()
 * @param object $sanitize
 * @param array $bind
 * @param integer $ID
 * 
 */
public function updateConfig($sanitize, $bind, $configId)
{
  $cleanId = $this->filteringId($sanitize, $configId, 'sql');

  if (!empty($bind['setting_name'])) {

    $this->modify("tbl_settings", [

      'setting_name' => $bind['setting_name'],
      'setting_value' => $bind['setting_value']
      
    ], " ID = {$cleanId}");
  
  }
 
}

/**
 * Delete config
 * 
 * @method public deleteConfig()
 * @param integer $ID
 * 
 */
public function deleteConfig($configId, $sanitize)
{
  $this->deleteRecord("tbl_settings", "ID = ".$this->filteringId($sanitize, $configId, 'sql'));
}

/**
 * Find configurations
 * 
 * @method public findConfigs()
 * @param integer $orderBy -- default order by ID
 * @return array
 * 
 */
public function findConfigs($orderBy = 'ID')
{
  
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings ORDER BY ':orderBy' ";

	$this->setSQL($sql);

	$configs = $this->findAll([':orderBy' => $orderBy]);

	return (empty($configs)) ?: $configs;
	
}

/**
 * Find general configurations
 *
 * @param string $orderBy
 * @param integer $limit
 * @return array
 * 
 */
public function findGeneralConfigs($orderBy = 'ID', $limit = 7)
{
 $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings ORDER BY '$orderBy' DESC LIMIT :limit ";

 $this->setSQL($sql);

 $general_configs = $this->findAll([':limit' => $limit]);

 return (empty($general_configs)) ?: $general_configs;

}

/**
 * findReadingConfigs
 *
 * @param string $orderBy
 * @return array
 * 
 */
public function findReadingConfigs($orderBy = 'ID')
{
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE ID BETWEEN 8 AND 11 ORDER BY '$orderBy' ";

  $this->setSQL($sql);

  $reading_configs = $this->findAll([]);

  return (empty($reading_configs)) ?: $reading_configs;

}

/**
 * Find Configuration
 * 
 * @method public findConfig()
 * @param integer $id
 * @param object $sanitize
 * @return array
 * 
 */
public function findConfig($id, $sanitize)
{
  
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE ID = ':ID' ";
   
  $id_sanitized = $this->filteringId($sanitize, $id, 'sql');

  $this->setSQL($sql);

  $detailConfig = $this->findRow([':ID' => $id_sanitized]);

  return (empty($detailConfig)) ?: $detailConfig;
  
}

/**
 * findConfigByName
 *
 * @param string $setting_name
 * @param object $sanitize
 * @return array
 * 
 */
public function findConfigByName($setting_name, $sanitize)
{
  
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE setting_name = ? LIMIT 1";

  $name_sanitized = $this->filteringId($sanitize, $setting_name, 'xss');

  $this->setSQL($sql);

  $detailConfig = $this->findRow([$name_sanitized]);

  return (empty($detailConfig)) ?: $detailConfig;

}

/**
 * Check configuration Id
 * 
 * @method public checkConfigId()
 * @param integer $id
 * @param object $sanitize
 * 
 */
public function checkConfigId($id, $sanitize)
{
  $cleanId = $this->filteringId($sanitize, $id, 'sql');
  $sql = "SELECT ID FROM tbl_settings WHERE ID = ?";
  $this->setSQL($sql);
  $stmt = $this->checkCountValue([$cleanId]);
  return $stmt > 0;
}

/**
 * Checking to setup
 * 
 */
public function checkToSetup()
{
	$sql = "SELECT ID FROM tbl_settings";
	$this->setSQL($sql);
	$stmt = $this->checkCountValue();
	return $stmt < 1;
}

/**
 * dropDownTimezone
 *
 * @param string $selected
 * 
 */
public function dropDownTimezone($selected = null)
{

  $name = 'timezone';

  $dropdown = '<div class="form-group">'. PHP_EOL;
  $dropdown .= '<label for="timezone">Timezone</label>'. PHP_EOL;
  $dropdown .= '<select class="form-control select2" style="width: 100%;" name="'.$name.'" id="'.$name.'">'. PHP_EOL;
  $dropdown .= '<option disabled selected>Please Select Timezone</option>' . PHP_EOL;

  $this->selected = $selected;

  $timezone_list = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
  
  foreach ($timezone_list as $option) {
    
    $select = $this->selected === $option ? '  selected' : null;

    /*** add each option to the dropdown ***/
    $dropdown .= '<option value="'.$option.'"'.$select.'>'.$option.'</option>'. PHP_EOL;

  }

  /*** close the select ***/
  $dropdown .= '</select>'. PHP_EOL;
  $dropdown .= '</div>';
    
  /*** and return the completed dropdown ***/
  return $dropdown;
  
}

/**
 * Total Config records
 * 
 * @param mixed $data
 * @return integer|numeric
 * 
 */
public function totalConfigRecords($data = null)
{
  $sql = "SELECT ID FROM tbl_settings";
  $this->setSQL($sql);
  return $this->checkCountValue($data);
}

}