<?php

declare(strict_types=1);

namespace App\Application\Validation;

class ValidationResult
{
    private bool $isValid;
    private array $errors;

    public function __construct(bool $isValid = true, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->isValid = false;
        $this->errors[$field][] = $message;
    }

    public function getErrorsForField(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function hasErrorsForField(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
}
