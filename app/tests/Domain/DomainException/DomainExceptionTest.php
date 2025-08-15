<?php

declare(strict_types=1);

namespace App\Tests\Domain\DomainException;

use App\Domain\DomainException\DomainException;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Tests\TestCase;

class DomainExceptionTest extends TestCase
{
    public function testDomainRecordNotFoundExceptionCreation(): void
    {
        $message = 'Record not found';
        $exception = new DomainRecordNotFoundException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testDomainRecordNotFoundExceptionWithCode(): void
    {
        $message = 'Record not found with code';
        $code = 404;
        $exception = new DomainRecordNotFoundException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testDomainRecordNotFoundExceptionWithPreviousException(): void
    {
        $previousException = new \Exception('Previous exception');
        $message = 'Record not found with previous';
        $exception = new DomainRecordNotFoundException($message, 0, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testDomainRecordNotFoundExceptionInheritance(): void
    {
        $message = 'Record not found';
        $exception = new DomainRecordNotFoundException($message);

        $this->assertInstanceOf(DomainException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
