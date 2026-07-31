<?php

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for the admin action command classes added as part of
 * the command-pattern refactor in the src/lib/handler/admin directory.
 *
 * @category Tests
 */
class AdminActionCommandStructureTest extends TestCase
{
    /**
     * Every admin command implementation keyed by domain.
     *
     * @var array<string, string[]>
     */
    private array $commandsByDomain = [
        'comment' => ['ListCommentsCmd', 'EditCommentCmd', 'DeleteCommentCmd'],
        'media'   => ['NewMediaCmd', 'ListMediaCmd', 'EditMediaCmd', 'DeleteMediaCmd'],
        'page'    => ['NewPageCmd', 'ListPagesCmd', 'EditPageCmd', 'DeletePageCmd'],
        'plugin'  => ['ListPluginsCmd', 'InstallPluginCmd', 'DeletePluginCmd', 'DeactivatePluginCmd', 'ActivatePluginCmd'],
        'post'    => ['NewPostCmd', 'ListPostsCmd', 'EditPostCmd', 'DeletePostCmd'],
        'theme'   => ['NewThemeCmd', 'ListThemesCmd', 'InstallThemeCmd', 'EditThemeCmd', 'DeleteThemeCmd', 'DeactivateThemeCmd', 'ActivateThemeCmd'],
        'topic'   => ['NewTopicCmd', 'ListTopicsCmd', 'EditTopicCmd', 'DeleteTopicCmd'],
        'user'    => ['NewUserCmd', 'ListUsersCmd', 'EditUserCmd', 'DeleteUserCmd'],
    ];

    public function testCommandFilesExist(): void
    {
        foreach ($this->commandsByDomain as $domain => $commands) {
            foreach ($commands as $command) {
                $file = __DIR__ . '/../../../src/lib/handler/admin/' . $domain . '/' . $command . '.php';
                $this->assertFileExists($file, "Command file $file does not exist");
            }
        }
    }

    public function testAllCommandsImplementAdminActionCommand(): void
    {
        $count = 0;

        foreach ($this->commandsByDomain as $domain => $commands) {
            foreach ($commands as $command) {
                $fqcn = 'Scriptlog\\Handler\\Admin\\' . ucfirst($domain) . '\\' . $command;
                $this->assertTrue(
                    class_exists($fqcn) || interface_exists($fqcn),
                    "Command class $fqcn could not be autoloaded"
                );
                $this->assertContains(
                    \Scriptlog\Handler\AdminActionCommand::class,
                    class_implements($fqcn),
                    "Command $command must implement AdminActionCommand"
                );
                $count++;
            }
        }

        $this->assertSame(35, $count, 'Expected 35 admin command classes');
    }

    public function testAllCommandsDeclareExecuteMethod(): void
    {
        foreach ($this->commandsByDomain as $domain => $commands) {
            foreach ($commands as $command) {
                $fqcn = 'Scriptlog\\Handler\\Admin\\' . ucfirst($domain) . '\\' . $command;
                $this->assertTrue(
                    method_exists($fqcn, 'execute'),
                    "Command $command must declare execute(array \$context): void"
                );
            }
        }
    }

    public function testAllCommandFilesContainSecurityGuard(): void
    {
        foreach ($this->commandsByDomain as $domain => $commands) {
            foreach ($commands as $command) {
                $file = __DIR__ . '/../../../src/lib/handler/admin/' . $domain . '/' . $command . '.php';
                $content = file_get_contents($file);
                $this->assertStringContainsString(
                    "defined('SCRIPTLOG') || die",
                    $content,
                    "Command file $command.php is missing the security guard"
                );
                $this->assertStringContainsString(
                    'implements AdminActionCommand',
                    $content,
                    "Command file $command.php must implement AdminActionCommand"
                );
            }
        }
    }

    public function testInterfaceAndRegistryExist(): void
    {
        $this->assertTrue(interface_exists(\Scriptlog\Handler\AdminActionCommand::class));
        $this->assertTrue(class_exists(\Scriptlog\Handler\AdminActionRegistry::class));
    }
}
