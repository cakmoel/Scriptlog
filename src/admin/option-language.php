<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * option-language.php
 *
 * Language/i18n settings admin page
 *
 * @category Admin Page
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  2.0
 * @since    Since Release 1.0
 *
 */

if (false === $app->authenticator->userAccessControl(ActionConst::CONFIGURATION)) {
    direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
}

$configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : null;
$configService = class_exists('ConfigurationService') ? new ConfigurationService($configDao, $app->validator, $app->sanitizer) : null;
$configController = class_exists('ConfigurationController') ? new ConfigurationController($configService) : null;

try {

    if ($configController) {
        $configController->updateLanguageSetting();
    } else {
        throw new AppException("Configuration module could not be initialized.");
    }

} catch (\Throwable $th) {

    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }

} catch (AppException $e) {

    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($e);
    }

}
