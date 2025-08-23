<?php

declare(strict_types=1);

namespace App\Tests\Application\Exceptions;

use App\Application\Exceptions\ValidationException;
use App\Application\Validation\ValidationResult;
use Exception;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testConstructorWithDefaultMessage(): void
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('field1', 'Field 1 is required');

        $exception = new ValidationException($validationResult);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(['field1' => ['Field 1 is required']], $exception->getValidationErrors());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('email', 'Invalid email format');

        $customMessage = 'User input validation failed';
        $exception = new ValidationException($validationResult, $customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(['email' => ['Invalid email format']], $exception->getValidationErrors());
    }

    public function testGetValidationErrorsWithMultipleErrors(): void
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('username', 'Username is required');
        $validationResult->addError('username', 'Username must be at least 3 characters');
        $validationResult->addError('password', 'Password is required');
        $validationResult->addError('email', 'Email is invalid');

        $exception = new ValidationException($validationResult, 'Multiple validation errors');

        $expectedErrors = [
            'username' => [
                'Username is required',
                'Username must be at least 3 characters'
            ],
            'password' => ['Password is required'],
            'email' => ['Email is invalid']
        ];

        $this->assertEquals($expectedErrors, $exception->getValidationErrors());
    }

    public function testGetValidationErrorsWithNoErrors(): void
    {
        $validationResult = new ValidationResult();

        $exception = new ValidationException($validationResult);

        $this->assertEquals([], $exception->getValidationErrors());
        $this->assertTrue($validationResult->isValid());
    }

    public function testExceptionInheritance(): void
    {
        $validationResult = new ValidationResult();
        $exception = new ValidationException($validationResult);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeCaught(): void
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('test', 'Test error');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Form validation failed');

        throw new ValidationException($validationResult, 'Form validation failed');
    }

    public function testExceptionCanBeCaughtAsGenericException(): void
    {
        $validationResult = new ValidationResult();

        $this->expectException(Exception::class);

        throw new ValidationException($validationResult);
    }

    public function testValidationResultPreservation(): void
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('field1', 'Error 1');
        $validationResult->addError('field2', 'Error 2');

        $exception = new ValidationException($validationResult);

        // Ensure the validation errors are preserved correctly
        $errors = $exception->getValidationErrors();
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('field1', $errors);
        $this->assertArrayHasKey('field2', $errors);
        $this->assertEquals(['Error 1'], $errors['field1']);
        $this->assertEquals(['Error 2'], $errors['field2']);
    }

    public function testExceptionWithComplexValidationScenario(): void
    {
        $validationResult = new ValidationResult();

        // Simulate a complex form validation scenario
        $validationResult->addError('name', 'Name is required');
        $validationResult->addError('name', 'Name must not contain special characters');
        $validationResult->addError('age', 'Age must be a number');
        $validationResult->addError('age', 'Age must be between 18 and 100');
        $validationResult->addError('email', 'Email is required');
        $validationResult->addError('email', 'Email format is invalid');
        $validationResult->addError('email', 'Email domain is not allowed');

        $exception = new ValidationException($validationResult, 'User registration validation failed');

        $this->assertEquals('User registration validation failed', $exception->getMessage());

        $errors = $exception->getValidationErrors();
        $this->assertCount(3, $errors); // 3 fields with errors
        $this->assertCount(2, $errors['name']);
        $this->assertCount(2, $errors['age']);
        $this->assertCount(3, $errors['email']);
    }

    public function testExceptionStackTrace(): void
    {
        $validationResult = new ValidationResult();
        $exception = new ValidationException($validationResult);

        $this->assertIsString($exception->getTraceAsString());
        $this->assertIsArray($exception->getTrace());
        $this->assertNotEmpty($exception->getTrace());
    }

    public function testExceptionCodeIsAlwaysZero(): void
    {
        $validationResult = new ValidationResult();
        $exception = new ValidationException($validationResult);

        // ValidationException doesn't set a custom code, so it should be 0
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionMessageCanBeEmpty(): void
    {
        $validationResult = new ValidationResult();
        $exception = new ValidationException($validationResult, '');

        $this->assertEquals('', $exception->getMessage());
    }
}
