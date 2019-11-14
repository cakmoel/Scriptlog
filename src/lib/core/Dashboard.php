<?php
/**
 * Abstract Class Dashboard implement BoardInterface
 * 
 * @package  SCRIPTLOG/LIB/CORE/Dashboard
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
abstract class Dashboard implements BoardInterface
{

/**
 * View
 * 
 * @var string
 * 
 */
 protected $view;

/**
 * Page title
 * 
 * @var string
 * 
 */
 protected $pageTitle;

/**
 * set view
 * Initialize an instance of Class View
 * 
 * @method setView()
 * @param string $viewName
 * 
 */
 public function setView($viewName)
 {
   $this->view = new View('admin', 'ui', 'dashboard', $viewName);
 }

/**
 * Set page title
 * 
 * @param string $pageTitle
 * 
 */
 public function setPageTitle($pageTitle)
 {
   $this->pageTitle = $pageTitle;
 }

 public function getPageTitle()
 {
   return $this->pageTitle;
 }

 abstract protected function listItems();

 abstract protected function detailItem($id);

}