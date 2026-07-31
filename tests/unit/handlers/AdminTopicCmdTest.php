<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin topic command handlers.
 *
 * @category Tests
 */
class AdminTopicCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $topicDao;

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
                $this->topicDao = new class() {
                    public $found = true;

                    public function checkTopicId($id, $sanitize)
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

    public function testNewTopicCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Topic\NewTopicCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'topicController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
    }

    public function testListTopicsCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Topic\ListTopicsCmd();

        $command->execute([
            'app' => $this->app,
            'topicController' => $controller,
        ]);

        $this->assertTrue($controller->listed);
    }

    public function testEditTopicCmdCallsUpdateWhenTopicExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Topic\EditTopicCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 3,
            'topicDao' => $this->app->topicDao,
            'topicController' => $controller,
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(3, $controller->lastId);
    }

    public function testDeleteTopicCmdCallsRemoveWhenTopicExists(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\Topic\DeleteTopicCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 4,
            'topicDao' => $this->app->topicDao,
            'topicController' => $controller,
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(4, $controller->lastId);
    }
}
