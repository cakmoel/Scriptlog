<?php

namespace Scriptlog\Handler\Admin\Theme;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing themes.
 */
class ListThemesCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $themeController = $context['themeController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::THEMES)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $themeController->listItems();
    }
}
