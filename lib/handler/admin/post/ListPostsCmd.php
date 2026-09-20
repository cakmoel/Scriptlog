<?php

namespace Scriptlog\Handler\Admin\Post;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing all posts (default action).
 */
class ListPostsCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $postController = $context['postController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::POSTS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $postController->listItems();
    }
}
