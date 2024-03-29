<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ThemeEvent Class
 * 
 * @category Event Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 * 
 */
class ThemeEvent
{
  /**
   * Theme ID
   * 
   * @var integer
   */
  private $theme_id;

  /**
   * Theme title
   * 
   * @var string
   */
  private $theme_title;

  /**
   * Theme description
   * 
   * @var string
   */
  private $theme_description;

  /**
   * Theme designer
   * 
   * @var string
   */
  private $theme_designer;

  /**
   * Theme directory
   * 
   * @var string
   */
  private $theme_directory;

  /**
   * Theme Status
   * 
   * @var string
   */
  private $theme_status;

  private $themeDao;

  private $validator;

  private $sanitize;

  public function __construct(ThemeDao $themeDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->themeDao = $themeDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  public function setThemeId($theme_id)
  {
    $this->theme_id = $theme_id;
  }

  public function setThemeTitle($theme_title)
  {
    $this->theme_title = $theme_title;
  }

  public function setThemeDescription($theme_description)
  {
    $this->theme_description = $theme_description;
  }

  public function setThemeDesigner($theme_designer)
  {
    $this->theme_designer = $theme_designer;
  }

  public function setThemeDirectory($theme_directory)
  {
    $this->theme_directory = $theme_directory;
  }

  public function setThemeStatus($theme_status)
  {
    $this->theme_status = $theme_status;
  }

  public function grabThemes($orderBy = 'ID') {
    return $this->themeDao->findThemes($orderBy);
  }

  public function grabTheme($id)
  {
    return $this->themeDao->findTheme($id, $this->sanitize);
  }
  
  public function addTheme() 
  {
    $this->validator->sanitize($this->theme_title, 'string');
    $this->validator->sanitize($this->theme_description, 'string');
    $this->validator->sanitize($this->theme_designer, 'string');
    
    return $this->themeDao->insertTheme([
      'theme_title' => $this->theme_title,
      'theme_desc' => $this->theme_description,
      'theme_designer' => $this->theme_designer,
      'theme_directory' => $this->theme_directory
    ]);

  }

  public function modifyTheme()
  {
    $this->validator->sanitize($this->theme_id, 'int');
    $this->validator->sanitize($this->theme_title, 'string');
    $this->validator->sanitize($this->theme_description, 'string');
    $this->validator->sanitize($this->theme_directory, 'string');
    
    $theme_config = [];
    $theme_config['info']['theme_name'] = $this->theme_title;
    $theme_config['info']['theme_designer'] = $this->theme_designer;
    $theme_config['info']['theme_description'] = $this->theme_description;
    $theme_config['info']['theme_directory'] = $this->theme_directory;

    write_ini(__DIR__ .'/../../'.APP_THEME.$this->theme_directory.DS.'theme.ini', $theme_config);
    
    return $this->themeDao->updateTheme( $this->sanitize, [

          'theme_title' => $this->theme_title,
          'theme_desc' => $this->theme_description,
          'theme_designer' => $this->theme_designer,
          'theme_directory' => $this->theme_directory

          ], $this->theme_id);

  }

  public function activateInstalledTheme()
  {
    $this->validator->sanitize($this->theme_id, 'int');
    return $this->themeDao->activateTheme($this->theme_id, $this->sanitize);
  }
   
  public function removeTheme()
  {
    $this->validator->sanitize($this->theme_id, 'int');

    if (!($data_theme = $this->themeDao->findTheme($this->theme_id, $this->sanitize))) {
      direct_page('index.php?load=templates&error=themeNotFound', 404);
    }

    $path_theme = __DIR__ . '/../../'.APP_THEME.$data_theme['theme_directory'] . DS;
    
    if ($data_theme['theme_directory'] != '') {
        delete_directory($path_theme);
    }

    return $this->themeDao->deleteTheme($this->theme_id, $this->sanitize);

  }

  public function isThemeExists($theme_title)
  {
    return $this->themeDao->themeExists($theme_title);
  }

  public function totalThemes($data = null)
  {
    return $this->themeDao->totalThemeRecords($data);
  }
  
}