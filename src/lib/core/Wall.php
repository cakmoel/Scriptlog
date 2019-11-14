<?php
/**
 * Class Wall extends Dashboard
 * 
 * @package  SCRIPTLOG/LIB/CORE/Wall
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class Wall extends Dashboard
{

/**
 * list Items
 * 
 * @method listItems()
 * 
 */
 public function listItems()
 {
   if(true === is_non_administrator()) {

     $this->welcomeUser('Hello '.$_SESSION['user_fullname']);

   } else {

     $this->welcomeAdmin('Hello '.$_SESSION['user_login']);

   }

 }

/**
 * Detail item
 * 
 * @param integer $id
 * 
 */
 public function detailItem($id)
 {

 }

/**
 * Welcome Admin
 * 
 * @param string $pageTitle
 * 
 */
 public function welcomeAdmin($pageTitle)
 {
   $this->setView('home-admin');
   $this->setPageTitle($pageTitle);
   $this->view->set('pageTitle', $this->getPageTitle());
   return $this->view->render();
 }

/**
 * Welcome User
 * 
 * @param string $pageTitle
 * 
 */
 public function welcomeUser($pageTitle)
 {
   $this->setView('home-user');
   $this->setPageTitle($pageTitle);
   $this->view->set('pageTitle', $this->getPageTitle());
   return $this->view->render();
 }

}