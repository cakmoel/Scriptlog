<?php 
/**
 * Abstract Class BaseApp implements AppInterface
 *
 * @package   SCRIPTLOG/LIB/CORE/BaseApp
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
abstract class BaseApp implements AppInterface
{
  /**
   * Page title
   * 
   * @var string
   */
  protected $pageTitle;

  /**
   * Form action
   * 
   * @var string
   * 
   */
  protected $formAction;
  
  /**
   * set page title
   * 
   * @param string $pageTitle
   * 
   */
  public function setPageTitle($pageTitle)
  {
    $this->pageTitle = $pageTitle; 
  }

  /**
   * get page title
   * 
   * @return string
   * 
   */
  public function getPageTitle()
  {
    return $this->pageTitle;
  }
  
  /**
   * set form action
   * 
   * @param string $formAction
   * 
   */
  public function setFormAction($formAction)
  {
    $this->formAction = $formAction;
  }
  
  /**
   * get form action
   * 
   * @return string
   * 
   */
  public function getFormAction()
  {
    return $this->formAction;
  }
  
  /**
   * abstract method list items
   * 
   * @abstract @method protected  listItems()
   * 
   */
  abstract protected function listItems();
  
  /**
   * abstract method insert
   * 
   * @abstract @method protected insert() 
   * 
   */
  abstract protected function insert();
 
/**
 * Abstract method update
 * 
 * @abstract @method protected update()
 * @param integer $id
 * 
 */
  abstract protected function update($id);

/**
 * Abstract method remove
 * 
 * @abstract @method protected remove()
 * @param integer $id
 * 
 */
  abstract protected function remove($id);
  
} 