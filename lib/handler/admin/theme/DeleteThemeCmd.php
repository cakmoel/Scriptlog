<?php

namespace Scriptlog\Handler\Admin\Theme;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;
use Scriptlog\Handler\AdminActionCommand;

/**
 * Command for deleting a theme.
 */
class DeleteThemeCmd implements AdminActionCommand
{
    /**
     * {@inheritdoc}
     */
    public function execute(array $context): void
    {
        $app = $context['app'];
        $themeDao = $context['themeDao'];
        $themeController = $context['themeController'];
        $themeId = (int)$context['id'];

        if ((!check_integer($themeId)) && (gettype($themeId) !== "integer")) {
            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
            header("Status: 400 Bad Request");
            throw new \AppException("Invalid ID data type!");
        }

        if (false === $app->authenticator->userAccessControl(ActionConst::THEMES)) {
            direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
        }

        if ($themeDao->checkThemeId($themeId, $app->sanitizer)) {
            $themeController->remove($themeId);
        } else {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        }
    }
}
