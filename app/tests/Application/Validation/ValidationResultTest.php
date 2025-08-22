<?php

declare(strict_types=1);

namespace Tests\Application\Validation;

use App\Application\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $result = new ValidationResult();

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testConstructorWithValidState(): void
    {
        $result = new ValidationResult(true, []);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testConstructorWithInvalidState(): void
    {
        $errors = ['field1' => ['Error message']];
        $result = new ValidationResult(false, $errors);

        $this->assertFalse($result->isValid());
        $this->assertEquals($errors, $result->getErrors());
    }

    public function testAddError(): void
    {
        $result = new ValidationResult();
        $result->addError('name', 'Name is required');

        $this->assertFalse($result->isValid());
        $this->assertEquals(['name' => ['Name is required']], $result->getErrors());
    }

    public function testAddMultipleErrorsToSameField(): void
    {
        $result = new ValidationResult();
        $result->addError('name', 'Name is required');
        $result->addError('name', 'Name is too short');

        $this->assertFalse($result->isValid());
        $this->assertEquals(['name' => ['Name is required', 'Name is too short']], $result->getErrors());
    }

    public function testAddErrorsToDifferentFields(): void
    {
        $result = new ValidationResult();
        $result->addError('name', 'Name is required');
        $result->addError('email', 'Email is invalid');

        $this->assertFalse($result->isValid());
        $this->assertEquals([
            'name' => ['Name is required'],
            'email' => ['Email is invalid']
        ], $result->getErrors());
    }

    public function testGetErrorsForField(): void
    {
        $result = new ValidationResult();
        $result->addError('name', 'Name is required');
        $result->addError('name', 'Name is too short');
        $result->addError('email', 'Email is invalid');

        $this->assertEquals(['Name is required', 'Name is too short'], $result->getErrorsForField('name'));
        $this->assertEquals(['Email is invalid'], $result->getErrorsForField('email'));
        $this->assertEquals([], $result->getErrorsForField('nonexistent'));
    }

    public function testHasErrorsForField(): void
    {
        $result = new ValidationResult();
        $result->addError('name', 'Name is required');

        $this->assertTrue($result->hasErrorsForField('name'));
        $this->assertFalse($result->hasErrorsForField('email'));
        $this->assertFalse($result->hasErrorsForField('nonexistent'));
    }

    public function testHasErrorsForFieldWithEmptyErrors(): void
    {
        $result = new ValidationResult();

        $this->assertFalse($result->hasErrorsForField('name'));
    }

    public function testValidationStateChangesAfterAddingError(): void
    {
        $result = new ValidationResult(true);
        $this->assertTrue($result->isValid());

        $result->addError('name', 'Error');
        $this->assertFalse($result->isValid());
    }
}
