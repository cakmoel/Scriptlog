<?php

namespace Scriptlog\Handler\Admin\Post;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new post.
 */
class NewPostCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $postController = $context['postController'];
        $postId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::POSTS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($postId == 0) {
            $postController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
