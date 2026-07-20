<?php

use PHPUnit\Framework\TestCase;

class TurnOnSessionFunctionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('turn_on_session')) {
            require_once __DIR__ . '/../../src/lib/utility/turn-on-session.php';
        }
    }

    public function testTurnOnSessionFunctionExists(): void
    {
        $this->assertTrue(function_exists('turn_on_session'));
    }

    public function testTurnOnSessionReturnsFalseForInvalidHandler(): void
    {
        $result = turn_on_session(new stdClass(), 3600, 'test', '/', '', false, true);
        $this->assertFalse($result);
    }

    public function testTurnOnSessionSourceUsesSessionMaker(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/turn-on-session.php');
        $this->assertStringContainsString('SessionMaker', $source);
    }

    public function testTurnOnSessionHasErrorHandling(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/turn-on-session.php');
        $this->assertStringContainsString('catch', $source);
        $this->assertStringContainsString('error_log', $source);
    }

    public function testTurnOnSessionHasTimeoutProtection(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/turn-on-session.php');
        $this->assertStringContainsString('set_time_limit', $source);
    }

    public function testTurnOnSessionHasSessionValidityCheck(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/turn-on-session.php');
        $this->assertStringContainsString('isValid', $source);
    }
}
