<?php
/**
 * class AccessControl
 * 
 * @category Core class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
final class AccessControl
{
/**
 * privilege
 *
 * @property bool $privilege
 * 
 */
private static $privilege = true;

/**
 * userActionPrivilege
 *
 * @method static userActionPrivilege()
 * @param string $control
 * @return bool
 */
private static function userActionPrivilege($control)
{
 return ( $control === ActionConst::USERS && user_privilege() !== 'administrator' ) ? false : "";
}

/**
 * pluginActionPrivilege
 *
 * @method static pluginActionPrivilege()
 * @param string $control
 * @return bool
 */
private static function pluginActionPrivilege($control)
{
  return ( $control === ActionConst::PLUGINS && user_privilege() !== 'administrator' && user_privilege() !== 'manager' ) ? false : "";
}

/**
 * themeActionPrivilege
 *
 * @method static themeActionPrivilege()
 * @param string $control
 * @return bool
 */
private static function themeActionPrivilege($control)
{
  return ( $control === ActionConst::THEMES && user_privilege() !== 'administrator' && user_privilege() !== 'manager') ? false : "";
}

/**
 * configActionPrivilege
 *
 * @method static configActionPrivilege()
 * @param string $control
 * @return bool
 */
private static function configActionPrivilege($control)
{
  return ( $control === ActionConst::CONFIGURATION && user_privilege() !== 'administrator' && user_privilege() !== 'manager') ? false : "";
}

/**
 * pageActionPrivilege
 *
 * @param string $control
 * @return bool
 */
private static function pageActionPrivilege($control)
{
  return ( $control === ActionConst::PAGES && user_privilege() !== 'administrator' && user_privilege() !== 'manager' ) ? false : "";
}

private static function navigationActionPrivilege($control)
{
  return ( $control === ActionConst::NAVIGATION && user_privilege() !== 'administrator' && user_privilege() !== 'manager' ) ? false : "";
}

private static function mediaActionPrivilege($control)
{
  return ( $control === ActionConst::MEDIALIB && user_privilege() !== 'administrator' && user_privilege() !== 'manager' && user_privilege() !== 'editor' && user_privilege() !== 'author' ) ? false : "";
}

private static function topicActionPrivilege($control) 
{
  return ( $control === ActionConst::TOPICS && user_privilege() !== 'administrator' && user_privilege() !== 'manager' && user_privilege() !== 'editor' ) ? false : "";
}

private static function commentActionPrivilege($control) 
{
 return ( $control === ActionConst::COMMENTS && user_privilege() !== 'administrator' && user_privilege() !== 'author' && user_privilege() !== 'contributor' ) ? false : "";
}

private static function replyActionPrivilege($control)
{
  return ( $control === ActionConst::REPLY && user_privilege() !== 'administrator' && user_privilege() !== 'editor' && user_privilege() !== 'author' ) ? false : "";
}

private static function defaultActionPrivilege() 
{
 return ( user_privilege() !== 'administrator' && user_privilege() !== 'manager' && user_privilege() !== 'editor' && user_privilege() !== 'author' && user_privilege() !== 'contributor' ) ? false : "";
}

/**
 * accessPrivilege
 *
 * @param string $control
 * @return bool|true|false
 */
public static function accessPrivilege($control = null)
{

  if ( self::userActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::pluginActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::themeActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::pageActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::configActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::navigationActionPrivilege($control) )  {

    self::$privilege = false;

  } elseif ( self::mediaActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::topicActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::commentActionPrivilege($control) ) {

    self::$privilege = false;

  } elseif ( self::replyActionPrivilege($control) ) {

    self::$privilege = false;
    
  } else {

     self::defaultActionPrivilege();
  }

  return self::$privilege;

}

}