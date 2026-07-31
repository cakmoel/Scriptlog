<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$commentId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$commentDao = class_exists('CommentDao') ? new CommentDao() : "";
$commentService = class_exists('CommentService') ? new CommentService($commentDao, $app->validator, $app->sanitizer) : "";
$commentController = class_exists('CommentController') ? new CommentController($commentService) : "";

try {
    $actionKey = empty($action) ? 'default' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $commentId,
            'commentDao' => $commentDao,
            'commentService' => $commentService,
            'commentController' => $commentController,
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
