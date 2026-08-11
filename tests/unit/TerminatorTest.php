<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * Terminator Test
 *
 * Covers terminator() behaviour changed in v1.6.0: the function now returns
 * false instead of emitting headers/exit when the caller is not authorized,
 * making it safe to unit test and reuse as a plain boolean helper.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TerminatorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('terminator')) {
            require_once __DIR__ . '/../../src/lib/utility/terminator.php';
        }

        unset($_COOKIE['scriptlog_auth']);
        if (isset($_SESSION['scriptlog_session_level'])) {
            unset($_SESSION['scriptlog_session_level']);
        }
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['scriptlog_auth']);
        if (isset($_SESSION['scriptlog_session_level'])) {
            unset($_SESSION['scriptlog_session_level']);
        }
    }

    public function testTerminatorReturnsFalseWhenNoPrivilegeAvailable(): void
    {
        $this->assertFalse(terminator(1));
    }

    public function testTerminatorReturnsFalseForUnknownPrivilege(): void
    {
        \Scriptlog\Core\Session::getInstance()->scriptlog_session_level = 'guest';
        $this->assertFalse(terminator(1));
    }

    public function testTerminatorAcceptsAdministratorPrivilege(): void
    {
        \Scriptlog\Core\Session::getInstance()->scriptlog_session_level = 'administrator';

        $result = terminator(1);
        $this->assertIsBool($result);
    }

    public function testTerminatorAcceptsSubscriberPrivilege(): void
    {
        \Scriptlog\Core\Session::getInstance()->scriptlog_session_level = 'subscriber';

        $result = terminator(1);
        $this->assertIsBool($result);
    }
}
