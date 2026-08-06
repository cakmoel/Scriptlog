<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action']), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : "";
$userId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
$sessionId = isset($_GET['sessionId']) ? safe_html($_GET['sessionId']) : null;
$userService = class_exists('UserService') ? new UserService($app->userDao, $app->validator, $app->userToken, $app->sanitizer) : "";
$userController = class_exists('UserController') ? new UserController($userService) : "";

try {
    $actionKey = empty($action) ? 'default_user' : $action;

    if ($app->adminActionRegistry && $app->adminActionRegistry->has($actionKey)) {
        $app->adminActionRegistry->execute($actionKey, [
            'app' => $app,
            'id' => $userId,
            'sessionId' => $sessionId,
            'userLogin' => $user_login ?? '',
            'userService' => $userService,
            'userController' => $userController,
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
