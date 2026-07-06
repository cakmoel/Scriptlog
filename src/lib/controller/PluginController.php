<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class PluginController
 *
 * @category Class PluginController extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0.0
 * @since    Since Release 1.0.0
 * @SuppressWarnings(PHPMD.ElseExpression)
 *
 */
class PluginController extends BaseApp
{
    /**
     * view
     *
     * @var object
     *
     */
    private $view;

    /**
     * pluginService
     *
     * @var object
     *
     */
    private $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->pluginService = $pluginService;
    }

    public function listItems()
    {
        $errors = $this->retrievePluginSessionErrors();
        $status = $this->retrievePluginSessionStatus();

        $this->setView('all-plugins');
        $this->setPageTitle('Plugins');
        $this->view->set('pageTitle', $this->getPageTitle());

        if (!empty($errors)) {
            $this->view->set('errors', $errors);
        }

        if (!empty($status)) {
            $this->view->set('status', $status);
        }

        $this->view->set('pluginsTotal', $this->pluginService->totalPlugins());
        $this->view->set('plugins', $this->pluginService->grabPlugins());
        return $this->view->render();
    }

    private function retrievePluginSessionErrors()
    {
        $errors = array();
        if (isset($_SESSION['error'])) {
            ($_SESSION['error'] == 'pluginNotFound') ? array_push($errors, "Error: Plugin Not Found!") : "";
            ($_SESSION['error'] == 'tableNotFound') ? array_push($errors, "Error: Table Plugin Not Found") : "";
            unset($_SESSION['error']);
        }
        return $errors;
    }

    private function retrievePluginSessionStatus()
    {
        $status = array();
        if (isset($_SESSION['status'])) {
            ($_SESSION['status'] == 'pluginAdded') ? array_push($status, "New plugin added") : "";
            ($_SESSION['status'] == 'pluginInstalled') ? array_push($status, "New plugin installed") : "";
            ($_SESSION['status'] == 'pluginUpdated') ? array_push($status, "Plugin updated") : "";
            ($_SESSION['status'] == 'pluginActivated') ? array_push($status, "Plugin actived") : "";
            ($_SESSION['status'] == 'pluginDeactivated') ? array_push($status, "Plugin deactivated") : "";
            ($_SESSION['status'] == 'pluginDeleted') ? array_push($status, "Plugin deleted") : "";
            unset($_SESSION['status']);
        }
        return $status;
    }

    public function insert()
    {
        // leave empty
    }

    public function installPlugin()
    {
        $errors = array();
        $checkError = true;

        if (!isset($_POST['pluginFormSubmit'])) {
            $this->setView('install-plugin');
            $this->setPageTitle('Upload Plugin');
            $this->setFormAction(ActionConst::INSTALLPLUGIN);
            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('pluginLevel', $this->pluginService->pluginLevelDropDown());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
            return $this->view->render();
        }

        $file_name = isset($_FILES['zip_file']['name']) ? $_FILES['zip_file']['name'] : null;
        $file_size = isset($_FILES['zip_file']['size']) ? $_FILES['zip_file']['size'] : null;
        $file_location = isset($_FILES['zip_file']['tmp_name']) ? $_FILES['zip_file']['tmp_name'] : null;
        $file_error = isset($_FILES['zip_file']['error']) ? $_FILES['zip_file']['error'] : null;

        try {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request");
                throw new AppException("Sorry, unpleasant attempt detected!");
            }

            $checkError = $this->validatePluginUpload($file_error, $file_size, $file_location, $file_name, $errors, $checkError);

            if (!$checkError) {
                $this->setView('install-plugin');
                $this->setPageTitle('Upload Plugin');
                $this->setFormAction(ActionConst::INSTALLPLUGIN);
                $this->view->set('pageTitle', $this->getPageTitle());
                $this->view->set('formAction', $this->getFormAction());
                $this->view->set('errors', $errors);
                $this->view->set('formData', $_POST);
                $this->view->set('pluginLevel', $this->pluginService->pluginLevelDropDown());
                $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                return $this->view->render();
            }

            $this->saveInstalledPlugin($file_name);
        } catch (Throwable $th) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($th);
        } catch (AppException $e) {
            LogError::setStatusCode(http_response_code());
            LogError::exceptionHandler($e);
        }

        return $this->view->render();
    }

    private function validatePluginUpload($file_error, $file_size, $file_location, $file_name, &$errors, $checkError)
    {
        if (!isset($file_error) || is_array($file_error)) {
            $checkError = false;
            array_push($errors, "Invalid paramenter");
        }

        $checkError = $this->handlePluginUploadError($file_error, $errors, $checkError);

        if (($file_size > scriptlog_upload_filesize()) || (format_size_unit(filesize($file_location)) == '0 bytes')) {
            $checkError = false;
            array_push($errors, "Exceeded file size limit. Maximum file size is. " . format_size_unit(scriptlog_upload_filesize()));
        }

        if (false === check_file_name($file_location)) {
            $checkError = false;
            array_push($errors, "file name is not valid");
        }

        if (true == check_file_length($file_location)) {
            $checkError = false;
            array_push($errors, "file name is too long");
        }

        $plugin_dir_name = current(explode(".", $file_name));

        if (is_dir(__DIR__ . '/../../' . APP_PLUGIN . $plugin_dir_name . DS)) {
            $checkError = false;
            array_push($errors, "Sorry you have installed this plugin before.");
        }

        if ($this->pluginService->isPluginExists($plugin_dir_name) === true) {
            $checkError = false;
            array_push($errors, "Sorry you have installed this plugin before.");
        }

        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $validate_ext = (strtolower($file_extension) == 'zip' ? true : false);

        if (is_uploaded_file($file_location)) {
            if ((!$validate_ext) || (false === check_mime_type(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'], $file_location))) {
                $checkError = false;
                array_push($errors, "Invalid file format");
            } else {
                if (upload_plugin($file_location, basename($file_name)) === false) {
                    $checkError = false;
                    array_push($errors, "Zip file corrupted");
                }
            }
        }

        return $checkError;
    }

    private function handlePluginUploadError($file_error, &$errors, $checkError)
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

    private function saveInstalledPlugin($file_name)
    {
        $plugin_ini_path = __DIR__ . '/../../' . APP_PLUGIN . basename($file_name, '.zip') . DS . 'plugin.ini';

        if (file_exists($plugin_ini_path)) {
            $plugin_ini = parse_ini_file($plugin_ini_path);

            $plugin_link = generate_request('index.php', 'get', [$plugin_ini['plugin_loader'], $plugin_ini['plugin_action'], 0])['link'];

            $this->pluginService->setPluginName($plugin_ini['plugin_name']);
            $this->pluginService->setPluginLink($plugin_link);
            $this->pluginService->setPluginDirectory($plugin_ini['plugin_directory']);
            $this->pluginService->setPluginDescription($plugin_ini['plugin_description']);
            $this->pluginService->setPluginLevel($plugin_ini['plugin_level']);
            $this->pluginService->addPlugin();

            $_SESSION['status'] = "pluginInstalled";
            direct_page("index.php?load=plugins&status=pluginInstalled", 302);
        } else {
            direct_page("index.php?load=plugins", 302);
        }
    }

    public function update($id)
    {
        // leave empty
    }

    /**
     * enablePlugin
     *
     * @param int|num $id
     *
     */
    public function enablePlugin($id)
    {

        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getPlugin = $this->pluginService->grabPlugin($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getPlugin) {
                    $checkError = false;
                    array_push($errors, 'Error: Plugin not found');
                }

                if (!$checkError) {
                    $this->setView('all-plugins');
                    $this->setPageTitle('Plugin not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('pluginsTotal', $this->pluginService->totalPlugins());
                    $this->view->set('plugins', $this->pluginService->grabPlugins());
                    return $this->view->render();
                }

                $this->pluginService->setPluginId($id);

                if ($this->pluginService->activateInstalledPlugin() === true) {
                    $_SESSION['status'] = "pluginActivated";
                    direct_page('index.php?load=plugins&status=pluginActivated', 302);
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
     * disablePlugin
     *
     * @param int|num $id
     *
     */
    public function disablePlugin($id)
    {

        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getPlugin = $this->pluginService->grabPlugin($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getPlugin) {
                    $checkError = false;
                    array_push($errors, 'Error: Plugin not found');
                }

                if (!$checkError) {
                    $this->setView('all-plugins');
                    $this->setPageTitle('Plugin not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('pluginsTotal', $this->pluginService->totalPlugins());
                    $this->view->set('plugins', $this->pluginService->grabPlugins());
                    return $this->view->render();
                }

                $this->pluginService->setPluginId($id);
                $this->pluginService->deactivateInstalledPlugin();
                $_SESSION['status'] = "pluginDeactivated";
                direct_page('index.php?load=plugins&status=pluginDeactivated', 302);
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
     * remove
     *
     * @param int|num $id
     *
     */
    public function remove($id)
    {

        $checkError = true;
        $errors = array();

        if (isset($_GET['Id'])) {
            $getPlugin = $this->pluginService->grabPlugin($id);

            try {
                if (!filter_input(INPUT_GET, 'Id', FILTER_SANITIZE_NUMBER_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    header(($_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.1") . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (!$getPlugin) {
                    $checkError = false;
                    array_push($errors, 'Error: Plugin not found');
                }

                if (!$checkError) {
                    $this->setView('all-plugins');
                    $this->setPageTitle('Plugin not found');
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('errors', $errors);
                    $this->view->set('pluginsTotal', $this->pluginService->totalPlugins());
                    $this->view->set('plugins', $this->pluginService->grabPlugins());
                    return $this->view->render();
                }

                $this->pluginService->setPluginId($id);
                $this->pluginService->removePlugin();
                $_SESSION['status'] = "pluginDeleted";
                direct_page('index.php?load=plugins&status=pluginDeleted', 302);
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
        $this->view = new View('admin', 'ui', 'plug-in', $viewName);
    }
}
