<?php

declare(strict_types=1);

namespace App\Application\Validation;

interface ValidationInterface
{
    /**
     * Validate data and return validation result
     */
    public function validate(array $data): ValidationResult;
}
