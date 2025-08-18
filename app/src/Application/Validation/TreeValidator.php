<?php

declare(strict_types=1);

namespace App\Application\Validation;

class TreeValidator implements ValidationInterface
{
    #[\Override]
    public function validate(array $data): ValidationResult
    {
        $result = new ValidationResult();

        // Validate name
        if (!isset($data['name']) || empty(trim($data['name']))) {
            $result->addError('name', 'Tree name is required');
        } elseif (strlen(trim($data['name'])) < 3) {
            $result->addError('name', 'Tree name must be at least 3 characters long');
        } elseif (strlen(trim($data['name'])) > 255) {
            $result->addError('name', 'Tree name must not exceed 255 characters');
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_().]+$/', trim($data['name']))) {
            $result->addError('name', 'Tree name contains invalid characters');
        }

        // Validate description (optional)
        if (isset($data['description']) && $data['description'] !== null) {
            if (strlen($data['description']) > 1000) {
                $result->addError('description', 'Description must not exceed 1000 characters');
            }
            // Basic HTML sanitization check
            if ($data['description'] !== strip_tags($data['description'])) {
                $result->addError('description', 'Description cannot contain HTML tags');
            }
        }

        return $result;
    }

    public function sanitize(array $data): array
    {
        $sanitized = [];

        if (isset($data['name'])) {
            $sanitized['name'] = trim(htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($data['description'])) {
            $sanitized['description'] = $data['description'] === null
                ? null
                : trim(htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'));
        }

        return $sanitized;
    }
}
