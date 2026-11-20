<?php

namespace Scriptlog\Handler;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Core\ActionConst;

/**
 * Registry of admin action commands.
 *
 * Maps action constants (ActionConst::EDITCOMMENT, etc.) to their
 * corresponding command implementations. Provides a unified dispatch
 * point that replaces switch statements in admin CRUD files.
 */
class AdminActionRegistry
{
    /**
     * Registered commands keyed by action constant.
     *
     * A value is either a ready-made command instance (eager registration) or
     * the class name of a command that is instantiated on first use (lazy
     * registration).
     *
     * @var array<string, AdminActionCommand|string>
     */
    private array $commands = [];

    /**
     * Register a command for the given action.
     *
     * @param string             $action  The action constant (e.g. ActionConst::EDITCOMMENT).
     * @param AdminActionCommand $command The command implementation.
     * @return void
     */
    public function register(string $action, AdminActionCommand $command): void
    {
        $this->commands[$action] = $command;
    }

    /**
     * Register a command class for the given action, to be instantiated on use.
     *
     * Frontend requests never dispatch admin actions, so constructing every
     * command at bootstrap (Bootstrap::buildAdminActionRegistry()) loaded ~35
     * classes for nothing. Registering the class name instead defers both the
     * autoload and the constructor to the first has()/execute() call.
     *
     * @param string $action       The action constant.
     * @param string $commandClass Fully qualified or aliased command class name.
     * @return void
     */
    public function registerClass(string $action, string $commandClass): void
    {
        $this->commands[$action] = $commandClass;
    }

    /**
     * Check whether a command is registered for the given action.
     *
     * For lazily registered commands this resolves the class, which preserves
     * the old eager `class_exists()` guard: an action whose implementation is
     * missing is reported as not registered instead of failing at execute().
     *
     * @param string $action The action constant.
     * @return bool
     */
    public function has(string $action): bool
    {
        if (!isset($this->commands[$action])) {
            return false;
        }

        $command = $this->commands[$action];

        if ($command instanceof AdminActionCommand) {
            return true;
        }

        if (!class_exists($command)) {
            unset($this->commands[$action]);

            return false;
        }

        return true;
    }

    /**
     * Execute the command registered for the given action.
     *
     * @param string $action  The action constant.
     * @param array  $context Execution context (app, id, daos, controllers).
     * @return void
     * @throws \RuntimeException When no command is registered for the action.
     */
    public function execute(string $action, array $context): void
    {
        if (!$this->has($action)) {
            throw new \RuntimeException("No command registered for action: " . $action);
        }

        $command = $this->commands[$action];

        if (is_string($command)) {
            $command = new $command();
            $this->commands[$action] = $command;
        }

        $command->execute($context);
    }
}
