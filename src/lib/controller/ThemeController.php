<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ThemeController
 *
 * @category Class ThemeController extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0
 * @SuppressWarnings(PHPMD.ElseExpression)
 *
 */
class ThemeController extends BaseApp
{
    private $view;

    private $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function listItems()
    {
        $errors = $this->retrieveSessionErrors();
        $status = $this->retrieveThemeSessionStatus();

        $this->setView('all-templates');
        $this->setPageTitle('Themes');
        $this->view->set('pageTitle', $this->getPageTitle());

        if (!empty($errors)) {
            $this->view->set('errors', $errors);
        }

        if (!empty($status)) {
            $this->view->set('status', $status);
        }

        $this->view->set('themesTotal', $this->themeService->totalThemes());
        $this->view->set('themes', $this->themeService->grabThemes());
        return $this->view->render();
    }

    private function retrieveThemeSessionStatus()
    {
        $status = array();
        if (isset($_SESSION['status'])) {
            ($_SESSION['status'] == "themeAdded") ? array_push($status, "New theme added") : "";
            ($_SESSION['status'] == "themeInstalled") ? array_push($status, "Theme installation process is successful, please activate it first to see it works!") : "";
            ($_SESSION['status'] == "themeUpdated") ? array_push($status, "Theme updated") : "";
            ($_SESSION['status'] == "themeActivated") ? array_push($status, "Theme activated") : "";
            ($_SESSION['status'] == "themeDeactivated") ? array_push($status, "Theme deactivated") : "";
            ($_SESSION['status'] == "themeDeleted") ? array_push($status, "Theme deleted") : "";
            unset($_SESSION['status']);
        }
        return $status;
    }

    private function retrieveSessionErrors()
    {
        $errors = array();
        if (isset($_SESSION['error'])) {
            ($_SESSION['error'] == 'themeNotFound') ? array_push($errors, "Error: Theme Not Found!") : "";
            unset($_SESSION['error']);
        }
        return $errors;
    }

    /**
     * insert()
     *
     * @inheritDoc
     * @uses BaseApp::insert
     */
    public function insert()
    {
        $errors = array();
        $checkError = true;

        if (!isset($_POST['themeFormSubmit'])) {
            $this->setView('edit-template');
            $this->setPageTitle('Add New Theme');
            $this->setFormAction(ActionConst::NEWTHEME);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            return $this->view->render();
        }

        $filters = [
          'theme_title' => isset($_POST['theme_title']) ? Sanitize::severeSanitizer($_POST['theme_title']) : "",
          'theme_description' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
          'theme_designer' => isset($_POST['theme_designer']) ? Sanitize::severeSanitizer($_POST['theme_designer']) : "",
          'theme_directory' => isset($_POST['theme_directory']) ? Sanitize::severeSanitizer($_POST['theme_directory']) : ""
        ];

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request");
                throw new AppException("Sorry, unpleasant attempt detected!");
            }

            if (empty($_POST['theme_title']) || empty($_POST['theme_designer']) || empty($_POST['theme_directory'])) {
                $checkError = false;
                array_push($errors, "All columns required must be filled");
            }

            if ($this->themeService->isThemeExists(distill_post_request($filters)['theme_title']) === true) {
                $checkError = false;
                array_push($errors, "Sorry, you have installed this theme before.");
            }

            if (!$checkError) {
                $this->setView('edit-template');
                $this->setPageTitle('Add New Theme');
                $this->setFormAction(ActionConst::NEWTHEME);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('formData', $_POST);
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                return $this->view->render();
            }

