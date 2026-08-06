<?php

namespace Scriptlog\Handler\Admin\User;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing users.
 */
class ListUsersCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $userController = $context['userController'];
        $userLogin = $context['userLogin'] ?? '';

        if (false === $app->authenticator->userAccessControl(ActionConst::USERS)) {
            $userController->showProfile($userLogin);
        } else {
            $userController->listItems();
        }
    }
}
