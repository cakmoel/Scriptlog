<?php

namespace Scriptlog\Handler\Admin\Media;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for editing an existing media item.
 */
class EditMediaCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $mediaDao = $context['mediaDao'];
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

        if ($mediaDao->checkMediaId($mediaId, $app->sanitizer)) {
            $mediaController->update($mediaId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
