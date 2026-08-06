<?php

namespace Scriptlog\Handler\Admin\User;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for deleting a user.
 */
class DeleteUserCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $userController = $context['userController'];
        $userId = (int)$context['id'];
        $userLogin = $context['userLogin'] ?? '';

        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if (!$app->userDao->checkUserId($userId, $app->sanitizer)) {
            if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
            }
        } else {
            if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
                $userController->removeProfile($userLogin, $app->authenticator);
            } else {
                $userController->remove($userId);
            }
        }
    }
}
