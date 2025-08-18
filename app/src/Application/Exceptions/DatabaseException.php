<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

use Exception;
use Throwable;

class DatabaseException extends Exception
{
    public function __construct(string $message = 'Database operation failed', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function transactionFailed(string $operation, ?Throwable $previous = null): self
    {
        return new self("Transaction failed during: {$operation}", 0, $previous);
    }

    public static function connectionFailed(?Throwable $previous = null): self
    {
        return new self('Database connection failed', 0, $previous);
    }

    public static function queryFailed(string $query, ?Throwable $previous = null): self
    {
        return new self("Query execution failed: {$query}", 0, $previous);
    }
}
