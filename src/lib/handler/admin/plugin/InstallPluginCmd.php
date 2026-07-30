<?php

namespace Scriptlog\Handler\Admin\Plugin;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for installing a plugin.
 */
class InstallPluginCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $pluginController = $context['pluginController'];
        $pluginId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::PLUGINS)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($pluginId)) && (gettype($pluginId))) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if ($pluginId == 0) {
            $pluginController->installPlugin();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
