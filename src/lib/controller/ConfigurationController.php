<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ConfigurationController
 *
 * @category  Class ConfigurationController extends BaseApp
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConfigurationController
{
    /**
     * view
     *
     * @var object
     *
     */
    private $view;

    /**
     * configService
     *
     * @var object
     *
     */
    private $configService;

    /**
     * pageTitle
     *
     * @var string
     *
     */
    protected $pageTitle;

    /**
     * formAction
     *
     * @var string
     *
     */
    protected $formAction;

    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function setFormAction($formAction)
    {
        $this->formAction = $formAction;
    }

    public function getFormAction()
    {
        return $this->formAction;
    }

    /**
     * UpdateGeneralSetting
     *
     * @param string $args
     * @return void
     *
     */
    public function updateGeneralSetting()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_POST['configFormSubmit'])) {
            $filters = [

               'setting_id' => [
                     'filter' => FILTER_VALIDATE_INT,
                     'flags' => FILTER_REQUIRE_ARRAY],

               'setting_value' => [
                     'filter' => FILTER_FLAG_NO_ENCODE_QUOTES,
                     'flags' => FILTER_REQUIRE_ARRAY]

            ];

            $size = (!empty($_POST['setting_value']) ? count($_POST['setting_value']) : null);

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (isset($_POST['setting_value']['1']) && strcmp($_POST['setting_value']['1'], app_key())) {
                    $checkError = false;
                    array_push($errors, "Application key does not match with the configuration file");
                }

                if (!$checkError) {
                    $this->setView('general-setting');
                    $this->setPageTitle('General Settings');
                    $this->setFormAction(ActionConst::GENERAL_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('settings', $this->configService->grabGeneralSettings('ID', 7));
                    $this->view->set('errors', $errors);
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    for ($i = 1; $i <= $size; $i++) {
                        $setting_value = purify_dirty_html(distill_post_request($filters)['setting_value'][$i]);
                        $setting_id = distill_post_request($filters)['setting_id'][$i];

                        $sql = sprintf("UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = %d", (int)$setting_id);
                        db_simple_query($sql);
                    }

                    $_SESSION['status'] = "generalConfigUpdated";
                    direct_page('index.php?load=option-general&status=generalConfigUpdated', 302);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } elseif (isset($_POST['clearCacheSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (function_exists('page_cache_clear')) {
                    page_cache_clear();
                    $_SESSION['status'] = "cacheCleared";
                    direct_page('index.php?load=option-general&status=cacheCleared', 302);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            }
        } else {
            if ((isset($_SESSION['status'])) && ($_SESSION['status'] == 'generalConfigUpdated')) {
                $checkStatus = true;
                array_push($status, "General setting has been updated");
                unset($_SESSION['status']);
            }

            if ((isset($_SESSION['status'])) && ($_SESSION['status'] == 'cacheCleared')) {
                $checkStatus = true;
                array_push($status, "Page cache has been cleared");
                unset($_SESSION['status']);
            }

            $this->setView('general-setting');
            $this->setPageTitle('General Settings');
            $this->setFormAction(ActionConst::GENERAL_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('settings', $this->configService->grabGeneralSettings('ID', 7));
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * updateReadingSetting
     *
     * @return array|mixed
     *
     */
    public function updateReadingSetting()
    {
        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_POST['configFormSubmit'])) {
            $filters = [

              'setting_id' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY],

              'setting_value' => [
                    'filter' => FILTER_FLAG_NO_ENCODE_QUOTES,
                    'flags' => FILTER_REQUIRE_ARRAY]

            ];

            $size = (!empty($_POST['setting_value']) ? count($_POST['setting_value']) : null);

            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (isset($_POST['setting_value']['8']) && is_numeric($_POST['setting_value']['8']) === false) {
                    $checkError = false;
                    array_push($errors, "Invalid post per page value");
                }

                if (isset($_POST['setting_value']['9']) && is_numeric($_POST['setting_value']['9']) === false) {
                    $checkError = false;
                    array_push($errors, 'Invalid post per rss value');
                }

                if (!$checkError) {
                    $this->setView('reading-setting');
                    $this->setPageTitle('Reading Settings');
                    $this->setFormAction(ActionConst::READING_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('settings', $this->configService->grabReadingSettings());
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    for ($i = 0; $i < $size; $i++) {
                        $setting_value = purify_dirty_html(distill_post_request($filters)['setting_value'][$i]);
                        $setting_id = distill_post_request($filters)['setting_id'][$i];

                        $sql = sprintf("UPDATE tbl_settings SET setting_value = '$setting_value' WHERE ID = %d", (int)$setting_id);
                        db_simple_query($sql);
                    }

                    $_SESSION['status'] = "readingConfigUpdated";
                    direct_page('index.php?load=option-reading&status=readingConfigUpdated', 302);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            if (isset($_SESSION['status'])) {
                $checkStatus = true;
                ($_SESSION['status'] == 'readingConfigUpdated') ? array_push($status, "Reading setting has been updated") : "";
                unset($_SESSION['status']);
            }

            $this->setView('reading-setting');
            $this->setPageTitle('Reading Settings');
            $this->setFormAction(ActionConst::READING_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('settings', $this->configService->grabReadingSettings());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * updatePermalinkConfig
     *
     * @return mixed
     *
     */
    public function updatePermalinkConfig()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (!$getPermalinkValue = $this->configService->grabSettingByName('permalink_setting')) {
            $_SESSION['error'] = "permalinkValueNotFound";
            direct_page('index.php?load=option-permalink&error=permalinkValueNotFound', 404);
        }

        $data_permalink = array(
          'ID' => $getPermalinkValue['ID'],
          'setting_name' => $getPermalinkValue['setting_name'],
          'setting_value' => $getPermalinkValue['setting_value']
        );

        $server_software = json_decode($data_permalink['setting_value'], true);

        $filters = ['permalinks' => isset($_POST['permalinks']) ? Sanitize::severeSanitizer($_POST['permalinks']) : "",
                    'setting_id' => FILTER_SANITIZE_NUMBER_INT,
                    'setting_name' => isset($_POST['setting_name']) ? Sanitize::severeSanitizer($_POST['setting_name']) : ""
                  ];

        if (isset($_POST['configFormSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['permalinks'], ['yes', 'no'])) {
                    $checkError = false;
                    array_push($errors, "Please choose the available value provided!");
                }

                if (!$checkError) {
                    $this->setView('permalink-setting');
                    $this->setPageTitle('Permalink Setting');
                    $this->setFormAction(ActionConst::PERMALINK_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('permalinkData', $data_permalink);
                    $this->view->set('errors', $errors);
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $this->configService->setConfigId((int)distill_post_request($filters)['setting_id']);

                    $this->configService->setConfigName(prevent_injection(distill_post_request($filters)['setting_name']));

                    $permalink_value = array(
                      'rewrite' => distill_post_request($filters)['permalinks'],
                      'server_software' => detect_web_server()
                    );

                    $updated_permalink_value = json_encode($permalink_value);

                    $this->configService->setConfigValue($updated_permalink_value);

                    # Handle web server config writing based on detected server
                    $detected_server = isset($server_software['server_software']) ? $server_software['server_software'] : detect_web_server();

                    if (($detected_server == 'Apache') || ($detected_server == 'LiteSpeed')) {
                        write_htaccess(distill_post_request($filters)['permalinks'], Session::getInstance()->scriptlog_session_level, read_htaccess_config(distill_post_request($filters)['permalinks']));
                    } elseif ($detected_server == 'Nginx') {
                        write_nginx_config(distill_post_request($filters)['permalinks'], Session::getInstance()->scriptlog_session_level, read_nginx_config_template(distill_post_request($filters)['permalinks']));
                    }

                    $this->configService->modifySetting();
                    $_SESSION['status'] = "permalinkConfigUpdated";
                    direct_page('index.php?load=option-permalink&status=permalinkConfigUpdated', 200);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            if (isset($_SESSION['error'])) {
                $checkError = false;
                ($_SESSION['error'] == 'permalinkValueNotFound') ? array_push($errors, "Error: Permalink value not found!") : "";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['status'])) {
                $checkStatus = true;
                ($_SESSION['status'] == 'permalinkConfigUpdated') ? array_push($status, "Permalink setting has been updated") : "";
                unset($_SESSION['status']);
            }

            $this->setView('permalink-setting');
            $this->setPageTitle('Permalink Setting');
            $this->setFormAction(ActionConst::PERMALINK_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('permalinkData', $data_permalink);
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * updateTimezoneConfig
     *
     */
    public function updateTimezoneConfig()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (!$getTimezoneValue = $this->configService->grabSettingByName('timezone_setting')) {
            $_SESSION['error'] = "timezoneValueNotFound";
            direct_page('index.php?load=option-timezone&error=timezoneValueNotFound', 404);
        }

        $data_timezone = array(
          'ID' => $getTimezoneValue['ID'],
          'setting_name' => $getTimezoneValue['setting_name'],
          'setting_value' => $getTimezoneValue['setting_value']
        );

        $timezone_identifier = json_decode($data_timezone['setting_value'], true);

        $filters = [
          'timezone' => isset($_POST['timezone']) ? Sanitize::severeSanitizer($_POST['timezone']) : "",
          'setting_id' => FILTER_SANITIZE_NUMBER_INT,
          'setting_name' => isset($_POST['setting_name']) ? Sanitize::severeSanitizer($_POST['setting_name']) : ""
        ];

        if (isset($_POST['configFormSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['timezone'], print_timezone_list())) {
                    $checkError = false;
                    array_push($errors, "Please choose the available value provided!");
                }

                if (!$checkError) {
                    $this->setView('timezone-setting');
                    $this->setPageTitle('Timezone Setting');
                    $this->setFormAction(ActionConst::TIMEZONE_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('timezoneData', $data_timezone);
                    $this->view->set('errors', $errors);
                    $this->view->set('timezoneIdentifier', $this->configService->timezoneIdentifierDropDown($timezone_identifier['timezone_identifier']));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $this->configService->setConfigId((int)distill_post_request($filters)['setting_id']);

                    $this->configService->setConfigName(prevent_injection(distill_post_request($filters)['setting_name']));

                    $timezone_value = array(
                      'timezone_identifier' => distill_post_request($filters)['timezone']
                    );

                    $updated_timezone_value = json_encode($timezone_value);

                    $this->configService->setConfigValue($updated_timezone_value);

                    $this->configService->modifySetting();

                    $_SESSION['status'] = "timezoneConfigUpdated";
                    direct_page('index.php?load=option-timezone&status=timezoneConfigUpdated', 200);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            if (isset($_SESSION['error'])) {
                $checkError = false;
                ($_SESSION['error'] == 'timezoneValueNotFound') ? array_push($errors, "Error: Timezone value not found!") : "";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['status'])) {
                $checkStatus = true;
                ($_SESSION['status'] == 'timezoneConfigUpdated') ? array_push($status, "Timezone setting has been updated") : "";
                unset($_SESSION['status']);
            }

            $this->setView('timezone-setting');
            $this->setPageTitle('Timezone Setting');
            $this->setFormAction(ActionConst::TIMEZONE_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('timezoneData', $data_timezone);
            $this->view->set('timezoneIdentifier', $this->configService->timezoneIdentifierDropDown($timezone_identifier['timezone_identifier']));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * updateMembershipConfig
     *
     * @return mixed
     *
     */
    public function updateMembershipConfig()
    {

        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (!$getMembershipValue = $this->configService->grabSettingByName('membership_setting')) {
            $_SESSION['error'] = "membershipValueNotFound";
            direct_page('index.php?load=option-memberships&error=membershipValueNotFound');
        }

        $data_memberships = array(
          'ID' => $getMembershipValue['ID'],
          'setting_name' => $getMembershipValue['setting_name'],
          'setting_value' => $getMembershipValue['setting_value']
        );

        $membership_default_role = json_decode($data_memberships['setting_value'], true);

        $filters = [
          'setting_id' => FILTER_SANITIZE_NUMBER_INT,
          'setting_name' => isset($_POST['setting_name']) ? Sanitize::severeSanitizer($_POST['setting_name']) : "",
          'user_can_register' => isset($_POST['user_can_register']) ? FILTER_SANITIZE_NUMBER_INT : 0,
          'user_level' => isset($_POST['user_level']) ? Sanitize::mildSanitizer($_POST['user_level']) : "",
        ];

        if (isset($_POST['configFormSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                if (false === sanitize_selection_box(distill_post_request($filters)['user_level'], ['manager' => 'Manager', 'editor' => 'Editor', 'author' => 'Author', 'contributor' => 'Contributor'])) {
                    $checkError = false;
                    array_push($errors, MESSAGE_INVALID_SELECTBOX);
                }

                if (!$checkError) {
                    $this->setView('membership-setting');
                    $this->setPageTitle('Membership');
                    $this->setFormAction(ActionConst::MEMBERSHIP_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('membershipData', $data_memberships);
                    $this->view->set('errors', $errors);
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('membershipDefaultRole', $this->configService->membershipDefaultRoleDropDown($membership_default_role['default_role']));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $this->configService->setConfigId(distill_post_request($filters)['setting_id']);
                    $this->configService->setConfigName(distill_post_request($filters)['setting_name']);

                    $membership_value = array(
                      'user_can_register' => distill_post_request($filters)['user_can_register'],
                      'default_role' => distill_post_request($filters)['user_level']
                    );

                    $updated_membership_value = json_encode($membership_value);

                    $this->configService->setConfigValue($updated_membership_value);

                    $this->configService->modifySetting();

                    $_SESSION['status'] = "membershipConfigUpdated";
                    direct_page('index.php?load=option-memberships&status=membershipConfigUpdated', 200);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            if (isset($_SESSION['error'])) {
                $checkError = false;
                ($_SESSION['error'] == 'membershipValueNotFound') ? array_push($errors, "Error: Membership value not found!") : "";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['status'])) {
                $checkStatus = true;
                ($_SESSION['status'] == 'membershipConfigUpdated') ? array_push($status, "Membership setting has been updated") : "";
                unset($_SESSION['status']);
            }

            $this->setView('membership-setting');
            $this->setPageTitle('Membership');
            $this->setFormAction(ActionConst::MEMBERSHIP_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('membershipData', $data_memberships);
            $this->view->set('membershipDefaultRole', $this->configService->membershipDefaultRoleDropDown($membership_default_role['default_role']));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * updateDownloadSetting
     *
     * @return array|mixed
     *
     */
    public function updateDownloadSetting()
    {
        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        if (isset($_POST['downloadSettingSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                $settings = [
                  'allowed_mime_types' => $_POST['allowed_mime_types'] ?? [],
                  'expiry_hours' => (int)($_POST['expiry_hours'] ?? 8),
                  'hotlink_protection' => isset($_POST['hotlink_protection']),
                  'allowed_domains' => array_filter(array_map('trim', explode("\n", $_POST['allowed_domains'] ?? ''))),
                  'support_url' => $_POST['support_url'] ?? '',
                  'support_label' => $_POST['support_label'] ?? 'Support'
                ];

                if (DownloadSettings::saveSettings($settings)) {
                    $_SESSION['status'] = 'downloadConfigUpdated';
                    direct_page('index.php?load=option-downloads&action=downloadConfig&status=downloadConfigUpdated', 302);
                } else {
                    $checkError = false;
                    array_push($errors, "Failed to save settings");
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        }

        if (isset($_SESSION['status'])) {
            $checkStatus = true;
            ($_SESSION['status'] == 'downloadConfigUpdated') ? array_push($status, "Download setting has been updated") : "";
            unset($_SESSION['status']);
        }

        $this->setView('download-setting');
        $this->setPageTitle('Download Settings');
        $this->setFormAction(ActionConst::DOWNLOAD_CONFIG);

        if (!$checkError) {
            $this->view->set('errors', $errors);
        }

        if ($checkStatus) {
            $this->view->set('status', $status);
        }

        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('currentSettings', DownloadSettings::getAllSettings());
        $this->view->set('defaultMimeTypes', DownloadSettings::DEFAULT_MIME_TYPES);
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        return $this->view->render();
    }

    /**
     * updateMailSetting
     *
     * @return array|mixed
     *
     */
    public function updateMailSetting()
    {
        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        // SMTP Setting keys
        $smtp_keys = [
            'smtp_host',
            'smtp_port',
            'smtp_encryption',
            'smtp_username',
            'smtp_password',
            'smtp_from_email',
            'smtp_from_name'
        ];

        if (isset($_POST['mailConfigSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                foreach ($smtp_keys as $key) {
                    $value = isset($_POST[$key]) ? $_POST[$key] : '';

                    // Validation
                    if ($key === 'smtp_port' && !empty($value) && !is_numeric($value)) {
                        $checkError = false;
                        array_push($errors, "Invalid port value. Must be numeric.");
                        continue;
                    }

                    if ($key === 'smtp_from_email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $checkError = false;
                        array_push($errors, "Invalid from email address.");
                        continue;
                    }

                    if ($checkError) {
                        // Update or Create setting
                        $existing = $this->configService->grabSettingByName($key);
                        if ($existing) {
                            $this->configService->setConfigId($existing['ID']);
                            $this->configService->setConfigName($key);
                            $this->configService->setConfigValue($value);
                            $this->configService->modifySetting();
                        } else {
                            $this->configService->setConfigName($key);
                            $this->configService->setConfigValue($value);
                            $this->configService->addSetting();
                        }
                    }
                }

                if ($checkError) {
                    $_SESSION['status'] = "mailConfigUpdated";
                    direct_page('index.php?load=option-mail&status=mailConfigUpdated', 302);
                }
            } catch (Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        }

        if (isset($_SESSION['status']) && $_SESSION['status'] == 'mailConfigUpdated') {
            $checkStatus = true;
            array_push($status, "Mail settings have been updated");
            unset($_SESSION['status']);
        }

        $this->setView('mail-setting');
        $this->setPageTitle('Mail Settings');
        $this->setFormAction(ActionConst::MAIL_CONFIG);

        $current_smtp = [];
        foreach ($smtp_keys as $key) {
            $setting = $this->configService->grabSettingByName($key);
            $current_smtp[$key] = $setting['setting_value'] ?? '';
        }

        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('formAction', $this->getFormAction());
        $this->view->set('smtp', $current_smtp);
        if (!$checkError) {
            $this->view->set('errors', $errors);
        }
        if ($checkStatus) {
            $this->view->set('status', $status);
        }
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        return $this->view->render();
    }

    /**
     * updateLanguageSetting
     *
     * @return mixed
     *
     */
    public function updateLanguageSetting()
    {
        $errors = array();
        $status = array();
        $checkError = true;
        $checkStatus = false;

        $languageService = class_exists('LanguageService') ? new LanguageService() : null;

        $langSettingKeys = [
            'lang_default',
            'lang_available',
            'lang_auto_detect',
            'lang_prefix_required'
        ];

        if (isset($_POST['languageConfigSubmit'])) {
            try {
                if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                    throw new AppException("Sorry, unpleasant attempt detected!");
                }

                $defaultLang = isset($_POST['lang_default']) ? Sanitize::severeSanitizer($_POST['lang_default']) : 'en';
                $availableLangs = isset($_POST['lang_available']) && is_array($_POST['lang_available'])
                    ? implode(',', array_map([Sanitize::class, 'severeSanitizer'], $_POST['lang_available']))
                    : 'en';
                $autoDetect = isset($_POST['lang_auto_detect']) ? '1' : '0';
                $prefixRequired = isset($_POST['lang_prefix_required']) ? '1' : '0';

                if (empty($defaultLang)) {
                    $checkError = false;
                    array_push($errors, "Default language must be selected.");
                }

                if (empty($availableLangs)) {
                    $checkError = false;
                    array_push($errors, "At least one language must be available.");
                }

                if (!$checkError) {
                    $activeLanguages = $languageService ? $languageService->getActiveLanguages() : [];
                    $currentSettings = $this->getCurrentLangSettings();

                    $this->setView('language-setting');
                    $this->setPageTitle('Language Configuration');
                    $this->setFormAction(ActionConst::LANGUAGE_CONFIG);
                    $this->view->set('pageTitle', $this->getPageTitle());
                    $this->view->set('formAction', $this->getFormAction());
                    $this->view->set('errors', $errors);
                    $this->view->set('activeLanguages', $activeLanguages);
                    $this->view->set('defaultLang', $currentSettings['lang_default']);
                    $this->view->set('availableLangs', $currentSettings['lang_available']);
                    $this->view->set('autoDetect', $currentSettings['lang_auto_detect']);
                    $this->view->set('prefixRequired', $currentSettings['lang_prefix_required']);
                    $this->view->set('selectedLangs', explode(',', $availableLangs));
                    $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
                } else {
                    $settingsToUpdate = [
                        'lang_default' => $defaultLang,
                        'lang_available' => $availableLangs,
                        'lang_auto_detect' => $autoDetect,
                        'lang_prefix_required' => $prefixRequired,
                    ];

                    foreach ($settingsToUpdate as $key => $value) {
                        $existing = $this->configService->grabSettingByName($key);
                        if ($existing) {
                            $this->configService->setConfigId($existing['ID']);
                            $this->configService->setConfigName($key);
                            $this->configService->setConfigValue($value);
                            $this->configService->modifySetting();
                        }
                    }

                    $_SESSION['status'] = "languageConfigUpdated";
                    direct_page('index.php?load=option-language&status=languageConfigUpdated', 302);
                }
            } catch (\Throwable $th) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($th);
            } catch (AppException $e) {
                LogError::setStatusCode(http_response_code());
                LogError::exceptionHandler($e);
            }
        } else {
            if (isset($_SESSION['status']) && $_SESSION['status'] == 'languageConfigUpdated') {
                $checkStatus = true;
                array_push($status, "Language settings have been updated");
                unset($_SESSION['status']);
            }

            $currentSettings = $this->getCurrentLangSettings();
            $activeLanguages = $languageService ? $languageService->getActiveLanguages() : [];

            $this->setView('language-setting');
            $this->setPageTitle('Language Configuration');
            $this->setFormAction(ActionConst::LANGUAGE_CONFIG);

            if (!$checkError) {
                $this->view->set('errors', $errors);
            }

            if ($checkStatus) {
                $this->view->set('status', $status);
            }

            $this->view->set('pageTitle', $this->getPageTitle());
            $this->view->set('formAction', $this->getFormAction());
            $this->view->set('activeLanguages', $activeLanguages);
            $this->view->set('defaultLang', $currentSettings['lang_default']);
            $this->view->set('availableLangs', $currentSettings['lang_available']);
            $this->view->set('autoDetect', $currentSettings['lang_auto_detect']);
            $this->view->set('prefixRequired', $currentSettings['lang_prefix_required']);
            $this->view->set('selectedLangs', explode(',', $currentSettings['lang_available']['setting_value'] ?? 'en'));
            $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        }

        return $this->view->render();
    }

    /**
     * getCurrentLangSettings
     *
     * @return array
     *
     */
    private function getCurrentLangSettings(): array
    {
        $keys = ['lang_default', 'lang_available', 'lang_auto_detect', 'lang_prefix_required'];
        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = $this->configService->grabSettingByName($key);
        }

        return $settings;
    }

    /**
     * setView
     *
     * @param string $viewName
     *
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'setting', $viewName);
    }
}
