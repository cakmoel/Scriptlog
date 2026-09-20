<?php

namespace Scriptlog\Handler\Admin\Theme;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new theme.
 */
class NewThemeCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $themeController = $context['themeController'];
        $themeId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::THEMES)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("invalid ID data type!");
        }

        if ($themeId == 0) {
            $themeController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
