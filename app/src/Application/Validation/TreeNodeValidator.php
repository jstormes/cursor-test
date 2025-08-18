<?php

declare(strict_types=1);

namespace App\Application\Validation;

class TreeNodeValidator implements ValidationInterface
{
    #[\Override]
    public function validate(array $data): ValidationResult
    {
        $result = new ValidationResult();

        // Validate name
        if (!isset($data['name']) || empty(trim($data['name']))) {
            $result->addError('name', 'Node name is required');
        } elseif (strlen(trim($data['name'])) < 2) {
            $result->addError('name', 'Node name must be at least 2 characters long');
        } elseif (strlen(trim($data['name'])) > 255) {
            $result->addError('name', 'Node name must not exceed 255 characters');
        }

        // Validate tree_id
        if (!isset($data['tree_id']) || !is_numeric($data['tree_id']) || (int)$data['tree_id'] <= 0) {
            $result->addError('tree_id', 'Valid tree ID is required');
        }

        // Validate parent_id (optional)
        if (isset($data['parent_id']) && $data['parent_id'] !== null) {
            if (!is_numeric($data['parent_id']) || (int)$data['parent_id'] <= 0) {
                $result->addError('parent_id', 'Parent ID must be a valid positive integer');
            }
        }

        // Validate sort_order
        if (isset($data['sort_order'])) {
            if (!is_numeric($data['sort_order']) || (int)$data['sort_order'] < 0) {
                $result->addError('sort_order', 'Sort order must be a non-negative integer');
            }
        }

        // Validate type
        if (isset($data['type'])) {
            $allowedTypes = ['SimpleNode', 'ButtonNode'];
            if (!in_array($data['type'], $allowedTypes, true)) {
                $result->addError('type', 'Invalid node type. Allowed types: ' . implode(', ', $allowedTypes));
            }
        }

        // Validate type_data for ButtonNode
        if (isset($data['type']) && $data['type'] === 'ButtonNode' && isset($data['type_data'])) {
            $this->validateButtonNodeData($data['type_data'], $result);
        }

        return $result;
    }

    private function validateButtonNodeData(array $typeData, ValidationResult $result): void
    {
        // Validate button text
        if (isset($typeData['button_text'])) {
            if (empty(trim($typeData['button_text']))) {
                $result->addError('type_data.button_text', 'Button text cannot be empty');
            } elseif (strlen($typeData['button_text']) > 100) {
                $result->addError('type_data.button_text', 'Button text must not exceed 100 characters');
            }
        }

        // Validate button action (basic security check)
        if (isset($typeData['button_action'])) {
            if (strlen($typeData['button_action']) > 500) {
                $result->addError('type_data.button_action', 'Button action must not exceed 500 characters');
            }
            // Basic security check for dangerous patterns
            $dangerousPatterns = [
                '/\bjavascript\s*:/i',
                '/\beval\s*\(/i',
                '/\bdocument\.write\s*\(/i',
                '/\binnerHTML\s*=/i',
                '/\bouterHTML\s*=/i',
            ];
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $typeData['button_action'])) {
                    $result->addError('type_data.button_action', 'Button action contains potentially dangerous code');
                    break;
                }
            }
        }
    }

    public function sanitize(array $data): array
    {
        $sanitized = [];

        if (isset($data['name'])) {
            $sanitized['name'] = trim(htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($data['tree_id'])) {
            $sanitized['tree_id'] = (int)$data['tree_id'];
        }

        if (isset($data['parent_id'])) {
            $sanitized['parent_id'] = $data['parent_id'] === null ? null : (int)$data['parent_id'];
        }

        if (isset($data['sort_order'])) {
            $sanitized['sort_order'] = (int)$data['sort_order'];
        }

        if (isset($data['type'])) {
            $sanitized['type'] = $data['type'];
        }

        if (isset($data['type_data']) && is_array($data['type_data'])) {
            $sanitized['type_data'] = $this->sanitizeTypeData($data['type_data']);
        }

        return $sanitized;
    }

    private function sanitizeTypeData(array $typeData): array
    {
        $sanitized = [];

        if (isset($typeData['button_text'])) {
            $sanitized['button_text'] = trim(htmlspecialchars($typeData['button_text'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($typeData['button_action'])) {
            $sanitized['button_action'] = trim(htmlspecialchars($typeData['button_action'], ENT_QUOTES, 'UTF-8'));
        }

        return $sanitized;
    }
}
