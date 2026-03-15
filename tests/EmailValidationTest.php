<?php
/**
 * Email Validation Test
 * 
 * Tests for email validation utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class EmailValidationTest extends TestCase
{
    public function testValidatorEmailInstanceReturnsObject(): void
    {
        $validator = validator_email_instance();
        $this->assertIsObject($validator);
    }
    
    public function testEmailValidationWithValidEmail(): void
    {
        $result = email_validation('test@example.com', new \Egulias\EmailValidator\Validation\RFCValidation());
        $this->assertTrue($result);
    }
    
    public function testEmailValidationWithInvalidEmail(): void
    {
        $result = email_validation('not-an-email', new \Egulias\EmailValidator\Validation\RFCValidation());
        $this->assertFalse($result);
    }
    
    public function testEmailValidationWithEmptyEmail(): void
    {
        $result = email_validation('', new \Egulias\EmailValidator\Validation\RFCValidation());
        $this->assertFalse($result);
    }
    
    public function testEmailMultipleValidationWithValidEmail(): void
    {
        $result = email_multiple_validation('test@example.com');
        $this->assertIsBool($result);
    }
}
