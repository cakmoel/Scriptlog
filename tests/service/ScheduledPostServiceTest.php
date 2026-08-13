<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * ScheduledPostServiceTest
 *
 * Unit tests for Scriptlog\Service\ScheduledPostService using mocked
 * PostDao / ConfigurationDao / Sanitize collaborators so no database is
 * required.
 *
 * @category Tests
 * @version  1.0
 */
class ScheduledPostServiceTest extends TestCase
{
    private $postDao;
    private $configDao;
    private $sanitize;
    private $service;

    protected function setUp(): void
    {
        $this->postDao = $this->createMock(\Scriptlog\Dao\PostDao::class);
        $this->configDao = $this->createMock(\Scriptlog\Dao\ConfigurationDao::class);
        $this->sanitize = $this->createMock(\Scriptlog\Core\Sanitize::class);

        $this->service = new \Scriptlog\Service\ScheduledPostService(
            $this->postDao,
            $this->configDao,
            $this->sanitize
        );
    }

    public function testConstructorAcceptsDaoCollaborators(): void
    {
        $this->assertInstanceOf(
            \Scriptlog\Service\ScheduledPostService::class,
            $this->service
        );
    }

    // -----------------------------------------------------------------------
    // isEnabled()
    // -----------------------------------------------------------------------

    public function testIsEnabledDefaultsTrueWhenSettingMissing(): void
    {
        $this->configDao->method('findConfigByName')->willReturn([]);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledDefaultsTrueWhenSettingValueEmpty(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => '']);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledTrueWhenSettingEnabled(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => '1']);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledFalseWhenSettingDisabled(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => '0']);

        $this->assertFalse($this->service->isEnabled());
    }

    // -----------------------------------------------------------------------
    // getNextRun() / setNextRun()
    // -----------------------------------------------------------------------

    public function testGetNextRunReturnsNullWhenSettingMissing(): void
    {
        $this->configDao->method('findConfigByName')->willReturn([]);

        $this->assertNull($this->service->getNextRun());
    }

    public function testGetNextRunReturnsNullWhenSettingEmpty(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => '']);

        $this->assertNull($this->service->getNextRun());
    }

    public function testGetNextRunReturnsStoredValue(): void
    {
        $when = date('Y-m-d H:i:s', time() + 3600);
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => $when]);

        $this->assertSame($when, $this->service->getNextRun());
    }

    public function testSetNextRunUpdatesExistingSetting(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with('writing_scheduled_next_run', '2026-08-14 09:00:00', $this->sanitize)
            ->willReturn(true);

        $this->configDao->expects($this->never())->method('createConfig');

        $this->service->setNextRun('2026-08-14 09:00:00');
    }

    public function testSetNextRunCreatesSettingWhenUpdateFails(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with('writing_scheduled_next_run', '2026-08-14 09:00:00', $this->sanitize)
            ->willReturn(false);

        $this->configDao->expects($this->once())
            ->method('createConfig')
            ->with('writing_scheduled_next_run', '2026-08-14 09:00:00', $this->sanitize);

        $this->service->setNextRun('2026-08-14 09:00:00');
    }

    public function testSetNextRunClearsSettingWhenNull(): void
    {
        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with('writing_scheduled_next_run', '', $this->sanitize)
            ->willReturn(true);

        $this->configDao->expects($this->never())->method('createConfig');

        $this->service->setNextRun(null);
    }

    // -----------------------------------------------------------------------
    // publishDuePosts()
    // -----------------------------------------------------------------------

    public function testPublishDuePostsReturnsZeroWhenDisabled(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturn(['setting_value' => '0']);

        $this->postDao->expects($this->never())->method('publishDueScheduledPosts');

        $this->assertSame(0, $this->service->publishDuePosts());
    }

    public function testPublishDuePostsReturnsZeroWhenNextRunInFuture(): void
    {
        $future = date('Y-m-d H:i:s', time() + 7200);
        $this->configDao->method('findConfigByName')
            ->willReturnCallback(static function (string $name) use ($future) {
                if ($name === 'writing_scheduled_post_enabled') {
                    return ['setting_value' => '1'];
                }
                return ['setting_value' => $future];
            });

        $this->postDao->expects($this->never())->method('publishDueScheduledPosts');

        $this->assertSame(0, $this->service->publishDuePosts());
    }

    public function testPublishDuePostsPromotesDuePostsAndRefreshesNextRun(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturnCallback(static function (string $name) {
                if ($name === 'writing_scheduled_post_enabled') {
                    return ['setting_value' => '1'];
                }
                return ['setting_value' => ''];
            });

        $this->postDao->expects($this->once())
            ->method('publishDueScheduledPosts')
            ->willReturn(2);

        $this->postDao->expects($this->once())
            ->method('nextScheduledPostDate')
            ->willReturn('2026-09-01 08:00:00');

        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with('writing_scheduled_next_run', '2026-09-01 08:00:00', $this->sanitize)
            ->willReturn(true);

        $this->assertSame(2, $this->service->publishDuePosts());
    }

    public function testPublishDuePostsRefreshesNextRunToNullWhenNoneLeft(): void
    {
        $this->configDao->method('findConfigByName')
            ->willReturnCallback(static function (string $name) {
                if ($name === 'writing_scheduled_post_enabled') {
                    return ['setting_value' => '1'];
                }
                return ['setting_value' => ''];
            });

        $this->postDao->expects($this->once())
            ->method('publishDueScheduledPosts')
            ->willReturn(1);

        $this->postDao->expects($this->once())
            ->method('nextScheduledPostDate')
            ->willReturn(null);

        $this->configDao->expects($this->once())
            ->method('updateConfigByName')
            ->with('writing_scheduled_next_run', '', $this->sanitize)
            ->willReturn(true);

        $this->assertSame(1, $this->service->publishDuePosts());
    }
}
