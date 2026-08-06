<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin page command handlers.
 *
 * @category Tests
 */
class AdminPageCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $pageDao;

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
                $this->pageDao = new class() {
                    public $found = true;

                    public function checkPageId($id, $sanitize)
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
        };
    }

    public function testNewPageCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Page\NewPageCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'pageController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
    }

    public function testListPagesCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Page\ListPagesCmd();

        $command->execute([
            'app' => $this->app,
            'pageController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testEditPageCmdCallsUpdateWhenPageExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Page\EditPageCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 6,
            'pageDao' => $this->app->pageDao,
            'pageController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(6, $controller->lastId);
    }

    public function testDeletePageCmdCallsRemoveWhenPageExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Page\DeletePageCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 8,
            'pageDao' => $this->app->pageDao,
            'pageController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(8, $controller->lastId);
    }
}
