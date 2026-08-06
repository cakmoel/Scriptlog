<?php

namespace Scriptlog\Handler\Admin\Media;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for listing media items.
 */
class ListMediaCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $mediaController = $context['mediaController'];

        if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        $mediaController->listItems();
    }
}
