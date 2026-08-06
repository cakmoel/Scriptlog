<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action']), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : "";
$postId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$postDao = class_exists('PostDao') ? new PostDao() : "";
$topicDao = class_exists('TopicDao') ? new TopicDao() : "";
$mediaDao = class_exists('MediaDao') ? new MediaDao() : "";
$postService = class_exists('PostService') ? new PostService($postDao, $app->validator, $app->sanitizer) : "";
$postAppService = class_exists('PostApplicationService') ? new PostApplicationService($postService) : "";
$postController = class_exists('PostController') ? new PostController($postService, $topicDao, $mediaDao, $postAppService) : "";

try {
    $actionKey = empty($action) ? 'default_post' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $postId,
            'postDao' => $postDao,
            'topicDao' => $topicDao,
            'mediaDao' => $mediaDao,
            'postService' => $postService,
            'postAppService' => $postAppService,
            'postController' => $postController,
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
