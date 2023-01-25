<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * BoardInterface Interface
 * giving dashboard application interface to set page title and handle dashboard's view.
 * This class implemented to Dashboard Abstract Class 
 * 
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 * 
 */
interface BoardInterface
{

/**
 * set View
 * 
 * @param string $view
 */
 public function setView($view);

/**
 * set page title
 * 
 * @param string $pageTitle
 * 
 */
 public function setPageTitle($pageTitle);

/**
 * get page title
 * 
 */
 public function getPageTitle();

}