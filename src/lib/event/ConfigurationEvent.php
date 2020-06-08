<?php
/**
 * Class ConfigurationEvent
 *
 * @category  Event Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConfigurationEvent
{
  /**
   * setting's ID
   * 
   * @var integer
   */
  private $setting_id;
  
  /**
   * Setting name
   * 
   * @var string
   */
  private $setting_name;
  
  /**
   * Setting Value
   * 
   * @var string
   */
  private $setting_value;

/**
 * An instance of ConfigurationDao
 *
 * @var object
 * 
 */
  private $configDao;

/**
 * An instance of formValidator
 *
 * @var object
 * 
 */
  private $validator;

/**
 * An instance of sanitizer
 *
 * @var object
 * 
 */
  private $sanitizer;
  
  public function __construct(ConfigurationDao $configDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->configDao = $configDao;
    $this->validator = $validator;
    $this->sanitizer = $sanitize;
  }

  public function setConfigId($setting_id)
  {
    $this->setting_id = $setting_id;
  }

  public function setConfigName($setting_name)
  {
    $this->setting_name = prevent_injection($setting_name);    
  }

  public function setConfigValue($setting_value)
  {
    $this->setting_value = prevent_injection($setting_value);
  }

  public function grabSettings($orderBy = 'ID')
  {
    return $this->configDao->findConfigs($orderBy);
  }

  public function grabGeneralSettings($orderBy = 'ID', $limit = 7)
  {
    return $this->configDao->findGeneralConfigs($orderBy, $limit);
  }

  public function grabSettingByName($setting_name)
  {
    return $this->configDao->findConfigByName($setting_name, $this->sanitizer);
  }

  public function grabSetting($id)
  {
    return $this->configDao->findConfig($id, $this->sanitizer);
  }
  
  public function addSetting()
  {
    
    $this->validator->sanitize($this->setting_name, 'string');
    $this->validator->sanitize($this->setting_value, 'string');
    
    return $this->configDao->createConfig([
      'setting_name' => $this->setting_name,
      'setting_value' => $this->setting_value
    ]);

  }

  public function modifySetting()
  {
    
    $this->validator->sanitize($this->setting_name, 'string');
    $this->validator->sanitize($this->setting_value, 'string');
  
    return $this->configDao->updateConfig($this->sanitizer, [
       'setting_name' => $this->setting_name,
       'setting_value' => $this->setting_value
    ], $this->setting_id);
    
  }
  
  public function removeSetting()
  {
    $this->validator->sanitize($this->setting_id, 'int');

    if (!$data_config = $this->configDao->findConfig($this->setting_id, $this->sanitizer)) {
      direct_page('index.php?load=settings&error=configNotFound', 404);
    }

    return $this->configDao->deleteConfig($this->setting_id, $this->sanitizer);

  }

  public function totalSettings($data = null)
  {
    return $this->configDao->totalConfigRecords($data);
  }

}