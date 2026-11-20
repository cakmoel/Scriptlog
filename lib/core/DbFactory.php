<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * DbFactory Class
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

class DbFactory
{
    /**
     * Connect
     *
     * Builds the database wrapper from a PDO configuration tuple:
     * `[dsn, username, password]`.
     *
     * @param array<int, string> $connection Database configuration tuple: DSN, user, password.
     * @param array<int|string, mixed> $options Additional PDO driver options.
     * @return Db|null Database wrapper, or null when connection setup failed.
     * @throws DbException When the database object is not available.
     *
     */
    public static function connect(array $connection, array $options = [])
    {

        try {
            $database = "Db";

            if (!class_exists($database)) {
                throw new DbException("Database object does not exists");
            }

            return new $database($connection, $options);
        } catch (\Throwable $th) {
            LogError::setStatusCode(http_response_code(500));
            LogError::exceptionHandler($th);
        } catch (DbException $e) {
            LogError::setStatusCode(http_response_code(500));
            LogError::exceptionHandler($e);
        }

        return null;
    }
}
