<?php

namespace Scriptlog\Handler\Admin\Comment;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for deleting a comment.
 */
class DeleteCommentCmd implements AdminActionCommand
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
            direct_page('index.php?load=403&');
        }

        if ((!check_integer($commentId)) && (gettype($commentId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if ($commentDao->checkCommentId($commentId, $app->sanitizer)) {
            $commentController->remove($commentId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
