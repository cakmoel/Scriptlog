<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$topicId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$topicDao = class_exists('TopicDao') ? new TopicDao() : "";
$topicService = class_exists('TopicService') ? new TopicService($topicDao, $app->validator, $app->sanitizer) : "";
$topicController = class_exists('TopicController') ? new TopicController($topicService) : "";

try {
    $actionKey = empty($action) ? 'default_topic' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $topicId,
            'topicDao' => $topicDao,
            'topicService' => $topicService,
            'topicController' => $topicController,
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
