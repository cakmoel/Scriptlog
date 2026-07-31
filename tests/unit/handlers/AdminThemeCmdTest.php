<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin theme command handlers.
 *
 * @category Tests
 */
class AdminThemeCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $themeDao;

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
                $this->themeDao = new class() {
                    public $found = true;

                    public function checkThemeId($id, $sanitize)
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
            public $inserted = false;
            public $listed = false;
            public $updated = false;
            public $removed = false;
            public $enabled = false;
            public $disabled = false;
            public $setup = false;
            public $lastId = null;

            public function insert()
            {
                $this->inserted = true;
            }

            public function listItems()
            {
                $this->listed = true;
            }

            public function update($id)
            {
                $this->updated = true;
                $this->lastId = $id;
            }

            public function remove($id)
            {
                $this->removed = true;
                $this->lastId = $id;
            }

            public function enableTheme($id)
            {
                $this->enabled = true;
                $this->lastId = $id;
            }

            public function disableTheme($id)
            {
                $this->disabled = true;
                $this->lastId = $id;
            }

            public function setupTheme()
            {
                $this->setup = true;
            }
        };
    }

    public function testNewThemeCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\NewThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
    }

    public function testListThemesCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\ListThemesCmd();

        $command->execute([
            'app' => $this->app,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testInstallThemeCmdCallsSetupThemeWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\InstallThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->setup);
    }

    public function testEditThemeCmdCallsUpdateWhenThemeExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\EditThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 2,
            'themeDao' => $this->app->themeDao,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(2, $controller->lastId);
    }

    public function testDeleteThemeCmdCallsRemoveWhenThemeExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\DeleteThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 3,
            'themeDao' => $this->app->themeDao,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(3, $controller->lastId);
    }

    public function testActivateThemeCmdCallsEnableThemeWhenThemeExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\ActivateThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 4,
            'themeDao' => $this->app->themeDao,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->enabled);
        $this->assertSame(4, $controller->lastId);
    }

    public function testDeactivateThemeCmdCallsDisableThemeWhenThemeExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Theme\DeactivateThemeCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 5,
            'themeDao' => $this->app->themeDao,
            'themeController' => $controller,
        ]);

        $this->assertTrue($controller->disabled);
        $this->assertSame(5, $controller->lastId);
    }
}
