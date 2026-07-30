<?php

namespace Scriptlog\Handler\Admin\Comment;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for editing an existing comment.
 */
class EditCommentCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $commentDao = $context['commentDao'];
        $commentController = $context['commentController'];
        $commentId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::COMMENTS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($commentDao->checkCommentId($commentId, $app->sanitizer)) {
            $commentController->update($commentId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
