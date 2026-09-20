<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/utility/download-settings.php';

class DownloadSettingsTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('DownloadSettings'));
    }

    public function testDefaultMimeTypesContainsPdf(): void
    {
        $types = DownloadSettings::getAllowedMimeTypes();
        $this->assertContains('application/pdf', $types);
    }

    public function testDefaultMimeTypesContainsDocx(): void
    {
        $types = DownloadSettings::getAllowedMimeTypes();
        $this->assertContains('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $types);
    }

    public function testDefaultMimeTypesIsArray(): void
    {
        $this->assertIsArray(DownloadSettings::getAllowedMimeTypes());
    }

    public function testDefaultMimeTypesNotEmpty(): void
    {
        $this->assertNotEmpty(DownloadSettings::getAllowedMimeTypes());
    }

    public function testDefaultExpiryHours(): void
    {
        $this->assertEquals(8, DownloadSettings::getDownloadExpiry());
    }

    public function testDefaultExpiryConstant(): void
    {
        $this->assertEquals(8, DownloadSettings::DEFAULT_EXPIRY_HOURS);
    }

    public function testDefaultHotlinkProtectionDisabled(): void
    {
        $this->assertFalse(DownloadSettings::isHotlinkProtectionEnabled());
    }

    public function testDefaultAllowedDomainsEmpty(): void
    {
        $this->assertIsArray(DownloadSettings::getAllowedDomains());
        $this->assertEmpty(DownloadSettings::getAllowedDomains());
    }

    public function testDefaultSupportUrlEmpty(): void
    {
        $this->assertEquals('', DownloadSettings::getSupportUrl());
    }

    public function testDefaultSupportLabel(): void
    {
        $this->assertEquals('Support', DownloadSettings::getSupportLabel());
    }

    public function testGetAllSettingsReturnsArray(): void
    {
        $settings = DownloadSettings::getAllSettings();
        $this->assertIsArray($settings);
    }

    public function testGetAllSettingsHasExpectedKeys(): void
    {
        $settings = DownloadSettings::getAllSettings();
        $expectedKeys = [
            'allowed_mime_types', 'expiry_hours', 'hotlink_protection',
            'allowed_domains', 'support_url', 'support_label'
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $settings);
        }
    }

    public function testGetAllSettingsReturnsDefaults(): void
    {
        $settings = DownloadSettings::getAllSettings();
        $this->assertIsArray($settings['allowed_mime_types']);
        $this->assertEquals(8, $settings['expiry_hours']);
        $this->assertFalse($settings['hotlink_protection']);
        $this->assertIsArray($settings['allowed_domains']);
        $this->assertEquals('', $settings['support_url']);
        $this->assertEquals('Support', $settings['support_label']);
    }

    public function testConstantsDefined(): void
    {
        $this->assertNotEmpty(DownloadSettings::KEY_ALLOWED_MIME_TYPES);
        $this->assertNotEmpty(DownloadSettings::KEY_EXPIRY_HOURS);
        $this->assertNotEmpty(DownloadSettings::KEY_HOTLINK_PROTECTION);
        $this->assertNotEmpty(DownloadSettings::KEY_ALLOWED_DOMAINS);
        $this->assertNotEmpty(DownloadSettings::KEY_SUPPORT_URL);
        $this->assertNotEmpty(DownloadSettings::KEY_SUPPORT_LABEL);
    }
}
