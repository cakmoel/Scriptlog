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

  if ( !empty($bind['setting_name'])) {

    $this->modify("tbl_settings", [

      'setting_name' => $bind['setting_name'],
      'setting_value' => $bind['setting_value']
      
    ], " ID = ".(int)$cleanId);
  
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
  $clean_id = $this->filteringId($sanitize, $configId, 'sql');

  $this->deleteRecord("tbl_settings", "ID = ".(int)$clean_id);

}

/**
 * Find configurations
 * 
 * @method public findConfigs()
 * @param integer $orderBy -- default order by ID
 * @return array
 * 
 */
public function findConfigs($orderBy = 'ID', $fetchMode = null)
{
  
  $sql = "SELECT ID, setting_name, setting_value 
          FROM tbl_settings 
          ORDER BY :orderBy DESC";

	$this->setSQL($sql);

	$configs = (!is_null($fetchMode)) ? $this->findAll([':orderBy' => $orderBy], $fetchMode) : $this->findAll([':orderBy' => $orderBy]);

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
 $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings ORDER BY :orderBy DESC LIMIT :limit";

 $this->setSQL($sql);

 $general_configs = $this->findAll([':orderBy' => $orderBy, ':limit' => $limit]);

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
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE ID BETWEEN 8 AND 10 ORDER BY :orderBy ";

  $this->setSQL($sql);

  $reading_configs = $this->findAll([':orderBy' => $orderBy]);

  return ( empty($reading_configs) ) ?: $reading_configs;

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
  
  $sql = "SELECT ID, setting_name, setting_value
		      FROM tbl_settings WHERE ID = :ID ";
   
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
  
  $sql = "SELECT ID, setting_name, setting_value FROM tbl_settings WHERE setting_name = :setting_name LIMIT 1";

  $name_sanitized = $this->filteringId($sanitize, $setting_name, 'xss');

  $this->setSQL($sql);

  $detailConfig = $this->findRow([':setting_name' => $name_sanitized]);

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