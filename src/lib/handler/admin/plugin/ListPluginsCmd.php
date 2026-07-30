<?php

namespace Scriptlog\Handler\Admin\Plugin;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing plugins.
 */
class ListPluginsCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $pluginController = $context['pluginController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $pluginController->listItems();
    }
}
