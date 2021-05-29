<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class UserApp 
 *
 * @category  Class UserApp extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Egulias\EmailValidator\Validation\RFCValidation;

class UserApp extends BaseApp
{

  private $view;

  private $userEvent;

  public function __construct(UserEvent $userEvent)
  {
    $this->userEvent = $userEvent;       
  }
  
  public function listItems()
  {
   
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
     
    if (isset($_GET['error'])) {
        $checkError = false;
        if ($_GET['error'] == 'userNotFound') array_push($errors, "Error: User Not Found!");
        if ($_GET['error'] == 'adminDeletedNotified') array_push($errors, "Error: Administrator could not be deleted");
    }
    
    if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'userAdded') array_push($status, "New user added");
        if ($_GET['status'] == 'userUpdated') array_push($status, "User has been updated");
        if ($_GET['status'] == 'userDeleted') array_push($status, "User deleted");
    }
    
     
    $this->setView('all-users');
    
    if (!$checkError) {
       
        $this->view->set('errors', $errors);
    
    }
    
    if ($checkStatus) {
       
        $this->view->set('status', $status);
        
    }

    $this->setPageTitle('Users');
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('usersTotal', $this->userEvent->totalUsers());
    $this->view->set('users', $this->userEvent->grabUsers());

    return $this->view->render();
   
  }

  /**
   * Show individual user profile (except:administrator)
   * retrieve individual user profile based on their user login
   * 
   * @param integer $id
   * @param object $sanitize
   * 
   */
  public function showProfile($user_login)
  {
    $errors = array();
    $status = array();
    $checkError = true;
    $checkStatus = false;
     
    if (isset($_GET['error'])) {
        $checkError = false;
        if ($_GET['error'] == 'profileNotFound') array_push($errors, "Error: Profile Not Found!");
    }
    
    if (isset($_GET['status'])) {
        $checkStatus = true;
        if ($_GET['status'] == 'profilUpdated') array_push($status, "Profile has been updated");
    }
    
    if (!$getUser = $this->userEvent->grabUserByLogin($user_login)) {
        
        direct_page('index.php?load=404&notfound='.notfound_id(), 404);
         
    }

    $data_user = array(
        
        'ID' => $getUser['ID'],
        'user_login' => $getUser['user_login'],
        'user_email' => $getUser['user_email'],
        'user_level' => $getUser['user_level'],
        'user_fullname' => $getUser['user_fullname'],
        'user_url' => $getUser['user_url'],
        'user_registered' => $getUser['user_registered'],
        'user_session' => $getUser['user_session']
        
    );

    $this->setView('edit-myprofile');

    if (!$checkError) {
       
        $this->view->set('errors', $errors);
    
    }
    
    if ($checkStatus) {
       
        $this->view->set('status', $status);
        
    }
    
    $this->setPageTitle('Profile');
    $this->setFormAction(ActionConst::EDITUSER);
    $this->view->set('pageTitle', $this->getPageTitle());
    $this->view->set('formAction', $this->getFormAction());
    $this->view->set('userData', $data_user);
    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    return $this->view->render();

  }
  
  /**
   * Insert user
   * 
   * {@inheritDoc}
   * @see BaseApp::insert()
   * 
   */
  public function insert()
  {
   
    $errors = array();
    $checkError = true;
    
    if (isset($_POST['userFormSubmit'])) {
       
        $filters = ['user_login' => FILTER_SANITIZE_STRING, 'user_fullname' => FILTER_SANITIZE_STRING, 'user_email' => FILTER_SANITIZE_EMAIL, 
                    'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, 'user_url' => FILTER_SANITIZE_URL, 'user_level' => FILTER_SANITIZE_STRING, 
                    'session_id' => FILTER_SANITIZE_ENCODED, 'send_user_notification' => FILTER_SANITIZE_NUMBER_INT];

        try {
        
            if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
                
                header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
                throw new AppException("Sorry, unpleasant attempt detected!");
                
            }
            
            if (empty($_POST['user_login']) || empty($_POST['user_email']) || empty($_POST['user_pass'])) {
                
               $checkError = false;
               array_push($errors, "All columns required must be filled");
               
            }
            
            if ((isset($_POST['user_login'])) && (!preg_match('/^(?=.{8,20}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._]+(?<![_.])$/', $_POST['user_login']))) {
                
                $checkError = false;
                array_push($errors, "Username requires only alphanumerics characters, underscore and dot. Number of characters must be between 8 to 20");
                
            } elseif ($this->userEvent->checkUserLogin($_POST['user_login'])) {
                
                $checkError = false;
                array_push($errors, "Username already in use");
                
            }
            
            if ((isset($_POST['user_email'])) && (!email_validation($_POST['user_email'], new RFCValidation()))) {
                
                $checkError = false;
                array_push($errors, "Please enter a valid email address");
                
            } elseif (!email_multiple_validation($_POST['user_email'])) {

                $checkError = false;
                array_push($errors, "Unknown DNS records that signal the server accepts emails");
                
            } elseif ($this->userEvent->isEmailExists($_POST['user_email'])) {
                
                $checkError = false;
                array_push($errors, "Email already in use");
                
            }
            
            if (isset($_POST['user_pass'])) {

                if (check_common_password($_POST['user_pass']) === true) {

                    $checkError = false;
                    array_push($errors, "Your password seems to be the most hacked password, please try another");

                }

                if (false === check_pwd_strength($_POST['user_pass'])) {

                    $checkError = false;
                    array_push($errors, "Password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters");
                    
                }

            }

            if ((!empty($_POST['user_url'])) && (!url_validation($_POST['user_url']))) {
                
                $checkError = false;
                array_push($errors, "Please enter a valid URL.");
                
            }
            
            if (!empty($_POST['user_fullname'])) {
                
                if (!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname'])) {
                    
                    $checkError = false;
                    array_push($errors, "Please enter a valid fullname");
                    
                }
                
            }
            
            if (!$checkError) {
                
                $this->setView('edit-user');
                $this->setPageTitle('Add New User');
                $this->setFormAction(ActionConst::NEWUSER);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('formData', $_POST);
                $this->view->set('userRole', $this->userEvent->userLevelDropDown());
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
               
            } else {
            
                $this->userEvent->setUserLogin(prevent_injection(distill_post_request($filters)['user_login']));
                $this->userEvent->setUserEmail(distill_post_request($filters)['user_email']);
                $this->userEvent->setUserPass(prevent_injection(distill_post_request($filters)['user_pass']));
                $this->userEvent->setUserLevel(distill_post_request($filters)['user_level']);
                $this->userEvent->setUserFullname((isset($_POST['user_fullname']) ? purify_dirty_html(distill_post_request($filters)['user_fullname']) : ""));
                $this->userEvent->setUserUrl((isset($_POST['user_url']) ? escape_html(distill_post_request($filters)['user_url']) : ""));
                $this->userEvent->setUserSession(distill_post_request($filters)['session_id']);
                
                if ((isset($_POST['send_user_notification'])) && ($_POST['send_user_notification'] == 1)) {
                    
                    $this->userEvent->setUserActivationKey(user_activation_key(distill_post_request($filters)['user_email'].get_ip_address()));
                      
                    $this->userEvent->addUser();
                    
                    notify_new_user(distill_post_request($filters)['user_email'], prevent_injection(distill_post_request($filters)['user_pass']));
                    
                } else {
                
                    $this->userEvent->addUser();
                                        
                }
                
                direct_page('index.php?load=users&status=userAdded', 200);
                
            }
            
        } catch (Throwable $th) {

            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);

        } catch (AppException $e) {
            
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($e);
            
        }
        
    } else {
        
        $this->setView('edit-user');
        $this->setPageTitle('Add New User');
        $this->setFormAction(ActionConst::NEWUSER);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('userRole', $this->userEvent->userLevelDropDown());
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
    }
    
    return $this->view->render();
          
  }
  
  /**
   * Update user
   * 
   * {@inheritDoc}
   * @see BaseApp::update()
   * 
   */
  public function update($id)
  {
    
    $errors = array();
    $checkError = true;
    $secret = ScriptlogCryptonize::generateSecretKey();

    if (!$getUser = $this->userEvent->grabUser($id)) {
        
       direct_page('index.php?load=users&error=userNotFound', 404);
        
    }
    
    $data_user = array(
        
        'ID'            => (int)$getUser['ID'],
        'user_login'    => $getUser['user_login'],
        'user_email'    => $getUser['user_email'],
        'user_level'    => $getUser['user_level'],
        'user_fullname' => $getUser['user_fullname'],
        'user_url'      => $getUser['user_url'],
        'user_registered' => $getUser['user_registered'],
        'user_session'  => $getUser['user_session'],
        'user_banned'   => $getUser['user_banned']
        
    );
    
    if (isset($_POST['userFormSubmit'])) {
       
        $filters = ['user_fullname' => FILTER_SANITIZE_STRING, 'user_email' => FILTER_SANITIZE_EMAIL, 
                    'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, 
                    'user_pass2' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'current_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'user_url' => FILTER_SANITIZE_URL, 'user_level' => FILTER_SANITIZE_STRING, 
                    'user_id' => FILTER_SANITIZE_NUMBER_INT, 
                    'user_banned' => FILTER_SANITIZE_NUMBER_INT
                ];
        
    try {
      
        if (!csrf_check_token('csrfToken', $_POST, 60*10)) {
              
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Sorry, unpleasant attempt detected!");
              
        }

        if ((!empty($_POST['user_pass2'])) || (!empty($_POST['user_pass'])) || (!empty($_POST['current_pwd']))) {
 
            if (($_POST['user_pass']) !== ($_POST['user_pass2'])) {

                $checkError = false;
                array_push($errors, "Password should both be equal");

            } 
            
            if (check_common_password($_POST['user_pass']) === true) {

                $checkError = false;
                array_push($errors, "Your password seems to be the most hacked password, please try another.");

            }

            if (false === check_pwd_strength($_POST['user_pass'])) {

                $checkError = false;
                array_push($errors, "Password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters");

            }

            if (false === $this->userEvent->reAuthenticateUserPrivilege($getUser['user_login'], $_POST['current_pwd'])) {

                $checkError = false;
                array_push($errors, "re-authentication failed, please check your current password");

            }
            
        }

        if (!empty($_POST['user_fullname'])) {
            
            if (!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname'])) {
                
                $checkError = false;
                array_push($errors, "Please enter a valid fullname");
                
            }
            
        }

        if (!email_validation($_POST['user_email'], new RFCValidation())) {

            $checkError = false;
            array_push($errors, "Please enter a valid email address");

        } elseif (!email_multiple_validation($_POST['user_email'])) {

            $checkError = false;
            array_push($errors, "Unknown DNS records that signal the server accepts emails");
            
        }

        if ( ( !empty($_POST['user_url']) ) && ( !url_validation($_POST['user_url']) ) ) {
                 
            $checkError = false;
            array_push($errors, "Please enter a valid URL");
            
        }

        if (!$checkError) {
            
              $this->setView('edit-user');
              $this->setPageTitle('Edit User');
              $this->setFormAction(ActionConst::EDITUSER);
              $this->view->set('pageTitle', $this->getPageTitle());
              $this->view->set('formAction', $this->getFormAction());
              $this->view->set('errors', $errors);
              $this->view->set('userData', $data_user);
             
              if (($getUser['ID'] == 1) && ($this->userEvent->isUserLevel() == 'administrator')) {

                $this->view->set('userRole', $this->userEvent->isUserLevel());
    
              } else {
    
                $this->view->set('userRole', $this->userEvent->userLevelDropDown($getUser['user_level']));
    
              }
        
              $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

          } else {
              
              $this->userEvent->setUserEmail(distill_post_request($filters)['user_email']);
              $this->userEvent->setUserFullname((isset($_POST['user_fullname']) ? purify_dirty_html(distill_post_request($filters)['user_fullname']) : ""));
              $this->userEvent->setUserUrl((isset($_POST['user_url']) ? escape_html(distill_post_request($filters)['user_url']) : ""));
              $this->userEvent->setUserId((isset($_POST['user_id']) ? abs((int)distill_post_request($filters)['user_id']) : 0));
              $this->userEvent->setUserBanned((isset($_POST['user_banned']) ? abs((int)distill_post_request($filters)['user_banned']) : 0));

              if ((isset($_POST['user_id'])) && ($_POST['user_id'] == 1) && ($this->userEvent->isUserLevel() == 'administrator')) {

                  $this->userEvent->setUserLevel($getUser['user_level']);

              } else {

                  $this->userEvent->setUserLevel(distill_post_request($filters)['user_level']);

              }
              
              if (!empty($_POST['user_pass'])) {
                      
                  $this->userEvent->setUserPass(prevent_injection(distill_post_request($filters)['user_pass']));

              }

              if($this->userEvent->identifyCookieToken($secret) == true) {
 
                if(!empty($_POST['user_pass'])) {

                  $random_password = Tokenizer::createToken(128);
                  set_cookies_scl('scriptlog_validator', $random_password, time() + Authentication::COOKIE_EXPIRE, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
                  
                  $random_selector = Tokenizer::createToken(128);
                  set_cookies_scl('scriptlog_selector', $random_selector, time() + Authentication::COOKIE_EXPIRE, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
                  
                  $hashed_password = Tokenizer::setRandomPasswordProtected($random_password);
                  $hashed_selector = Tokenizer::setRandomSelectorProtected($random_selector, $secret);
              
                  $this->userEvent->setPwdHash($hashed_password);
                  $this->userEvent->setSelectorHash($hashed_selector);
                  $this->userEvent->setUserLogin($getUser['user_login']);

                  $expiry_date = date("Y-m-d H:i:s", time() + Authentication::COOKIE_EXPIRE);
                  $this->userEvent->setCookieExpireDate($expiry_date); 

                }
                
            }
 
              $this->userEvent->modifyUser();

              direct_page('index.php?load=users&status=userUpdated', 200);
                      
          }
          
      } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);  

      } catch (AppException $e) {
          
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
          
      }
      
    } else {
    
        $this->setView('edit-user');
        $this->setPageTitle('Edit User');
        $this->setFormAction(ActionConst::EDITUSER);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('userData', $data_user);

        if ($getUser['ID'] == 1 && $this->userEvent->isUserLevel() == 'administrator') {

            $this->view->set('userRole', $this->userEvent->isUserLevel());

        } else {

            $this->view->set('userRole', $this->userEvent->userLevelDropDown($getUser['user_level']));

        }
        
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        
    }
    
   return $this->view->render();
    
  }

