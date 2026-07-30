<?php

namespace Scriptlog\Handler\Admin\Topic;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new topic.
 */
class NewTopicCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $topicController = $context['topicController'];
        $topicId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($topicId)) && (gettype($topicId) != "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type");
        }

        if ($topicId == 0) {
            $topicController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
