<?php

namespace Scriptlog\Handler\Admin\Comment;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing all comments (default action).
 */
class ListCommentsCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $commentController = $context['commentController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::COMMENTS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $commentController->listItems();
    }
}
