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

        // Phase 6 (task 6.2): plugin mutation actions are state changes and must
        // arrive as POST with a valid plugin-action CSRF token.
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            throw new \AppException('Method not allowed', 405);
        }

        if (!csrf_check_token('csrfPluginAction', $_POST, 60 * 10)) {
            throw new \AppException(MESSAGE_UNPLEASANT_ATTEMPT, 400);
        }

        $pluginId = isset($_POST['Id']) ? (int)$_POST['Id'] : $pluginId;

        if (false === $app->authenticator->userAccessControl(ActionConst::PLUGIN_MANAGE)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($pluginDao->checkPluginId($pluginId, $app->sanitizer)) {
            $pluginController->disablePlugin($pluginId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
