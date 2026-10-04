<?php

declare(strict_types=1);

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\ConfigurationDao;
use Scriptlog\Dao\PostDao;

/**
 * Class ScheduledPostService
 *
 * Promotes posts with the 'scheduled' status whose post_date has passed,
 * using a lightweight per-request flip (WordPress-style "poor man's cron").
 *
 * @category  Service Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 */
class ScheduledPostService
{
    /**
     * Name of the global enable/disable setting.
     *
     * @var string
     */
    private const ENABLED_SETTING = 'writing_scheduled_post_enabled';

    /**
     * Name of the next-run shortcut setting.
     *
     * @var string
     */
    private const NEXT_RUN_SETTING = 'writing_scheduled_next_run';

    /**
     * Post data access object.
     *
     * @var PostDao
     */
    private $postDao;

    /**
     * Configuration (settings) data access object.
     *
     * @var ConfigurationDao
     */
    private $configDao;

    /**
     * Sanitizer used for settings key/value handling.
     *
     * @var Sanitize
     */
    private $sanitize;

    /**
     * Constructor.
     *
     * @param PostDao $postDao
     * @param ConfigurationDao $configDao
     * @param Sanitize $sanitize
     */
    public function __construct(PostDao $postDao, ConfigurationDao $configDao, Sanitize $sanitize)
    {
        $this->postDao = $postDao;
        $this->configDao = $configDao;
        $this->sanitize = $sanitize;
    }

    /**
     * Check whether scheduled posting is enabled globally.
     *
     * Defaults to enabled when the setting row is missing (fresh installs
     * and existing blogs before the Writing settings page is first saved).
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $setting = $this->configDao->findConfigByName(self::ENABLED_SETTING, $this->sanitize);

        if (empty($setting) || !isset($setting['setting_value']) || $setting['setting_value'] === '') {
            return true;
        }

        return (string)$setting['setting_value'] === '1';
    }

    /**
     * Get the earliest scheduled post_date (next-run shortcut).
     *
     * @return string|null Next run datetime ('Y-m-d H:i:s'), or null when unset
     */
    public function getNextRun(): ?string
    {
        $setting = $this->configDao->findConfigByName(self::NEXT_RUN_SETTING, $this->sanitize);

        $value = (empty($setting) || !isset($setting['setting_value'])) ? '' : (string)$setting['setting_value'];

        return $value === '' ? null : $value;
    }

    /**
     * Persist the next-run shortcut.
     *
     * @param string|null $when Next scheduled datetime, or null to clear it
     * @return void
     */
    public function setNextRun(?string $when): void
    {
        $value = $when === null ? '' : $when;

        $updated = $this->configDao->updateConfigByName(self::NEXT_RUN_SETTING, $value, $this->sanitize);

        if (!$updated) {
            $this->configDao->createConfig(self::NEXT_RUN_SETTING, $value, $this->sanitize);
        }
    }

    /**
     * Publish due posts, but check at most once per interval.
     *
     * S3: the unthrottled check costs ~6 percent of every frontend hit (E1).
     * The timestamp file keeps steady-state requests at zero queries; a due
     * post still goes public within $intervalSeconds of its date (default
     * 300 seconds, never below 60). When the directory is not writable the
     * method degrades to an unthrottled run (today's behavior).
     *
     * @param string $cacheDir Directory holding the check timestamp file.
     * @param int $intervalSeconds Minimum seconds between checks.
     * @return int Number of posts promoted to 'publish'
     */
    public function publishDuePostsThrottled($cacheDir, $intervalSeconds = 300)
    {
        $intervalSeconds = max(60, (int)$intervalSeconds);
        $stampFile = rtrim((string)$cacheDir, '/\\')
            . DIRECTORY_SEPARATOR . 'scheduled-post-check.txt';

        if (is_file($stampFile)) {
            $lastCheck = (int)@filemtime($stampFile);
            if ($lastCheck > 0 && (time() - $lastCheck) < $intervalSeconds) {
                return 0;
            }
        }

        $count = $this->publishDuePosts();

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            // M4: block web access to the throttle directory with the same
            // dual-syntax deny used by the other file caches.
            $htaccess = rtrim((string)$cacheDir, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
            if (!is_file($htaccess)) {
                @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
            }
            @touch($stampFile);
        }

        return $count;
    }

    /**
     * Publish every scheduled post whose post_date has passed.
     *
     * Short-circuits when the feature is disabled or when no post is due
     * (next-run shortcut), so steady-state requests never touch the posts
     * table. Recomputes the shortcut after each run.
     *
     * @return int Number of posts promoted to 'publish'
     * @psalm-suppress PossiblyUnusedReturnValue -- consumed by the test suite
     *     (tests/service/ScheduledPostServiceTest.php); the production caller
     *     in lib/main.php intentionally ignores it.
     */
    public function publishDuePosts(): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $now = function_exists('date_for_database') ? date_for_database() : date('Y-m-d H:i:s');
        $nextRun = $this->getNextRun();

        if ($nextRun !== null && $nextRun > $now) {
            return 0;
        }

        $count = $this->postDao->publishDueScheduledPosts($now);
        $this->setNextRun($this->postDao->nextScheduledPostDate());

        return $count;
    }
}
