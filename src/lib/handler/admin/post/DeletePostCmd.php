<?php

namespace Scriptlog\Handler\Admin\Post;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for deleting a post.
 */
class DeletePostCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $postDao = $context['postDao'];
        $postController = $context['postController'];
        $postId = (int)$context['id'];

        if ($postId <= 0) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if (false === $app->authenticator->userAccessControl(ActionConst::POSTS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($postDao->checkPostId($postId, $app->sanitizer)) {
            $postController->remove($postId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
