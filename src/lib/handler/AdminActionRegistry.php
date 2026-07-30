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
     * Registered command instances keyed by action constant.
     *
     * @var array<string, AdminActionCommand>
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
     * Check whether a command is registered for the given action.
     *
     * @param string $action The action constant.
     * @return bool
     */
    public function has(string $action): bool
    {
        return isset($this->commands[$action]);
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
        if (!isset($this->commands[$action])) {
            throw new \RuntimeException("No command registered for action: " . $action);
        }
        $this->commands[$action]->execute($context);
    }
}
