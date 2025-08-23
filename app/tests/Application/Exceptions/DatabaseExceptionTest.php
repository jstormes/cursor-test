<?php

declare(strict_types=1);

namespace App\Tests\Application\Exceptions;

use App\Application\Exceptions\DatabaseException;
use Exception;
use PHPUnit\Framework\TestCase;

class DatabaseExceptionTest extends TestCase
{
    public function testConstructorWithDefaultMessage(): void
    {
        $exception = new DatabaseException();
        
        $this->assertInstanceOf(DatabaseException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals('Database operation failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $customMessage = 'Connection to database failed';
        $exception = new DatabaseException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomCode(): void
    {
        $customMessage = 'Query execution failed';
        $customCode = 500;
        $exception = new DatabaseException($customMessage, $customCode);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals($customCode, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithPreviousException(): void
    {
        $previousException = new Exception('Original error');
        $exception = new DatabaseException('Database error', 0, $previousException);
        
        $this->assertEquals('Database error', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testConstructorWithAllParameters(): void
    {
        $customMessage = 'Transaction rollback failed';
        $customCode = 1001;
        $previousException = new Exception('Deadlock detected');
        
        $exception = new DatabaseException($customMessage, $customCode, $previousException);
        
        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals($customCode, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new DatabaseException('Test error');
        
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionCanBeCaught(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database connection lost');
        $this->expectExceptionCode(2002);
        
        throw new DatabaseException('Database connection lost', 2002);
    }

    public function testExceptionCanBeCaughtAsGenericException(): void
    {
        $this->expectException(Exception::class);
        
        throw new DatabaseException('Generic database error');
    }

    public function testExceptionStackTrace(): void
    {
        $exception = new DatabaseException('Stack trace test');
        
        $this->assertIsString($exception->getTraceAsString());
        $this->assertIsArray($exception->getTrace());
        $this->assertNotEmpty($exception->getTrace());
    }

    public function testExceptionStringRepresentation(): void
    {
        $exception = new DatabaseException('String representation test', 999);
        $string = (string) $exception;
        
        $this->assertStringContainsString('DatabaseException', $string);
        $this->assertStringContainsString('String representation test', $string);
        $this->assertStringContainsString('999', (string) $exception->getCode());
    }
}