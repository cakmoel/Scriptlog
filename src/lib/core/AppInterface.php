<?php
/**
 * AppInterface Interface
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
interface AppInterface
{

  /**
   * set page title
   * 
   * @param string $pageTitle
   * 
   */
  public function setPageTitle($pageTitle);

  /**
   * get page title
   */
  public function getPageTitle();

  /**
   * set form action
   * 
   * @param string $formAction
   * 
   */
  public function setFormAction($formAction);

  /**
   * get form action
   * 
   */
  public function getFormAction();
    
}