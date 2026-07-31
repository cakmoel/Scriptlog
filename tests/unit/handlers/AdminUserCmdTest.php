<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavioral tests for the admin user command handlers.
 *
 * @category Tests
 */
class AdminUserCmdTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new class() {
            public $authenticator;
            public $sanitizer;
            public $userDao;

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
                $this->userDao = new class() {
                    public $found = true;

                    public function checkUserId($id, $sanitize)
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
            public $profileUpdated = false;
            public $profileRemoved = false;
            public $profileShown = false;
            public $lastId = null;
            public $lastLogin = null;

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

            public function updateProfile($login)
            {
                $this->profileUpdated = true;
                $this->lastLogin = $login;
            }

            public function removeProfile($login, $authenticator)
            {
                $this->profileRemoved = true;
                $this->lastLogin = $login;
            }

            public function showProfile($login)
            {
                $this->profileShown = true;
                $this->lastLogin = $login;
            }
        };
    }

    public function testNewUserCmdCallsInsertWhenIdIsZero(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\NewUserCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 0,
            'userController' => $controller,
        ]);

        $this->assertTrue($controller->inserted);
        $this->assertFalse($controller->listed);
    }

    public function testListUsersCmdCallsListItemsWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\ListUsersCmd();

        $command->execute([
            'app' => $this->app,
            'userController' => $controller,
            'userLogin' => 'admin',
        ]);

        $this->assertTrue($controller->listed);
        $this->assertFalse($controller->profileShown);
    }

    public function testListUsersCmdShowsOwnProfileWhenUnauthorized(): void
    {
        $this->app->authenticator->allowed = false;
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\ListUsersCmd();

        $command->execute([
            'app' => $this->app,
            'userController' => $controller,
            'userLogin' => 'johndoe',
        ]);

        $this->assertTrue($controller->profileShown);
        $this->assertSame('johndoe', $controller->lastLogin);
        $this->assertFalse($controller->listed);
    }

    public function testEditUserCmdCallsUpdateWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\EditUserCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 5,
            'userController' => $controller,
            'userLogin' => 'admin',
        ]);

        $this->assertTrue($controller->updated);
        $this->assertSame(5, $controller->lastId);
    }

    public function testEditUserCmdCallsUpdateProfileWhenUnauthorized(): void
    {
        $this->app->authenticator->allowed = false;
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\EditUserCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 5,
            'userController' => $controller,
            'userLogin' => 'johndoe',
        ]);

        $this->assertTrue($controller->profileUpdated);
        $this->assertSame('johndoe', $controller->lastLogin);
        $this->assertFalse($controller->updated);
    }

    public function testDeleteUserCmdCallsRemoveWhenAuthorized(): void
    {
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\DeleteUserCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 7,
            'userController' => $controller,
            'userLogin' => 'admin',
        ]);

        $this->assertTrue($controller->removed);
        $this->assertSame(7, $controller->lastId);
    }

    public function testDeleteUserCmdCallsRemoveProfileWhenUnauthorized(): void
    {
        $this->app->authenticator->allowed = false;
        $controller = $this->makeController();
        $command = new Scriptlog\Handler\Admin\User\DeleteUserCmd();

        $command->execute([
            'app' => $this->app,
            'id' => 7,
            'userController' => $controller,
            'userLogin' => 'johndoe',
        ]);

        $this->assertTrue($controller->profileRemoved);
        $this->assertSame('johndoe', $controller->lastLogin);
        $this->assertFalse($controller->removed);
    }
}
