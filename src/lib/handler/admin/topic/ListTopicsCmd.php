<?php

namespace Scriptlog\Handler\Admin\Topic;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing all topics (default action).
 */
class ListTopicsCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $topicController = $context['topicController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::TOPICS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $topicController->listItems();
    }
}
