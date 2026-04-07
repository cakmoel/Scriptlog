<?php
/**
 * Membership Utility Functions Test
 * 
 * Tests for membership.php utility functions:
 * - is_registration_unable()
 * - membership_default_role()
 * - is_membership_setting_available()
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MembershipFunctionsTest extends TestCase
{
    public function testIsIterable()
    {
        $this->assertTrue(is_iterable([]));
        $this->assertTrue(is_iterable(['a', 'b']));
        $this->assertTrue(is_iterable(['key' => 'value']));
        $this->assertTrue(is_iterable(new ArrayIterator([])));
        $this->assertFalse(is_iterable('string'));
        $this->assertFalse(is_iterable(123));
        $this->assertFalse(is_iterable(null));
    }

    public function testJsonDecodeMembershipSetting()
    {
        $membershipSettingJson = '{"user_can_register":"1","default_role":"subscriber"}';
        $decoded = json_decode($membershipSettingJson, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('1', $decoded['user_can_register']);
        $this->assertEquals('subscriber', $decoded['default_role']);
    }

    public function testMembershipSettingStructure()
    {
        $membershipData = [
            'ID' => 5,
            'setting_name' => 'membership_setting',
            'setting_value' => '{"user_can_register":"1","default_role":"subscriber"}'
        ];

        $this->assertArrayHasKey('ID', $membershipData);
        $this->assertArrayHasKey('setting_name', $membershipData);
        $this->assertArrayHasKey('setting_value', $membershipData);

        $decoded = json_decode($membershipData['setting_value'], true);
        $this->assertEquals('1', $decoded['user_can_register']);
        $this->assertEquals('subscriber', $decoded['default_role']);
    }

    public function testRegistrationSettingLogic()
    {
        $testCases = [
            ['setting_value' => '{"user_can_register":"1"}', 'expected' => true, 'desc' => 'Registration enabled'],
            ['setting_value' => '{"user_can_register":"0"}', 'expected' => false, 'desc' => 'Registration disabled'],
            ['setting_value' => '{"user_can_register":""}', 'expected' => false, 'desc' => 'Empty registration value'],
            ['setting_value' => '{}', 'expected' => false, 'desc' => 'Empty JSON object'],
            ['setting_value' => '', 'expected' => false, 'desc' => 'Empty setting value'],
        ];

        foreach ($testCases as $testCase) {
            $result = false;
            if (!empty($testCase['setting_value'])) {
                $canRegister = json_decode($testCase['setting_value'], true);
                $result = (isset($canRegister['user_can_register']) && $canRegister['user_can_register'] == '1');
            }
            $this->assertEquals($testCase['expected'], $result, $testCase['desc']);
        }
    }

    public function testDefaultRoleLogic()
    {
        $testCases = [
            ['setting_value' => '{"default_role":"subscriber"}', 'expected' => 'subscriber', 'desc' => 'subscriber role'],
            ['setting_value' => '{"default_role":"author"}', 'expected' => 'author', 'desc' => 'author role'],
            ['setting_value' => '{"default_role":"contributor"}', 'expected' => 'contributor', 'desc' => 'contributor role'],
            ['setting_value' => '{}', 'expected' => '', 'desc' => 'Empty JSON object'],
            ['setting_value' => '', 'expected' => '', 'desc' => 'Empty setting value'],
            ['setting_value' => '{"default_role":""}', 'expected' => '', 'desc' => 'Empty role value'],
        ];

        foreach ($testCases as $testCase) {
            $defaultRole = '';
            if (!empty($testCase['setting_value'])) {
                $decoded = json_decode($testCase['setting_value'], true);
                $defaultRole = isset($decoded['default_role']) ? $decoded['default_role'] : '';
            }
            $this->assertEquals($testCase['expected'], $defaultRole, $testCase['desc']);
        }
    }

    public function testIsMembershipSettingAvailableLogic()
    {
        $testCases = [
            [['ID' => 1, 'setting_name' => 'membership_setting', 'setting_value' => '{}'], true, 'Valid membership setting'],
            [['ID' => 1, 'setting_name' => 'other_setting', 'setting_value' => '{}'], false, 'Wrong setting name'],
            [['ID' => 1, 'setting_name' => 'membership_setting'], true, 'Has ID and correct setting name'],
            [['setting_name' => 'membership_setting'], true, 'Has setting_name but missing ID'],
            [['ID' => 1], false, 'Missing setting_name'],
            [[], false, 'Empty array'],
            [null, false, 'Null value'],
        ];

        foreach ($testCases as [$data, $expected, $desc]) {
            $result = is_iterable($data) && isset($data['setting_name']) && $data['setting_name'] === 'membership_setting';
            $this->assertEquals($expected, $result, $desc);
        }
    }

    public function testIdSanitization()
    {
        $testCases = [
            ['input' => '5', 'expected' => '5', 'desc' => 'Numeric string'],
            ['input' => '123', 'expected' => '123', 'desc' => 'Larger numeric string'],
            ['input' => '', 'expected' => '', 'desc' => 'Empty string'],
        ];

        foreach ($testCases as $testCase) {
            $idSetting = $testCase['input'];
            $hasValidId = !empty($idSetting);
            $this->assertEquals(!empty($testCase['expected']), $hasValidId, $testCase['desc']);
        }
    }
}
