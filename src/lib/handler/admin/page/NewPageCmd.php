<?php

namespace Scriptlog\Handler\Admin\Page;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new page.
 */
class NewPageCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $pageController = $context['pageController'];
        $pageId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::PAGES)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($pageId)) && (gettype($pageId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if ($pageId == 0) {
            $pageController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
