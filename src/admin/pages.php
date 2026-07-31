<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$pageId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$pageDao = class_exists('PageDao') ? new PageDao() : "";
$pageService = class_exists('PageService') ? new PageService($pageDao, $app->validator, $app->sanitizer) : "";
$pageController = class_exists('PageController') ? new PageController($pageService) : "";

try {
    $actionKey = empty($action) ? 'default_page' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $pageId,
            'pageDao' => $pageDao,
            'pageService' => $pageService,
            'pageController' => $pageController,
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
