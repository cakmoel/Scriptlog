<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class ThemeService
 * 
 * @category Class ThemeService
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 * 
 */
class ThemeService
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

  /**
   * ThemeDao Data Access Object for themes
   *
   * @var object 
   */
  private $themeDao;

  /**
   * FormValidator Validator for input data
   *
   * @var object
   */
  private $validator;

  /** @var Sanitize Sanitization utility */
  private $sanitize;

  /**
   * Constructor
   * 
   * @param ThemeDao $themeDao
   * @param FormValidator $validator
   * @param Sanitize $sanitize
   */
  public function __construct(ThemeDao $themeDao, FormValidator $validator, Sanitize $sanitize)
  {
    $this->themeDao = $themeDao;
    $this->validator = $validator;
    $this->sanitize = $sanitize;
  }

  /**
   * Set Theme ID
   * 
   * @param int $theme_id
   */
  public function setThemeId($theme_id)
  {
    $this->theme_id = $theme_id;
  }

  /**
   * Set Theme Title
   * 
   * @param string $theme_title
   */
  public function setThemeTitle($theme_title)
  {
    $this->theme_title = $theme_title;
  }

  /**
   * Set Theme Description
   * 
   * @param string $theme_description
   */
  public function setThemeDescription($theme_description)
  {
    $this->theme_description = $theme_description;
  }

  /**
   * Set Theme Designer
   * 
   * @param string $theme_designer
   */
  public function setThemeDesigner($theme_designer)
  {
    $this->theme_designer = $theme_designer;
  }

  /**
   * Set Theme Directory
   * 
   * @param string $theme_directory
   */
  public function setThemeDirectory($theme_directory)
  {
    $this->theme_directory = $theme_directory;
  }

  /**
   * Set Theme Status
   * 
   * @param string $theme_status
   */
  public function setThemeStatus($theme_status)
  {
    $this->theme_status = $theme_status;
  }

  /**
   * Retrieve themes
   * 
   * @param string $orderBy Column to order by
   * @return array
   */
  public function grabThemes($orderBy = 'ID') {
    return $this->themeDao->findThemes($orderBy);
  }

  /**
   * Retrieve a single theme
   * 
   * @param int $id
   * @return array
   */
  public function grabTheme($id)
  {
    return $this->themeDao->findTheme($id, $this->sanitize);
  }
  
  /**
   * Add a new theme
   * 
   */
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

  /**
   * Modify an existing theme
   * 
   */
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

    write_ini(__DIR__ .'/../../'.APP_THEME.$this->theme_directory.DIRECTORY_SEPARATOR.'theme.ini', $theme_config);
    
    return $this->themeDao->updateTheme( $this->sanitize, [

          'theme_title' => $this->theme_title,
          'theme_desc' => $this->theme_description,
          'theme_designer' => $this->theme_designer,
          'theme_directory' => $this->theme_directory

          ], $this->theme_id);

  }

  /**
   * Activate an installed theme
   * 
   */
  public function activateInstalledTheme()
  {
    $this->validator->sanitize($this->theme_id, 'int');
    return $this->themeDao->activateTheme($this->theme_id, $this->sanitize);
  }
  
  /**
   * Remove a theme
   * 
   * @return bool
   */
  public function removeTheme()
  {
    $this->validator->sanitize($this->theme_id, 'int');

    if (!($data_theme = $this->themeDao->findTheme($this->theme_id, $this->sanitize))) {
      
      $_SESSION['error'] = "themeNotFound";  
      direct_page('index.php?load=templates&error=themeNotFound', 404);
    }

    $path_theme = __DIR__ . '/../../'.APP_THEME.$data_theme['theme_directory'] . DIRECTORY_SEPARATOR;
    
    if ($data_theme['theme_directory'] !== '') {
        delete_directory($path_theme);
    }

    return $this->themeDao->deleteTheme($this->theme_id, $this->sanitize);

  }

  /**
   * isThemeExists
   *
   * @param string $theme_title
   * @return boolean
   */
  public function isThemeExists($theme_title)
  {
    return $this->themeDao->themeExists($theme_title);
  }

  /**
   * totalThemes
   *
   * @param array $data
   * @return integer|numeric|null
   */
  public function totalThemes(array $data = []): ?int
  {
    return $this->themeDao->totalThemeRecords($data);
  }
  
}