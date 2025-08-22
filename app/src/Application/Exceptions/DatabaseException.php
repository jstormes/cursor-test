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
}
