<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pluginId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pluginDao = class_exists('PluginDao') ? new PluginDao() : "";
$pluginService = class_exists('PluginService') ? new PluginService($pluginDao, $app->validator, $app->sanitizer) : "";
$pluginController = class_exists('PluginController') ? new PluginController($pluginService) : "";

try {
    $actionKey = empty($action) ? 'default_plugin' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $pluginId,
            'pluginDao' => $pluginDao,
            'pluginService' => $pluginService,
            'pluginController' => $pluginController,
        ]);
    } else {
        direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
    }
} catch (\Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