            $this->themeService->setThemeTitle(prevent_injection(distill_post_request($filters)['theme_title']));
            $this->themeService->setThemeDescription(purify_dirty_html(distill_post_request($filters)['theme_description']));
            $this->themeService->setThemeDesigner(prevent_injection(distill_post_request($filters)['theme_designer']));
            $this->themeService->setThemeDirectory(prevent_injection(distill_post_request($filters)['theme_directory']));
            $this->themeService->addTheme();
            $_SESSION['status'] = "themeAdded";
            direct_page('index.php?load=templates&status=themeAdded', 302);
        } catch (Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        } catch (AppException $e) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($e);
        }

        return $this->view->render();
    }

    /**
     * setupTheme
     *
     */
    public function setupTheme()
    {
        $errors = array();
        $checkError = true;

        if (!isset($_POST['themeFormSubmit'])) {
            $this->setView('install-template');
            $this->setPageTitle('Upload Theme');
            $this->setFormAction(ActionConst::INSTALLTHEME);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            return $this->view->render();
        }

        $file_name = isset($_FILES['zip_file']['name']) ? $_FILES['zip_file']['name'] : null;
        $file_size = isset($_FILES['zip_file']['size']) ? $_FILES['zip_file']['size'] : null;
        $file_location = isset($_FILES['zip_file']['tmp_name']) ? $_FILES['zip_file']['tmp_name'] : null;
        $file_error = isset($_FILES['zip_file']['error']) ? $_FILES['zip_file']['error'] : null;

        $theme_title = current(explode(".", $file_name));
        $theme_dir = $theme_title;

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                throw new AppException("Sorry, unpleasant attempt detected!");
            }

            $checkError = $this->validateThemeUpload($file_error, $file_size, $file_location, $file_name, $theme_title, $errors, $checkError);

            if (!$checkError) {
                $this->setView('install-template');
                $this->setPageTitle('Upload Theme');
                $this->setFormAction(ActionConst::INSTALLTHEME);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                return $this->view->render();
            }

            $this->installUploadedTheme($file_name, $file_location, $theme_title, $theme_dir);
        } catch (Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        } catch (AppException $e) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($e);
        }

        return $this->view->render();
    }

    private function validateThemeUpload($file_error, $file_size, $file_location, $file_name, $theme_title, &$errors, $checkError)
    {
        if (!isset($file_error) || is_array($file_error)) {
            $checkError = false;
            array_push($errors, "Invalid paramenter");
        }

        $checkError = $this->handleUploadError($file_error, $errors, $checkError);

        if ($file_size > scriptlog_upload_filesize()) {
            $checkError = false;
            array_push($errors, "Exceeded file size limit. Maximum file size is. " . format_size_unit(scriptlog_upload_filesize()));
        }

        if (false === check_file_name($file_location)) {
            $checkError = false;
            array_push($errors, "file name is not valid");
        }

        if (true === check_file_length($file_location)) {
            $checkError = false;
            array_push($errors, "file name is too long");
        }

        if (!(is_writable(__DIR__ . '/../../' . APP_THEME))) {
            $checkError = false;
            array_push($errors, "Permission denied.");
        } elseif ((is_dir(__DIR__ . '/../../' . APP_THEME . $theme_title . '/')) || (is_readable(__DIR__ . '/../../' . APP_THEME . $theme_title . '/theme.ini'))) {
            $checkError = false;
            array_push($errors, "Sorry, you have installed this theme before.");
        }

        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $validate_format = (strtolower($extension) == 'zip' ? true : false);

        if (is_uploaded_file($file_location)) {
            if ((!$validate_format) || false === check_mime_type(array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'), $file_location)) {
                $checkError = false;
                array_push($errors, "Invalid file format.Make sure you have a .zip format");
            } else {
                upload_theme(basename($file_name), $file_location, ["..", ".git", ".svn", "composer.json", "composer.lock", "framework_config.yaml", ".html", ".phtml", ".pl", ".py", ".sh"]);
            }
        }

        return $checkError;
    }

    private function handleUploadError($file_error, &$errors, $checkError)
    {
        switch ($file_error) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $checkError = false;
                array_push($errors, "No file uploaded");
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $checkError = false;
                array_push($errors, "Exceeded filesize limit");
                break;
            default:
                $checkError = false;
                array_push($errors, "Unknown errors");
                break;
        }
        return $checkError;
    }

    private function installUploadedTheme($file_name, $file_location, $theme_title, $theme_dir)
    {
        $theme_ini = null;

        if (file_exists(APP_ROOT . 'public/themes/' . $theme_title . '/theme.ini')) {
            $theme_ini = parse_ini_file(APP_ROOT . 'public/themes/' . $theme_title . '/theme.ini');
        }

        $this->themeService->setThemeTitle($theme_ini['theme_name']);
        $this->themeService->setThemeDescription($theme_ini['theme_description']);
        $this->themeService->setThemeDesigner($theme_ini['theme_designer']);
        $this->themeService->setThemeDirectory($theme_dir);
        $this->themeService->addTheme();
        $_SESSION['status'] = "themeAdded";
        direct_page('index.php?load=templates&status=themeAdded', 200);
    }

    public function update($id)
    {
        $errors = array();
        $checkError = true;

        $getTheme = $this->themeService->grabTheme($id);
        if (!$getTheme) {
            $_SESSION['error'] = "themeNotFound";
            direct_page('index.php?load=templates&error=themeNotFound', 404);
        }

        $data_theme = array(
          'ID' => $getTheme['ID'],
          'theme_title' => $getTheme['theme_title'],
          'theme_desc' => $getTheme['theme_desc'],
          'theme_designer' => $getTheme['theme_designer'],
          'theme_directory' => $getTheme['theme_directory'],
          'theme_status' => $getTheme['theme_status']
        );

        if (!isset($_POST['themeFormSubmit'])) {
            $this->setView('edit-template');
            $this->setPageTitle('Edit Theme');
            $this->setFormAction(ActionConst::EDITTHEME);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('themeData', $data_theme);
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            return $this->view->render();
        }

        $filters = ['theme_title' => isset($_POST['theme_title']) ? Sanitize::severeSanitizer($_POST['theme_title']) : "",
                    'theme_description' => FILTER_SANITIZE_SPECIAL_CHARS,
                    'theme_designer' => isset($_POST['theme_designer']) ? Sanitize::severeSanitizer($_POST['theme_designer']) : "",
                    'theme_directory' => isset($_POST['theme_directory']) ? Sanitize::severeSanitizer($_POST['theme_directory']) : "",
                    'theme_id' => FILTER_SANITIZE_NUMBER_INT];
        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request");
                throw new AppException("Sorry, unpleasant attempt detected!");
            }

            if (empty($_POST['theme_title']) || empty($_POST['theme_designer']) || empty($_POST['theme_directory'])) {
                $checkError = false;
                array_push($errors, "All columns required must be filled");
            }

            if (!$checkError) {
                $this->setView('edit-template');
                $this->setPageTitle('Edit Theme');
                $this->setFormAction(ActionConst::EDITTHEME);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('themeData', $data_theme);
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                return $this->view->render();
            }

            $this->themeService->setThemeId((int)distill_post_request($filters)['theme_id']);
            $this->themeService->setThemeTitle(prevent_injection(distill_post_request($filters)['theme_title']));
            $this->themeService->setThemeDescription(purify_dirty_html(distill_post_request($filters)['theme_description']));
            $this->themeService->setThemeDesigner(distill_post_request($filters)['theme_designer']);
            $this->themeService->setThemeDirectory(prevent_injection(distill_post_request($filters)['theme_directory']));
            $this->themeService->modifyTheme();
            $_SESSION['status'] = "themeUpdated";
            direct_page('index.php?load=templates&status=themeUpdated', 302);
        } catch (Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        } catch (AppException $e) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($e);
        }

        return $this->view->render();
    }

    public function remove($id)
    {

        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getTheme = $this->themeService->grabTheme($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getTheme) {
                    $checkError = false;
                    array_push($errors, 'Error: Theme not found');
                }

                if (!$checkError) {
                    $this->setView('all-templates');
                    $this->setPageTitle('Theme not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('themesTotal', $this->themeService->totalThemes());
                    $this->view->set('themes', $this->themeService->grabThemes());
                    return $this->view->render();
                }

                $this->themeService->setThemeId($id);
                $this->themeService->removeTheme();
                $_SESSION['status'] = "themeDeleted";
                direct_page('index.php?load=templates&status=themeDeleted', 302);
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        }
    }

    public function enableTheme($id)
    {
        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getTheme = $this->themeService->grabTheme($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getTheme) {
                    $checkError = false;
                    array_push($errors, 'Error: Theme not found');
                }

                if (!$checkError) {
                    $this->setView('all-templates');
                    $this->setPageTitle('Theme not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('themesTotal', $this->themeService->totalThemes());
                    $this->view->set('themes', $this->themeService->grabThemes());
                    return $this->view->render();
                }

                $this->themeService->setThemeId($id);
                $this->themeService->activateInstalledTheme();
                $_SESSION['status'] = "themeActivated";
                direct_page('index.php?load=templates&status=themeActivated', 302);
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        }
    }

    public function disableTheme($id)
    {
        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getTheme = $this->themeService->grabTheme($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getTheme) {
                    $checkError = false;
                    array_push($errors, 'Error: Theme not found');
                }

                if (!$checkError) {
                    $this->setView('all-templates');
                    $this->setPageTitle('Theme not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('themesTotal', $this->themeService->totalThemes());
                    $this->view->set('themes', $this->themeService->grabThemes());
                    return $this->view->render();
                }

                $this->themeService->setThemeId($id);
                $this->themeService->deactivateInstalledTheme();
                $_SESSION['status'] = "themeDeactivated";
                direct_page('index.php?load=templates&status=themeDeactivated', 302);
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
     * setView
     *
     * @param string $viewName
     *
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'appearance', $viewName);
    }
}
