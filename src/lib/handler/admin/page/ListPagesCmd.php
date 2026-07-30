<?php

namespace Scriptlog\Handler\Admin\Page;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing pages.
 */
class ListPagesCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $pageController = $context['pageController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::PAGES)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $pageController->listItems();
    }
}
