<?php
/**
 * Class Wall extends Dashboard
 * 
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

     if (isset($_SESSION['user_fullname'])) {

        $non_admin = $_SESSION['user_fullname'];

     }

     if (isset($_COOKIE['cookie_user_fullname'])) {

        $non_admin = $_COOKIE['cookie_user_fullname'];

     }

     $this->welcomeUser('Hello '.$non_admin);

   } else {

     if (isset($_SESSION['user_login'])) {

         $administrator = $_SESSION['user_login'];

     } 

     if (isset($_COOKIE['cookie_user_login'])) {

        $administrator = $_COOKIE['cookie_user_login'];

     }

     $this->welcomeAdmin('Hello '.$administrator);

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