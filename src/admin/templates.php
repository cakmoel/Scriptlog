<?php if (!defined('SCRIPTLOG')) die("Direct Access Not Allowed!");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$themeId = isset($_GET['themeId']) ? abs((int)$_GET['themeId']) : 0;
$themeDao = new ThemeDao();
$validator = new FormValidator();
$themeEvent = new ThemeEvent($themeDao, $validator, $sanitizer);
$themeApp = new ThemeApp($themeEvent);

switch ($action) {

    case ActionConst::NEWTHEME:
        
        if ($themeId == 0) {
            
            $themeApp -> insert();

        } 

        break;

    case ActionConst::INSTALLTHEME:
        
        if ($themeId == 0) {

            $themeApp -> setupTheme();

        } 

        break;

    case ActionConst::EDITTHEME:

        if ($themeDao -> checkThemeId($themeId, $sanitizer)) {
            
            $themeApp -> update($themeId);

        } else {

            direct_page('index.php?load=templates&error=themeNotFound', 404);

        }
    
        break;

    case ActionConst::DELETETHEME:

       $themeApp -> remove($themeId);

    default:

        # show list of all themes
        $themeApp -> listItems();
        break;

}