/**
 * updateProfile 
 *
 * @param string $user_login
 * @return mixed
 * 
 */
  public function updateProfile($user_login)
  {

    $errors = array();
    $checkError = true;
    $secret = ScriptlogCryptonize::generateSecretKey();

    if(!$getProfile = $this->userEvent->grabUserByLogin($user_login)) {

        direct_page('index.php?load=404&notfound='.notfound_id(), 404);

    }

    $data_profile = array(
        
        'ID' => (int)$getProfile['ID'],
        'user_login' => $getProfile['user_login'],
        'user_email' => $getProfile['user_email'],
        'user_level' => $getProfile['user_level'],
        'user_fullname' => $getProfile['user_fullname'],
        'user_url' => $getProfile['user_url'],
        'user_registered' => $getProfile['user_registered'],
        'user_session' => $getProfile['user_session']
        
    );

    if(isset($_POST['userFormSubmit'])) {

        $filters = ['user_fullname' => FILTER_SANITIZE_STRING, 'user_email' => FILTER_SANITIZE_EMAIL, 
                    'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, 'user_pass2' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'current_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'user_url' => FILTER_SANITIZE_URL, 'user_id' => FILTER_SANITIZE_NUMBER_INT];

    try {
            
        if(!csrf_check_token('csrfToken', $_POST, 60*10)) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            throw new AppException("Sorry, unpleasant attempt detected!");

        }
            
        if ((!empty($_POST['user_pass2'])) || (!empty($_POST['user_pass'])) || (!empty($_POST['current_pwd']))) {

            if (($_POST['user_pass']) !== ($_POST['user_pass2'])) {

                $checkError = false;
                array_push($errors, "Password should both be equal");

            } 
                
            if (check_common_password($_POST['user_pass']) === true) {

                $checkError = false;
                array_push($errors, "Your password seems to be the most hacked password, please try another.");

            }

            if (false === check_pwd_strength($_POST['user_pass'])) {

                $checkError = false;
                array_push($errors, "Password requires at least 8 characters with lowercase, uppercase letters, numbers and special characters");

            }

            if (false === $this->userEvent->reAuthenticateUserPrivilege($getProfile['user_login'], $_POST['current_pwd'])) {

                $checkError = false;
                array_push($errors, "re-authentication failed, please check your current password");

            }

        }
    
        if (!email_validation($_POST['user_email'], new RFCValidation())) {

            $checkError = false;
            array_push($errors, "Please enter a valid email address");

        } elseif (!email_multiple_validation($_POST['user_email'])) {

            $checkError = false;
            array_push($errors, "Unknown DNS records that signal the server accepts emails");
                
        }

        if ((!empty($_POST['user_url'])) && (!url_validation($_POST['user_url']))) {
                 
            $checkError = false;
            array_push($errors, "Please enter a valid URL");
                
        }

        if(!empty($_POST['user_fullname'])) {
                
            if(!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname'])) {

                $checkError = false;
                array_push($errors, "Please enter a valid fullname");

            }

        }

        if (!$checkError) {

            $this->setView('edit-myprofile');
            $this->setPageTitle('Profile');
            $this->setFormAction(ActionConst::EDITUSER);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('errors', $errors);
            $this->view->set('userData', $data_profile);
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        } else {

            $this->userEvent->setUserEmail(distill_post_request($filters)['user_email']);
            $this->userEvent->setUserFullname((isset($_POST['user_fullname']) ? purify_dirty_html(distill_post_request($filters)['user_fullname']) : ""));
            $this->userEvent->setUserUrl((isset($_POST['user_url']) ? escape_html(distill_post_request($filters)['user_url'], 'url') : ""));
            $this->userEvent->setUserId((isset($_POST['user_id']) ? abs((int)distill_post_request($filters)['user_id']) : 0));

            if(!empty($_POST['user_pass'])) {

                $this->userEvent->setUserPass(purify_dirty_html(distill_post_request($filters)['user_pass']));

            }

            if ($this->userEvent->identifyCookieToken($secret) == true) {

                if(!empty($_POST['user_pass'])) {

                    $random_password = Tokenizer::createToken(128);
                    set_cookies_scl('scriptlog_validator', $random_password, time() + Authentication::COOKIE_EXPIRE, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
                
                    $random_selector = Tokenizer::createToken(128);
                    set_cookies_scl('scriptlog_selector', $random_selector, time() + Authentication::COOKIE_EXPIRE, Authentication::COOKIE_PATH, domain_name(), is_cookies_secured(), true);
                
                    $hashed_password = Tokenizer::setRandomPasswordProtected($random_password);
                    $hashed_selector = Tokenizer::setRandomSelectorProtected($random_selector, $secret);
                
                    $this->userEvent->setPwdHash($hashed_password);
                    $this->userEvent->setSelectorHash($hashed_selector);
                    $this->userEvent->setUserLogin($getProfile['user_login']);

                    $expiry_date = date("Y-m-d H:i:s", time() + Authentication::COOKIE_EXPIRE);
                    $this->userEvent->setCookieExpireDate($expiry_date);

                }
              
            }

            $this->userEvent->modifyUser();

            direct_page('index.php?load=users&status=userUpdated', 200);

        }
            
    } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
            
    } catch (AppException $e) {
            
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
        
    }

    } else {

        $this->setView('edit-myprofile');
        $this->setPageTitle('Profile');
        $this->setFormAction(ActionConst::EDITUSER);
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('userData', $data_profile);
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

    }

    return $this->view->render();
    
  }

  /**
   * 
   * {@inheritDoc}
   * @see BaseApp::delete()
   * 
   */
  public function remove($id)
  {

    $checkError = true;
    $errors = array();

    if (isset($_GET['Id'])) {

        $getUser = $this->userEvent->grabUser($id);
        
     try {
        
        if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
            throw new AppException("Sorry, unpleasant attempt detected!");

        }
        
        if (!filter_var($id, FILTER_VALIDATE_INT)) {

            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400);
            throw new AppException("Sorry, unpleasant attempt detected!");

        }

        if (($getUser['ID'] == 1) && ($getUser['user_level'] == 'administrator')) {

            $checkError = false;
            array_push($errors, "Error: administrator could not be deleted");
           
        } 

        if (!$checkError) {

            $this->setView('all-users');
            $this->setPageTitle('User Not Found');
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('errors', $errors);
            $this->view->set('usersTotal', $this->userEvent->totalUsers());
            $this->view->set('users', $this->userEvent->grabUsers());
            return $this->view->render();

        } else {

           $this->userEvent->setUserId($id);
           $this->userEvent->removeUser();
           direct_page('index.php?load=users&status=userDeleted', 200);

        }

     } catch (Throwable $th) {

        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);

     } catch (AppException $e) {
        
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
         
     }

    } 

  }
  
  protected function setView($viewName)
  {
     $this->view = new View('admin', 'ui', 'users', $viewName);
  }
  
}