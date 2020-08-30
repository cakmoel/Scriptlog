<?php 
/**
 * Theme class extends Dao
 *
 * 
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ThemeDao extends Dao
{
  public function __construct()
  {
    
    parent::__construct();
    
  }
  
  /**
   * findThemes function
   * Retrieve all themes records 
   * ordered by ID (default)
   * 
   * @method mixed findThemes()
   * @param integer $orderBy
   * @return mixed
   * 
   */
  public function findThemes($orderBy = "ID")
  {
    $sql = "SELECT ID, theme_title, theme_desc, theme_designer, theme_directory, 
            theme_status FROM tbl_themes ORDER BY :orderBy DESC";
    
    $this->setSQL($sql);
    
    $themes = $this->findAll([':orderBy' => $orderBy]);

    return (empty($themes)) ?: $themes;
    
  }
  
  /**
   * findTheme function
   * Retrieve single theme record
   * based on ID
   * 
   * @method mixed findTheme()
   * @param integer $id
   * @param object $sanitize
   * @return mixed
   * 
   */
  public function findTheme($id, $sanitize)
  {
    
    $sql = "SELECT ID, theme_title, theme_desc, theme_designer, 
                theme_directory, theme_status 
            FROM tbl_themes WHERE ID = ?";
    
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');

    $this->setSQL($sql);
    
    $themeDetail = $this->findRow([$idsanitized]);
    
    return (empty($themeDetail)) ?: $themeDetail;

  }

  /**
   * addTheme function
   * Insert new theme record into theme table
   * 
   * @method mixed addTheme()
   * @param array $bind
   * 
   */
  public function insertTheme($bind)
  {
    $this->create("tbl_themes", [
      'theme_title' => $bind['theme_title'],
      'theme_desc' => $bind['theme_desc'],
      'theme_designer' => $bind['theme_designer'],
      'theme_directory' => $bind['theme_directory']
   ]);
  
  }
  
  /**
   * updateTheme function
   * update an existing theme record
   * 
   * @method mixed updateTheme()
   * @param integer $id
   * @param array $bind
   * 
   */
  public function updateTheme($sanitize, $bind, $ID)
  {

    $cleanId = $this->filteringId($sanitize, $ID, 'sql');
    $this->modify("tbl_themes", [
       'theme_title' => $bind['theme_title'],
       'theme_desc' => $bind['theme_desc'],
       'theme_designer' => $bind['theme_designer'],
       'theme_directory' => $bind['theme_directory']
     ], "ID = ".(int)$cleanId);

  }
  
  /**
   * deleteTheme function
   * Remove theme record from theme table
   * 
   * @method mixed deleteTheme()
   * @param integer $id
   * @param object $sanitize
   * 
   */
  public function deleteTheme($id, $sanitize)
  {
    $clean_id = $this->filteringId($sanitize, $id, 'sql');
    $this->deleteRecord("tbl_themes", "ID = ".(int)$clean_id);
  }
  
  /**
   * Check theme function
   * checking ID theme
   * 
   * @method mixed checkThemeId()
   * @param integer $id
   * @param object $sanitize
   * @return numeric
   * 
   */
  public function checkThemeId($id, $sanitize)
  {
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    $sql = "SELECT ID FROM tbl_themes WHERE ID = ?";
    $this->setSQL($sql);
    $stmt = $this->checkCountValue([$idsanitized]);
    return($stmt > 0);
  }

  /**
   * Is theme active or not
   * 
   * @param string $theme_title
   * @return boolen|string
   * 
   */
  public function isThemeActived($theme_title)
  {
    
    if($this->themeExists($theme_title) == true) {

       $sql = "SELECT theme_status FROM tbl_themes WHERE theme_title = ?";
       $this->setSQL($sql);
       $is_actived = $this->findColumn([$theme_title]);

       return (empty($is_actived)) ?: $is_actived;

    } else {

       return false;

    }

  }

  /**
   * Activate theme function
   * 
   * @method mixed activateTheme()
   * @param integer $id
   * @param object $
   * 
   */
  public function activateTheme($id, $sanitize)
  {
    $idsanitized = $this->filteringId($sanitize, $id, 'sql');
    
    // activate theme
    $stmt = $this->modify("tbl_themes", [
      'theme_status' => 'Y'
    ], "`ID` = {$idsanitized}");
    
  // non-activate the other table
    $stmt2 = $this->modify("tbl_themes", [
      'theme_status' => 'N'
    ], "`ID` != {$idsanitized}");
  
  }
  
  /**
   * Total theme record function
   * 
   * @method totalThemeRecords()
   * @param array $data
   * @return numeric|integer
   * 
   */
  public function totalThemeRecords($data = null)
  {
    $sql = "SELECT ID FROM tbl_themes";
    $this->setSQL($sql);
    return $this->checkCountValue($data);
  }

  /**
   * Theme exists function
   * is theme exists or not
   * 
   * @method themeExists()
   * @param string $theme_title
   * @return boolean
   * 
   */
  public function themeExists($theme_title)
  {
    $sql = "SELECT COUNT(ID) FROM tbl_themes WHERE theme_title = ?";
    $this->setSQL($sql);
    $stmt = $this->findColumn([$theme_title]);
    
    if ($stmt == 1) {
       
       return true;
    
    } else {
       
       return false;
        
    }
    
  }

  /**
   * Load theme function
   * 
   * @method loadTheme()
   * @param string $theme_status
   * @return array
   * 
   */
  public function loadTheme($theme_status)
  {
    $sql = "SELECT ID, theme_directory, theme_status 
          FROM tbl_themes WHERE theme_status = :theme_status";
    
    $this->setSQL($sql);
    
    $activeTheme = $this->findRow([':theme_status' => $theme_status]);

    return (empty($activeTheme)) ?: $activeTheme;
    
  }
  
}