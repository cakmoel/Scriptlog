<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Abstract class FrontProvider
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
abstract class FrontProviderController implements FrontProviderInterface
{
/**
 * frontTitle
 *
 * @var string
 * 
 */
private $frontTitle;

/**
 * content
 *
 * @var object
 * 
 */
protected $content;

/**
 * setFrontTitle()
 *
 * @param string $frontTitle
 * @return string
 * 
 */
public function setFrontTitle($frontTitle)
{
  $this->frontTitle = $frontTitle;
}

/**
 * getFrontTitle
 *
 * @return string
 * 
 */
public function getFrontTitle()
{
  return $this->frontTitle;
}

/**
 * renderFrontView
 * 
 * rendering content within view
 * 
 * @param string $eventPath
 * @param string $uiPath
 * @param string $modulePath
 * @param string $file
 * @return object
 * 
 */
public function renderFrontView($eventPath, $uiPath, $modulePath = null, $file = null)
{
  $this->content = new View($eventPath, $uiPath, $modulePath, $file);
}

abstract protected function getItems();

abstract protected function getItemById($id);

abstract protected function getItemBySlug($slug);

}