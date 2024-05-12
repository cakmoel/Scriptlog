<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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

 // action constand for dashboard
 const DASHBOARD  = "dashboard";
 const DETAILITEM = "detailItem";

 // action constant for configuration functionality
 const CONFIGURATION     = "configuration";
 const GENERAL_CONFIG    = "generalConfig";
 const PERMALINK_CONFIG  = "permalinkConfig";
 const READING_CONFIG    = "readingConfig";
 const TIMEZONE_CONFIG   = "timezoneConfig";
 const MEMBERSHIP_CONFIG = "membershipConfig";

 // action constant for post functionality 
 const POSTS       = "posts";
 const NEWPOST     = "newPost";
 const EDITPOST    = "editPost";
 const DELETEPOST  = "deletePost";
 
 // action constant for page functionality
 const PAGES       = "pages";
 const NEWPAGE     = "newPage";
 const EDITPAGE    = "editPage";
 const DELETEPAGE  = "deletePage";

 // action constant for topic functionality 
 const TOPICS      = "topics";
 const NEWTOPIC    = "newTopic";
 const EDITTOPIC   = "editTopic";
 const DELETETOPIC = "deleteTopic";

// action constant for comment functionality
 const COMMENTS      = "comments";
 const EDITCOMMENT   = "editComment";
 const DELETECOMMENT = "deleteComment";

// action constant for reply functionality
 const REPLY         = "reply";
 const RESPONSETO    = "responseTo";
 const DELETEREPLY   = "deleteReply";

// action constant for navigation or menu functionality
 const NAVIGATION  = "navigation";
 const NEWMENU     = "newMenu";
 const EDITMENU    = "editMenu";
 const DELETEMENU  = "deleteMenu";
 
 const NEWSUBMENU    = "newSubMenu";
 const EDITSUBMENU   = "editSubMenu";
 const DELETESUBMENU = "deleteSubMenu";

 // action constant for media functionality
 const MEDIALIB      = "medialib";
 const NEWMEDIA      = "newMedia";
 const EDITMEDIA     = "editMedia";
 const DELETEMEDIA   = "deleteMedia";

// action constant for plugin functionality
 const PLUGINS          = "plugins";
 const INSTALLPLUGIN    = "installPlugin";
 const ACTIVATEPLUGIN   = "activatePlugin";
 const DEACTIVATEPLUGIN = "deactivatePlugin";
 const DELETEPLUGIN     = "deletePlugin";

// action constant for theme functionality
 const THEMES        = "themes";
 const NEWTHEME      = "newTheme";
 const INSTALLTHEME  = "installTheme";
 const ACTIVATETHEME = "activateTheme";
 const EDITTHEME     = "editTheme";
 const DELETETHEME   = "deleteTheme";

// action const for user functionality
 const USERS      = "users";
 const NEWUSER    = "newUser";
 const EDITUSER   = "editUser";
 const DELETEUSER = "deleteUser";
 
// action const for logging out from admin panel
 const LOGOUT = "doLogOut";

}