<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin plugin command handlers.
 *
 * @category Tests
 */
class AdminPluginCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $pluginDao;

            public function __construct()
            {
                $this->authenticator = new class() {
                    public $allowed = true;

                    public function userAccessControl($control = null)
                    {
                        return $this->allowed;
                    }
                };
                $this->sanitizer = new class() {
                    public function sanitize($value)
                    {
                        return $value;
                    }
                };
                $this->pluginDao = new class() {
                    public $found = true;

                    public function checkPluginId($id, $sanitize)
                    {
                        return $this->found;
                    }
                };
            }
        };
    }

    private function makeController(): object
    {
        return new class() {
            public $listed = false;
            public $removed = false;
            public $installed = false;
            public $enabled = false;
            public $disabled = false;
            public $lastId = null;

            public function listItems()
            {
                $this->listed = true;
            }

            public function remove($id)
            {
                $this->removed = true;
                $this->lastId = $id;
            }

            public function installPlugin()
            {
                $this->installed = true;
            }

            public function enablePlugin($id)
            {
                $this->enabled = true;
                $this->lastId = $id;
            }

            public function disablePlugin($id)
            {
                $this->disabled = true;
                $this->lastId = $id;
            }
        };
    }

    public function testListPluginsCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Plugin\ListPluginsCmd();

        $command->execute([
            'app' => $this->app,
            'pluginController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testInstallPluginCmdCallsInstallWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Plugin\InstallPluginCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'pluginController' => $controller,
        ]);

        $this->assertTrue($controller->installed);
    }

    public function testActivatePluginCmdCallsEnablePluginWhenPluginExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Plugin\ActivatePluginCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 11,
            'pluginDao' => $this->app->pluginDao,
            'pluginController' => $controller,
        ]);

        $this->assertTrue($controller->enabled);
        $this->assertSame(11, $controller->lastId);
    }

    public function testDeactivatePluginCmdCallsDisablePluginWhenPluginExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Plugin\DeactivatePluginCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 12,
            'pluginDao' => $this->app->pluginDao,
            'pluginController' => $controller,
        ]);

        $this->assertTrue($controller->disabled);
        $this->assertSame(12, $controller->lastId);
    }

    public function testDeletePluginCmdCallsRemoveWhenPluginExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Plugin\DeletePluginCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 13,
            'pluginDao' => $this->app->pluginDao,
            'pluginController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(13, $controller->lastId);
    }
}
