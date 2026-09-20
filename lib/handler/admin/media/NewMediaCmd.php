<?php

namespace Scriptlog\Handler\Admin\Media;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for creating a new media item.
 */
class NewMediaCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $mediaController = $context['mediaController'];
        $mediaId = (int)$context['id'];

        if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ((!check_integer($mediaId)) && (gettype($mediaId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if ($mediaId == 0) {
            $mediaController->insert();
        } else {
            direct_page('index.php?load=dashboard', 302);
        }
    }
}
