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

    /**
     * an instance of view
     *
     * @var object
     * 
     */
    private $view;

    /**
     * an instance of userEvent
     *
     * @var object
     * 
     */
    private $userEvent;

    public function __construct(UserEvent $userEvent)
    {
        $this->userEvent = $userEvent;
    }

    /**
     * listItems()
     * 
     * retrieves all of users record and display it
     * 
     * {@inheritDoc}
     * @uses BaseApp::listItems() BaseApp::listItems
     * @return mixed
     * 
     */
    public function listItems()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_SESSION['status'])) {

            $checkStatus = true;
            ($_SESSION['status'] == 'userAdded') ? array_push($status, "New user added") : "";
            ($_SESSION['status'] == 'userUpdated') ? array_push($status, "User has been updated") : "";
            ($_SESSION['status'] == 'userDeleted') ? array_push($status, "User deleted") : "";
            unset($_SESSION['status']);
        }

        if (isset($_SESSION['error'])) {

            $checkError = false;
            ($_SESSION['error'] == 'userNotFound') ? array_push($errors, "Error: User Not Found") : "";
            ($_SESSION['error'] == 'adminDeletedNotified') ? array_push($errors, "Error: Administrator could not be deleted") : "";
            unset($_SESSION['error']);
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
     * showProfile()
     * 
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

        if (isset($_SESSION['error'])) {

            $checkError = false;
            ($_SESSION['error'] == 'profileNotFound') ? array_push($errors, "Error: Profile Not Found!") : "";
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['status'])) {

            $checkStatus = true;
            ($_SESSION['status'] == 'profileUpdated') ? array_push($status, "Profile has been updated") : "";
            unset($_SESSION['status']);
        }

        if (!$getUser = $this->userEvent->grabUserByLogin($user_login)) {

            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
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

            $filters = [
                'user_login' => isset($_POST['user_login']) ? Sanitize::severeSanitizer($_POST['user_login']) : "",
                'user_fullname' => isset($_POST['user_fullname']) ? Sanitize::severeSanitizer($_POST['user_fullname']) : "",
                'user_email' => FILTER_SANITIZE_EMAIL,
                'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'user_url' => FILTER_SANITIZE_URL,
                'user_level' => isset($_POST['user_level']) ? Sanitize::mildSanitizer($_POST['user_level']) : "",
                'session_id' => FILTER_SANITIZE_ENCODED,
                'send_user_notification' => FILTER_SANITIZE_NUMBER_INT
            ];

            try {

                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
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
                    array_push($errors, MESSAGE_INVALID_EMAILADDRESS);
                } elseif ((checking_internet_connection()) && (!email_multiple_validation($_POST['user_email']))) {

                    $checkError = false;
                    array_push($errors, MESSAGE_UNKNOWN_DNS);
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
                        array_push($errors, MESSAGE_WEAK_PASSWORD);
                    }
                }

                if ((!empty($_POST['user_url'])) && (!url_validation($_POST['user_url']))) {

                    $checkError = false;
                    array_push($errors, "Please enter a valid URL.");
                }

                if ((!empty($_POST['user_fullname'])) && (!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname']))) {

                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_FULLNAME);
                }

                if (sanitize_selection_box(distill_post_request($filters)['user_level'], ['manager' => 'Manager', 'editor' => 'Editor', 'author' => 'Author', 'contributor' => 'Contributor', 'subscriber' => 'Subscriber']) === false) {

                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_SELECTBOX);
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

                        $this->userEvent->setUserActivationKey(user_activation_key(distill_post_request($filters)['user_email'] . get_ip_address()));
                        $this->userEvent->addUser();
                        notify_new_user(distill_post_request($filters)['user_email'], prevent_injection(distill_post_request($filters)['user_pass']));

                    } else {

                        $this->userEvent->addUser();
                    }

                    $_SESSION['status'] = "userAdded";
                    direct_page('index.php?load=users&status=userAdded', 302);
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
     * Update
     * 
     * Updating user record by administrator
     * 
     * {@inheritDoc}
     * @see BaseApp::update()
     * 
     */
    public function update($id)
    {

        $errors = array();
        $checkError = true;
        $secret = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::generateSecretKey() : '';

        if (!$getUser = $this->userEvent->grabUser($id)) {

            $_SESSION['error'] = "userNotFound";
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

            $filters = [
                'user_fullname' => isset($_POST['user_fullname']) ? Sanitize::severeSanitizer($_POST['user_fullname']) : "",
                'user_email' => FILTER_SANITIZE_EMAIL,
                'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'user_pass2' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'current_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'user_url' => FILTER_SANITIZE_URL,
                'user_level' => isset($_POST['user_level']) ? Sanitize::mildSanitizer($_POST['user_level']) : "",
                'user_id' => FILTER_SANITIZE_NUMBER_INT,
                'user_banned' => FILTER_SANITIZE_NUMBER_INT
            ];

            try {

                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if ((!empty($_POST['user_pass2'])) || (!empty($_POST['user_pass'])) || (!empty($_POST['current_pwd']))) {

                    if (($_POST['user_pass']) !== ($_POST['user_pass2'])) {

                        $checkError = false;
                        array_push($errors, "Password should both be equal");
                    }

                    if (check_common_password($_POST['user_pass']) === true) {

                        $checkError = false;
                        array_push($errors, "Your password seems to be the most hacked password, please try another");
                    }

                    if (false === check_pwd_strength($_POST['user_pass'])) {

                        $checkError = false;
                        array_push($errors, MESSAGE_WEAK_PASSWORD);
                    }

                    if (false === $this->userEvent->reAuthenticateUserPrivilege($getUser['user_login'], $_POST['current_pwd'])) {

                        $checkError = false;
                        array_push($errors, "re-authentication failed, please check your current password");
                    }
                }

                if ((!empty($_POST['user_fullname'])) && (!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname']))) {

                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_FULLNAME);
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['user_level'], ['manager' => 'Manager', 'editor' => 'Editor', 'author' => 'Author', 'contributor' => 'Contributor'])) {

                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_SELECTBOX);
                }

                if (false === checking_internet_connection()) {

                    $checkError = false;
                    array_push($errors, "Please, check your internet connection");
                    
                } else {

                    if (!email_validation($_POST['user_email'], new RFCValidation())) {

                        $checkError = false;
                        array_push($errors, MESSAGE_INVALID_EMAILADDRESS);
                    } elseif (!email_multiple_validation($_POST['user_email'])) {

                        $checkError = false;
                        array_push($errors, MESSAGE_UNKNOWN_DNS);
                    }
                }

                if ((!empty($_POST['user_url'])) && (!url_validation($_POST['user_url']))) {

                    $checkError = false;
                    array_push($errors, "Please enter a valid URL Website");
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

                    if (($this->userEvent->identifyCookieToken($secret)) && (!empty($_POST['user_pass']))) {

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

                    $this->userEvent->modifyUser();
                    $_SESSION['status'] = "userUpdated";
                    direct_page('index.php?load=users&status=userUpdated', 302);
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

            if (($getUser['ID'] == 1)  && ($this->userEvent->isUserLevel() == 'administrator')) {

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

        if (!$getProfile = $this->userEvent->grabUserByLogin($user_login)) {

            $_SESSION['error'] = "profileNotFound";
            direct_page('index.php?load=users&error=profileNotFound', 404);
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

        if (isset($_POST['userFormSubmit'])) {

            $filters = [
                'user_fullname' => isset($_POST['user_fullname']) ? Sanitize::severeSanitizer($_POST['user_fullname']) : "",
                'user_email' => FILTER_SANITIZE_EMAIL,
                'user_pass' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'user_pass2' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'current_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'user_url' => FILTER_SANITIZE_URL,
                'user_id' => FILTER_SANITIZE_NUMBER_INT
            ];

            try {

                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
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
                        array_push($errors, MESSAGE_WEAK_PASSWORD);
                    }

                    if (false === $this->userEvent->reAuthenticateUserPrivilege($getProfile['user_login'], $_POST['current_pwd'])) {

                        $checkError = false;
                        array_push($errors, "re-authentication failed, please check your current password");
                    }
                }

                if (false === checking_internet_connection()) {

                    $checkError = false;
                    array_push($errors, "Please, check your internet connection");
                } else {

                    if (!email_validation($_POST['user_email'], new RFCValidation())) {

                        $checkError = false;
                        array_push($errors, MESSAGE_INVALID_EMAILADDRESS);
                    } elseif (!email_multiple_validation($_POST['user_email'])) {

                        $checkError = false;
                        array_push($errors, MESSAGE_UNKNOWN_DNS);
                    }
                }

                if ((!empty($_POST['user_url'])) && (!url_validation($_POST['user_url']))) {

                    $checkError = false;
                    array_push($errors, "Please enter a valid URL");
                }

                if ((!empty($_POST['user_fullname'])) && (!preg_match('/^[A-Z \'.-]{2,90}$/i', $_POST['user_fullname']))) {

                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_FULLNAME);
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

                    if (!empty($_POST['user_pass'])) {

                        $this->userEvent->setUserPass(purify_dirty_html(distill_post_request($filters)['user_pass']));
                    }

                    if (($this->userEvent->identifyCookieToken($secret)) && (!empty($_POST['user_pass']))) {

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

                    $this->userEvent->modifyUser();
                    $_SESSION['status'] = "profileUpdated";
                    direct_page('index.php?load=users&status=profileUpdated', 200);
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
     * remove
     * 
     * {@inheritDoc}
     * @see BaseApp::delete()
     * 
     */
    public function remove($userId)
    {

        $checkError = true;
        $errors = array();
    
        if (! $getUser = $this->userEvent->grabUser($userId)) {

            $_SESSION['error'] = "userNotFound";
            direct_page('index.php?load=users&error=userNotFound', 404);

        }

        $sanitizeID = sanitizer($userId, 'sql');

        if (isset($_GET['Id'])) {

            try {

                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if (!filter_var($userId, FILTER_VALIDATE_INT)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if (($getUser['ID'] == 1) && ($getUser['user_level'] == 'administrator') && ($this->userEvent->isUserLevel() == 'administrator')) {

                    $checkError = false;
                    $_SESSION['error'] = 'adminDeletedNotified';
                    direct_page("index.php?load=users&error=adminDeletedNotified");
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

                    $this->userEvent->setUserId($sanitizeID);
                    $this->userEvent->removeUser();
                    $_SESSION['status'] = "userDeleted";
                    direct_page('index.php?load=users&status=userDeleted', 302);
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

    /**
     * removeProfile
     *
     * @param string $user_login
     * @param object $authenticator
     * 
     */
    public function removeProfile($user_login, $authenticator)
    {

        $errors = array();
        $checkError = true;
    
        if (!$getProfile = $this->userEvent->grabUserByLogin($user_login)) {

            $_SESSION['error'] = "profileNotFound";
            direct_page('index.php?load=users&error=profileNotFound', 404);
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

        if (isset($_POST['userFormSubmit'])) {

            $filters = [
                'user_id' =>  FILTER_SANITIZE_NUMBER_INT,
                'current_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'confirm_pwd' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
            ];

            try {

                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {

                    header($_SERVER["SERVER_PROTOCOL"] . MESSAGE_BADREQUEST, true, 400);
                    throw new AppException(MESSAGE_UNPLEASANT_ATTEMPT);
                }

                if (isset($_POST['user_id']) && ($_POST['user_id'] == 1) && ($getProfile['ID'] == 1) 
                   && ($getProfile['user_level'] === 'administrator') && ($this->userEvent->isUserLevel() === 'administrator')) {

                    $checkError = false;
                    array_push($errors, "Sorry, ID not recognized");
                }

                if ((!empty($_POST['current_pwd'])) || (!empty($_POST['confirm_pwd']))) {

                    if (($_POST['current_pwd']) !== ($_POST['confirm_pwd'])) {

                        $checkError = false;
                        array_push($errors, "Password should both be equal");
                    }

                    if (false === $this->userEvent->reAuthenticateUserPrivilege($getProfile['user_login'], $_POST['current_pwd'])) {

                        $checkError = false;
                        array_push($errors, "re-authentication failed, please check your current password");
                    }
                }


                if (!$checkError) {

                    $this->setView('remove-profile');
                    $this->setPageTitle('Remove profile');
                    $this->setFormAction(ActionConst::DELETEUSER);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('userData', $data_profile);
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

                } else {

                  if (true === terminator($getProfile['ID'])) {

                    (function_exists('sleep')) ? sleep(10) : "";

                    session_unset();
                    session_destroy();
                    session_start();
                    session_regenerate_id(true);
                    Session::getInstance()->startSession();
                    ((!empty($_POST['current_pwd'])) && (is_a($authenticator, 'Authentication'))) ?? $authenticator->removeCookies();
                    Session::getInstance()->destroy();

                    $this->userEvent->setUserId(sanitizer(distill_post_request($filters)['user_id'], 'sql'));
                    $this->userEvent->removeUser();
                    direct_page('login.php', 302);

                  }
                      
                }

            } catch (Throwable $th) {

                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {

                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {

            $this->setView('remove-profile');
            $this->setPageTitle('Remove profile');
            $this->setFormAction(ActionConst::DELETEUSER);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('userData', $data_profile);
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * setView
     *
     * @param string $viewName
     * 
     */
    protected function setView($viewName)
    {
       $this->view = new View('admin', 'ui', 'users', $viewName);
    }

}