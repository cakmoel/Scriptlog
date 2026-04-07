<?php
/**
 * Security Functions Test
 * 
 * Tests for security utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testGenerateToken(): void
    {
        $token = generate_token(32);
        $this->assertEquals(64, strlen($token));
    }
    
    public function testGenerateTokenDifferentLengths(): void
    {
        $token16 = generate_token(16);
        $this->assertEquals(32, strlen($token16));
        
        $token64 = generate_token(64);
        $this->assertEquals(128, strlen($token64));
    }
    
    public function testFormId(): void
    {
        $id = form_id('login');
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }
    
    public function testSimpleSalt(): void
    {
        $salt = simple_salt(16);
        $this->assertIsString($salt);
        $this->assertGreaterThan(0, strlen($salt));
    }
    
    public function testSimpleSaltDifferentLengths(): void
    {
        $salt8 = simple_salt(8);
        $this->assertIsString($salt8);
        
        $salt32 = simple_salt(32);
        $this->assertIsString($salt32);
    }
    
    public function testEncodeEmailAddress(): void
    {
        $encoded = encode_email_address('test@example.com');
        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);
    }
    
    public function testHideEmailWithValidEmail(): void
    {
        $hidden = hide_email('test@example.com');
        $this->assertIsString($hidden);
    }
    
    public function testCheckPwdStrengthWithWeakPassword(): void
    {
        $strength = check_pwd_strength('123');
        $this->assertLessThan(50, $strength);
    }
    
    public function testCheckPwdStrengthWithMediumPassword(): void
    {
        $strength = check_pwd_strength('Password123');
        $this->assertIsBool($strength);
    }
    
    public function testWorstPasswords(): void
    {
        $worst = worst_passwords();
        $this->assertIsArray($worst);
        $this->assertNotEmpty($worst);
    }
    
    public function testCheckIntegerWithValidInteger(): void
    {
        $result = check_integer('123');
        $this->assertTrue($result);
    }
    
    public function testCheckIntegerWithNonInteger(): void
    {
        $result = check_integer('abc');
        $this->assertFalse($result);
    }
}
