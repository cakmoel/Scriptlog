<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin post command handlers.
 *
 * @category Tests
 */
class AdminPostCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $postDao;

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
                $this->postDao = new class() {
                    public $found = true;

                    public function checkPostId($id, $sanitize)
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

    public function testNewPostCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Post\NewPostCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'postController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
    }

    public function testListPostsCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Post\ListPostsCmd();

        $command->execute([
            'app' => $this->app,
            'postController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testEditPostCmdCallsUpdateWhenPostExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Post\EditPostCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 9,
            'postDao' => $this->app->postDao,
            'postController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(9, $controller->lastId);
    }

    public function testDeletePostCmdCallsRemoveWhenPostExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Post\DeletePostCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 10,
            'postDao' => $this->app->postDao,
            'postController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(10, $controller->lastId);
    }
}
