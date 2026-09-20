<?php

namespace Scriptlog\Handler\Admin\Plugin;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for deactivating a plugin.
 */
class DeactivatePluginCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $pluginDao = $context['pluginDao'];
        $pluginController = $context['pluginController'];
        $pluginId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($pluginDao->checkPluginId($pluginId, $app->sanitizer)) {
            $pluginController->disablePlugin($pluginId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
