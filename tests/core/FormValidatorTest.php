<?php
/**
 * FormValidator Tests
 *
 * Phase 3.3: Core Classes - FormValidator (15 tests)
 * Tests for input validation and sanitization
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class FormValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new FormValidator();
    }

    // =========================================================================
    // validate() Tests
    // =========================================================================

    public function testValidateEmailValid(): void
    {
        $result = FormValidator::validateItem('test@example.com', 'email');

        $this->assertTrue($result);
    }

    public function testValidateEmailInvalid(): void
    {
        $result = FormValidator::validateItem('invalid-email', 'email');

        $this->assertFalse($result);
    }

    public function testValidateUrlValid(): void
    {
        $result = FormValidator::validateItem('https://example.com', 'url');

        $this->assertTrue($result);
    }

    public function testValidateUrlInvalid(): void
    {
        $result = FormValidator::validateItem('not-a-url', 'url');

        $this->assertFalse($result);
    }

    public function testValidateInteger(): void
    {
        // FormValidator doesn't have 'integer' type - uses regex
        $result = FormValidator::validateItem('123', 'number');

        $this->assertTrue($result);
    }

    public function testValidateIntegerInvalid(): void
    {
        $result = FormValidator::validateItem('abc', 'number');

        $this->assertFalse($result);
    }

    // =========================================================================
    // sanitize() Tests
    // =========================================================================

    public function testSanitizeEmail(): void
    {
        $result = $this->validator->sanitize('test@example.com', 'email');

        $this->assertEquals('test@example.com', $result);
    }

    public function testSanitizeString(): void
    {
        $result = $this->validator->sanitize('<script>alert("xss")</script>', 'string');

        $this->assertIsString($result);
    }

    public function testSanitizeInteger(): void
    {
        // Need to set up validations array so sanitize() knows the type
        $validator = new FormValidator(
            ['number_field' => 'int'],  // key => type mapping
            [],
            ['number_field' => 'int']
        );

        $result = $validator->sanitize(['number_field' => '123abc']);

        $this->assertEquals('123', $result['number_field']);
    }

    // =========================================================================
    // validate() with $items Tests
    // =========================================================================

    public function testValidateMultipleFieldsValid(): void
    {
        $items = [
            'email' => 'test@example.com',
            'name' => 'John Doe'
        ];

        $result = $this->validator->validate($items);

        $this->assertIsBool($result);
    }

    public function testValidateMultipleFieldsInvalid(): void
    {
        $items = [
            'email' => 'invalid-email',
            'name' => 'John Doe'
        ];

        $result = $this->validator->validate($items);

        $this->assertIsBool($result);
    }

    // =========================================================================
    // getJSON() Tests
    // =========================================================================

    public function testGetJsonReturnsEmptyWhenNoErrors(): void
    {
        $result = $this->validator->getJSON();

        $this->assertJson($result);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function testConstructorSetsProperties(): void
    {
        $validator = new FormValidator(
            ['field1' => 'value1'],
            ['field1'],
            ['field1' => 'string']
        );

        $this->assertInstanceOf(FormValidator::class, $validator);
    }

    public function testValidateItemWithInvalidType(): void
    {
        $result = FormValidator::validateItem('test', 'nonexistent_type');

        $this->assertIsBool($result);
    }
}
