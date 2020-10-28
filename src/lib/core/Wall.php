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
 public function listItems($authenticator, $user_login)
 {
  
  if(is_non_administrator() === true) {

    $non_admin = isset($_COOKIE['scriptlog_auth']) ? user_info($authenticator, $user_login)['user_fullname'] : Session::getInstance()->scriptlog_session_fullname;
    
    $this->welcomeUser('Hello '.safe_html($non_admin));

  } else {

    $administrator = isset($_COOKIE['scriptlog_auth']) ? user_info($authenticator, $user_login)['user_login'] : Session::getInstance()->scriptlog_session_login;

    $this->welcomeAdmin('Hello '.safe_html($administrator));

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