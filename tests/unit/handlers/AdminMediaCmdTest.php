<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin media command handlers.
 *
 * @category Tests
 */
class AdminMediaCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $mediaDao;

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
                $this->mediaDao = new class() {
                    public $found = true;

                    public function checkMediaId($id, $sanitize)
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

    public function testNewMediaCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Media\NewMediaCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'mediaController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
    }

    public function testListMediaCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Media\ListMediaCmd();

        $command->execute([
            'app' => $this->app,
            'mediaController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testEditMediaCmdCallsUpdateWhenMediaExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Media\EditMediaCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 14,
            'mediaDao' => $this->app->mediaDao,
            'mediaController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(14, $controller->lastId);
    }

    public function testDeleteMediaCmdCallsRemoveWhenMediaExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Media\DeleteMediaCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 15,
            'mediaDao' => $this->app->mediaDao,
            'mediaController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(15, $controller->lastId);
    }
}
