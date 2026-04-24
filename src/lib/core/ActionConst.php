<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
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
    public const DASHBOARD  = "dashboard";
    public const DETAILITEM = "detailItem";

    // action constant for configuration functionality
    public const CONFIGURATION     = "configuration";
    public const GENERAL_CONFIG    = "generalConfig";
    public const PERMALINK_CONFIG  = "permalinkConfig";
    public const READING_CONFIG    = "readingConfig";
    public const TIMEZONE_CONFIG   = "timezoneConfig";
    public const MEMBERSHIP_CONFIG = "membershipConfig";
    public const MAIL_CONFIG       = "mailConfig";
    // action constant for post functionality
    public const POSTS       = "posts";
    public const NEWPOST     = "newPost";
    public const EDITPOST    = "editPost";
    public const DELETEPOST  = "deletePost";

    // action constant for page functionality
    public const PAGES       = "pages";
    public const NEWPAGE     = "newPage";
    public const EDITPAGE    = "editPage";
    public const DELETEPAGE  = "deletePage";

    // action constant for topic functionality
    public const TOPICS      = "topics";
    public const NEWTOPIC    = "newTopic";
    public const EDITTOPIC   = "editTopic";
    public const DELETETOPIC = "deleteTopic";

    // action constant for comment functionality
    public const COMMENTS      = "comments";
    public const EDITCOMMENT   = "editComment";
    public const DELETECOMMENT = "deleteComment";

    // action constant for reply functionality
    public const REPLY         = "reply";
    public const EDITREPLY     = "editReply";
    public const DELETEREPLY   = "deleteReply";

    // action constant for navigation or menu functionality
    public const NAVIGATION  = "navigation";
    public const NEWMENU     = "newMenu";
    public const EDITMENU    = "editMenu";
    public const DELETEMENU  = "deleteMenu";

    public const NEWSUBMENU    = "newSubMenu";
    public const EDITSUBMENU   = "editSubMenu";
    public const DELETESUBMENU = "deleteSubMenu";

    // action constant for media functionality
    public const MEDIALIB      = "medialib";
    public const NEWMEDIA      = "newMedia";
    public const EDITMEDIA     = "editMedia";
    public const DELETEMEDIA   = "deleteMedia";

    // action constant for plugin functionality
    public const PLUGINS          = "plugins";
    public const INSTALLPLUGIN    = "installPlugin";
    public const ACTIVATEPLUGIN   = "activatePlugin";
    public const DEACTIVATEPLUGIN = "deactivatePlugin";
    public const DELETEPLUGIN     = "deletePlugin";

    // action constant for theme functionality
    public const THEMES        = "themes";
    public const NEWTHEME      = "newTheme";
    public const INSTALLTHEME  = "installTheme";
    public const ACTIVATETHEME = "activateTheme";
    public const DEACTIVATETHEME = "deactivateTheme";
    public const EDITTHEME     = "editTheme";
    public const DELETETHEME   = "deleteTheme";

    // action const for user functionality
    public const USERS      = "users";
    public const NEWUSER    = "newUser";
    public const EDITUSER   = "editUser";
    public const DELETEUSER = "deleteUser";

    // action const for logging out from admin panel
    public const LOGOUT = "doLogOut";

    // action const for import functionality
    public const IMPORT = "import";

    // action constant for privacy functionality
    public const PRIVACY = "privacy";
    public const DATA_REQUESTS = "dataRequests";
    public const AUDIT_LOGS = "auditLogs";

    // action constant for i18n functionality
    public const LANGUAGES     = "languages";
    public const NEWLANGUAGE   = "newLanguage";
    public const EDITLANGUAGE  = "editLanguage";
    public const DELETELANGUAGE = "deleteLanguage";
    public const TRANSLATIONS   = "translations";
    public const NEWTRANSLATION = "newTranslation";
    public const EDITTRANSLATION = "editTranslation";
    public const DELETETRANSLATION = "deleteTranslation";

    // action constant for language settings
    public const LANGUAGE_CONFIG = "languageConfig";

    // action constant for download functionality
    public const DOWNLOADS = "downloads";
    public const DOWNLOAD_CONFIG = "downloadConfig";
    public const DELETEDOWNLOAD = "deleteDownload";

    // action constant for API settings
    public const API_CONFIG = "apiConfig";
}
