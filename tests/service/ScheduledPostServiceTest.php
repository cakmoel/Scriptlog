<?php

namespace Scriptlog\Service {

    /**
     * Stub for the DB-backed global date_for_database().
     *
     * The service resolves the unqualified call to this namespaced function
     * first (PHP namespace function fallback), so unit tests never touch the
     * database through app_info()/timezone_identifier().
     *
     * @param string|null $date
     * @return string
     */
    function date_for_database($date = null)
    {
        return $date === null ? '2026-01-01 12:00:00' : (string)$date;
    }
}

namespace {

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\ConfigurationDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Service\ScheduledPostService;

/**
 * Unit tests for ScheduledPostService.
 *
 * Covers the enable/disable setting, the next-run shortcut and the
 * per-request promotion of due scheduled posts. All DAO collaborators are
 * mocked; the service must short-circuit to zero work when the feature is
 * disabled or no post is due yet.
 *
 * @coversDefaultClass Scriptlog\Service\ScheduledPostService
 */
class ScheduledPostServiceTest extends TestCase
{
    private const ENABLED_SETTING = 'writing_scheduled_post_enabled';
    private const NEXT_RUN_SETTING = 'writing_scheduled_next_run';

    /**
     * Mocked PostDao instance.
     *
     * @var PostDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $postDao;

    /**
     * Mocked ConfigurationDao instance.
     *
     * @var ConfigurationDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $configDao;

    /**
     * Mocked Sanitize instance.
     *
     * @var Sanitize&\PHPUnit\Framework\MockObject\MockObject
     */
    private $sanitizer;

    /**
     * Service under test.
     *
     * @var ScheduledPostService
     */
    private $service;

    /**
     * Build the service with mocked collaborators.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', 'test');
        }

        $this->postDao = $this->createMock(PostDao::class);
        $this->configDao = $this->createMock(ConfigurationDao::class);
        $this->sanitizer = $this->createMock(Sanitize::class);

        $this->service = new ScheduledPostService(
            $this->postDao,
            $this->configDao,
            $this->sanitizer
        );
    }

    /**
     * Stub findConfigByName() to return per-setting values.
     *
     * @param array $settings Map of setting name => setting_value (or false for missing)
     * @return void
     */
    private function stubSettings(array $settings): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturnCallback(function ($name) use ($settings) {
                $value = $settings[$name] ?? null;

                if ($value === null) {
                    return false;
                }

                return ['setting_name' => $name, 'setting_value' => $value];
            });
    }

    /**
     * isEnabled() defaults to true when the setting row is missing.
     *
     * @return void
     */
    public function testIsEnabledDefaultsTrueWhenSettingMissing(): void
    {
        $this->stubSettings([]);

        $this->assertTrue($this->service->isEnabled());
    }

    /**
     * isEnabled() returns true when the setting value is '1'.
     *
     * @return void
     */
    public function testIsEnabledTrueForEnabledValue(): void
    {
        $this->stubSettings([self::ENABLED_SETTING => '1']);

        $this->assertTrue($this->service->isEnabled());
    }

    /**
     * isEnabled() returns false when the setting value is '0'.
     *
     * @return void
     */
    public function testIsEnabledFalseForDisabledValue(): void
    {
        $this->stubSettings([self::ENABLED_SETTING => '0']);

        $this->assertFalse($this->service->isEnabled());
    }

    /**
     * getNextRun() returns null when the shortcut setting is missing.
     *
     * @return void
     */
    public function testGetNextRunReturnsNullWhenUnset(): void
    {
        $this->stubSettings([]);

        $this->assertNull($this->service->getNextRun());
    }

    /**
     * getNextRun() returns the stored datetime when present.
     *
     * @return void
     */
    public function testGetNextRunReturnsStoredValue(): void
    {
        $this->stubSettings([self::NEXT_RUN_SETTING => '2026-09-01 10:00:00']);

        $this->assertSame('2026-09-01 10:00:00', $this->service->getNextRun());
    }

    /**
     * setNextRun() delegates to updateConfigByName() with the new value.
     *
     * @return void
     */
    public function testSetNextRunUpdatesExistingSetting(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with(self::NEXT_RUN_SETTING, '2026-09-01 10:00:00', $this->sanitizer)
            ->willReturn(true);

        $this->configDao->expects($this->never())
            ->method('createConfig');

        $this->service->setNextRun('2026-09-01 10:00:00');
    }

    /**
     * setNextRun() creates the setting when the update targets a missing row.
     *
     * @return void
     */
    public function testSetNextRunCreatesSettingWhenMissing(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with(self::NEXT_RUN_SETTING, '2026-09-01 10:00:00', $this->sanitizer)
            ->willReturn(false);

        $this->configDao->expects($this->once())
            ->method('createConfig')
            ->with(self::NEXT_RUN_SETTING, '2026-09-01 10:00:00', $this->sanitizer);

        $this->service->setNextRun('2026-09-01 10:00:00');
    }

    /**
     * setNextRun(null) clears the shortcut by persisting an empty value.
     *
     * @return void
     */
    public function testSetNextRunNullClearsSetting(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with(self::NEXT_RUN_SETTING, '', $this->sanitizer)
            ->willReturn(true);

        $this->service->setNextRun(null);
    }

    /**
     * publishDuePosts() does no work when the feature is disabled.
     *
     * @return void
     */
    public function testPublishDuePostsShortCircuitsWhenDisabled(): void
    {
        $this->stubSettings([self::ENABLED_SETTING => '0']);

        $this->postDao->expects($this->never())
            ->method('publishDueScheduledPosts');

        $this->assertSame(0, $this->service->publishDuePosts());
    }

    /**
     * publishDuePosts() does no work when the next-run shortcut is in the future.
     *
     * @return void
     */
    public function testPublishDuePostsShortCircuitsWhenNextRunInFuture(): void
    {
        $this->stubSettings([
            self::ENABLED_SETTING => '1',
            self::NEXT_RUN_SETTING => '2999-12-31 23:59:59',
        ]);

        $this->postDao->expects($this->never())
            ->method('publishDueScheduledPosts');

        $this->assertSame(0, $this->service->publishDuePosts());
    }

    /**
     * publishDuePosts() promotes due posts through the DAO and recomputes the
     * next-run shortcut, returning the number of promoted posts.
     *
     * @return void
     */
    public function testPublishDuePostsDelegatesToDaoAndRecomputesNextRun(): void
    {
        $this->stubSettings([self::ENABLED_SETTING => '1']);

        $this->postDao->expects($this->once())
            ->method('publishDueScheduledPosts')
            ->with($this->matchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'))
            ->willReturn(2);

        $this->postDao->expects($this->once())
            ->method('nextScheduledPostDate')
            ->willReturn('2026-10-01 08:00:00');

        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with(self::NEXT_RUN_SETTING, '2026-10-01 08:00:00', $this->sanitizer)
            ->willReturn(true);

        $this->assertSame(2, $this->service->publishDuePosts());
    }
}

}
