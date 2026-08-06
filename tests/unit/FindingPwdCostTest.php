<?php

defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * FindingPwdCost Tests
 *
 * Covers the finding_pwd_cost() helper which benchmarks the server to pick a
 * BCRYPT cost. The function now coerces its arguments to float/int so string
 * inputs (e.g. from configuration files or forms) cannot corrupt the loop.
 */
class FindingPwdCostTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('finding_pwd_cost')) {
            require_once __DIR__ . '/../../src/lib/utility/finding-pwd-cost.php';
        }
    }

    public function testReturnsIntForFloatAndIntInput(): void
    {
        $result = finding_pwd_cost(0.001, 10);
        $this->assertIsInt($result);
        $this->assertGreaterThan(10, $result);
    }

    public function testCoercesNumericStringInputs(): void
    {
        $result = finding_pwd_cost('0.001', '10');
        $this->assertIsInt($result);
        $this->assertGreaterThan(10, $result);
    }

    public function testZeroTargetStopsAfterFirstIteration(): void
    {
        $result = finding_pwd_cost(0.0, 8);
        $this->assertSame(9, $result);
    }
}
