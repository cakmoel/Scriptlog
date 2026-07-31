<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin comment command handlers.
 *
 * @category Tests
 */
class AdminCommentCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $commentDao;

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
                $this->commentDao = new class() {
                    public $found = true;

                    public function checkCommentId($id, $sanitize)
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
            public $updated = false;
            public $removed = false;
            public $lastId = null;

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

    public function testListCommentsCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Comment\ListCommentsCmd();

        $command->execute([
            'app' => $this->app,
            'commentController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testEditCommentCmdCallsUpdateWhenCommentExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Comment\EditCommentCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 16,
            'commentDao' => $this->app->commentDao,
            'commentController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(16, $controller->lastId);
    }

    public function testDeleteCommentCmdCallsRemoveWhenCommentExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Comment\DeleteCommentCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 17,
            'commentDao' => $this->app->commentDao,
            'commentController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(17, $controller->lastId);
    }
}
