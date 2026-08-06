<?php

namespace Scriptlog\Handler\Admin\User;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new user.
 */
class NewUserCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $userController = $context['userController'];
        $userId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($userId)) && (gettype($userId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("invalid ID data type!");
        }

        if ($userId === 0) {
            $userController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
