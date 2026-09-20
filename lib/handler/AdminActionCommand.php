<?php

namespace Scriptlog\Handler;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Contract for admin action commands.
 *
 * Each concrete command encapsulates the logic for a single
 * admin CRUD action (create, edit, delete, list).
 */
interface AdminActionCommand
{
    /**
     * Execute the command with the given context.
     *
     * Context array typically contains:
     *  - 'app': AppContext instance
     *  - 'id': entity identifier (int)
     *  - '{resource}Dao': resource-specific DAO
     *  - '{resource}Controller': resource-specific controller
     *
     * @param array $context
     * @return void
     */
    public function execute(array $context): void;
}
