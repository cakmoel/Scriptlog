<?php
/**
 * The collection of actions constant 
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class ActionConst
{

/**
 * action constant for configuration functionality
 */
 const CONFIGURATION = "configuration";
 const NEWCONFIG     = "newConfig";
 const EDITCONFIG    = "editConfig";
 const DELETECONFIG  = "deleteConfig"; 

/**
 * action constant for post functionality
 * 
 */
 const NEWPOST     = "newPost";
 const EDITPOST    = "editPost";
 const DELETEPOST  = "deletePost";
 
/**
 * action constant for page functionality
 */
 const PAGES       = "pages";
 const NEWPAGE     = "newPage";
 const EDITPAGE    = "editPage";
 const DELETEPAGE  = "deletePage";

/**
 * action constant for topic functionality
 */
 const NEWTOPIC    = "newTopic";
 const EDITTOPIC   = "editTopic";
 const DELETETOPIC = "deleteTopic";

/**
 * action constant for comment functionality
 */
 const EDITCOMMENT   = "editComment";
 const DELETECOMMENT = "deleteComment";

/**
 * action constant for menu functionality
 */
 const NEWMENU     = "newMenu";
 const EDITMENU    = "editMenu";
 const DELETEMENU  = "deleteMenu";

 const NEWSUBMENU    = "newSubMenu";
 const EDITSUBMENU   = "editSubMenu";
 const DELETESUBMENU = "deleteSubMenu";

/**
 * action constant for media functionality
 */
 const MEDIALIB      = "medialib";
 const NEWMEDIA      = "newMedia";
 const EDITMEDIA     = "editMedia";
 const DELETEMEDIA   = "deleteMedia";

/**
 * action constant for plugin functionality
 */
 const PLUGINS          = "plugins";
 const INSTALLPLUGIN    = "installPlugin";
 const ACTIVATEPLUGIN   = "activatePlugin";
 const DEACTIVATEPLUGIN = "deactivatePlugin";
 const NEWPLUGIN        = "newPlugin";
 const EDITPLUGIN       = "editPlugin";
 const DELETEPLUGIN     = "deletePlugin";

/**
 * action constant for theme functionality
 */
 const THEMES        = "themes";
 const NEWTHEME      = "newTheme";
 const INSTALLTHEME  = "installTheme";
 const ACTIVATETHEME = "activateTheme";
 const EDITTHEME     = "editTheme";
 const DELETETHEME   = "deleteTheme";

/**
 * action const for user functionality
 */
 const NEWUSER    = "newUser";
 const EDITUSER   = "editUser";
 const DELETEUSER = "deleteUser";

}