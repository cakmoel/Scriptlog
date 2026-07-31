<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$mediaId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$mediaDao = class_exists('MediaDao') ? new MediaDao() : "";
$downloadModel = class_exists('DownloadModel') ? new DownloadModel() : "";
$mediaService = class_exists('MediaService') ? new MediaService($mediaDao, $downloadModel, $app->validator, $app->sanitizer) : "";
$mediaController = class_exists('MediaController') ? new MediaController($mediaService) : "";

try {
    $actionKey = empty($action) ? 'default_media' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $mediaId,
            'mediaDao' => $mediaDao,
            'downloadModel' => $downloadModel,
            'mediaService' => $mediaService,
            'mediaController' => $mediaController,
        ]);
    } else {
        direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
    }
} catch (Throwable $th) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
