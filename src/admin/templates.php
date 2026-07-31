<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$themeId = isset($_GET['Id']) ? abs((int)$_GET['Id']) : 0;
$themeDao = class_exists('ThemeDao') ? new ThemeDao() : "";
$themeService = class_exists('ThemeService') ? new ThemeService($themeDao, $app->validator, $app->sanitizer) : "";
$themeController = class_exists('ThemeController') ? new ThemeController($themeService) : "";

try {
    $actionKey = empty($action) ? 'default_theme' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $themeId,
            'themeDao' => $themeDao,
            'themeService' => $themeService,
            'themeController' => $themeController,
        ]);
    } else {
        direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
    }
} catch (Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
