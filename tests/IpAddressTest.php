<?php
/**
 * IP Address Functions Test
 * 
 * Tests for IP address utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class IpAddressTest extends TestCase
{
    private $backupServer;
    
    protected function setUp(): void
    {
        $this->backupServer = $_SERVER;
    }
    
    protected function tearDown(): void
    {
        $_SERVER = $this->backupServer;
    }
    
    public function testDecbin32Returns32Bits(): void
    {
        $this->assertEquals('00000000000000000000000000001101', decbin32(13));
    }
    
    public function testDecbin32WithFullByte(): void
    {
        $this->assertEquals('00000000000000000000000011111111', decbin32(255));
    }
    
    public function testIpRangeWithCidr(): void
    {
        $this->assertTrue(ip_range('192.168.1.100', '192.168.1.0/24'));
        $this->assertFalse(ip_range('192.168.2.100', '192.168.1.0/24'));
    }
    
    public function testIpRangeWithNetmask(): void
    {
        $this->assertTrue(ip_range('192.168.1.100', '192.168.1.0/255.255.255.0'));
        $this->assertFalse(ip_range('192.168.2.100', '192.168.1.0/255.255.255.0'));
    }
    
    public function testIpRangeWithWildcard(): void
    {
        $this->assertTrue(ip_range('192.168.1.50', '192.168.*.*'));
        $this->assertFalse(ip_range('192.169.1.50', '192.168.*.*'));
    }
    
    public function testIpRangeWithRange(): void
    {
        $this->assertTrue(ip_range('192.168.1.50', '192.168.1.1-192.168.1.100'));
        $this->assertFalse(ip_range('192.168.1.200', '192.168.1.1-192.168.1.100'));
    }
    
    public function testCloudflareIpRangesReturnsArray(): void
    {
        $ranges = cloudflare_ipranges();
        $this->assertIsArray($ranges);
        $this->assertNotEmpty($ranges);
        $this->assertContains('103.21.244.0/22', $ranges);
    }
    
    public function testCloudflareCheckIPWithValidIp(): void
    {
        $this->assertFalse(cloudflare_checkIP('8.8.8.8'));
    }
    
    public function testCloudflareRequestCheckWithoutHeaders(): void
    {
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        unset($_SERVER['HTTP_CF_IPCOUNTRY']);
        unset($_SERVER['HTTP_CF_RAY']);
        unset($_SERVER['HTTP_CF_VISITOR']);
        
        $this->assertFalse(cloudflare_request_check());
    }
    
    public function testCloudflareRequestCheckWithHeaders(): void
    {
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '8.8.8.8';
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
        $_SERVER['HTTP_CF_RAY'] = 'abc123';
        $_SERVER['HTTP_CF_VISITOR'] = '{"scheme":"https"}';
        
        $this->assertTrue(cloudflare_request_check());
    }
    
    public function testIsCloudflareWithoutCloudflareHeaders(): void
    {
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        unset($_SERVER['REMOTE_ADDR']);
        
        $this->assertFalse(is_cloudflare());
    }
    
    public function testIpRangeReturnsFalseForInvalidRange(): void
    {
        $this->assertFalse(ip_range('192.168.1.1', 'invalid-range'));
    }
    
    public function testIpRangeWithFullWildcard(): void
    {
        $this->assertTrue(ip_range('10.20.30.40', '*.*.*.*'));
    }
}
