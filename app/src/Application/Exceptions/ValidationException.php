<?php

declare(strict_types=1);

namespace App\Application\Exceptions;

use App\Application\Validation\ValidationResult;
use Exception;

class ValidationException extends Exception
{
    private ValidationResult $validationResult;

    public function __construct(ValidationResult $validationResult, string $message = 'Validation failed')
    {
        $this->validationResult = $validationResult;
        parent::__construct($message);
    }

    public function getValidationResult(): ValidationResult
    {
        return $this->validationResult;
    }

    public function getValidationErrors(): array
    {
        return $this->validationResult->getErrors();
    }
}
