<?php

declare(strict_types=1);

namespace App\Application\Configuration;

class EnvironmentValidator
{
    private array $requiredEnvVars = [
        'MYSQL_HOST',
        'MYSQL_PORT',
        'MYSQL_DATABASE',
        'MYSQL_USER',
        'MYSQL_PASSWORD',
    ];

    private array $validationRules = [
        'MYSQL_PORT' => 'numeric',
        'MYSQL_HOST' => 'hostname',
        'MYSQL_DATABASE' => 'alphanum_underscore',
        'MYSQL_USER' => 'alphanum_underscore',
    ];

    public function validate(): array
    {
        $errors = [];

        foreach ($this->requiredEnvVars as $envVar) {
            $value = $_ENV[$envVar] ?? getenv($envVar);

            if (empty($value)) {
                $errors[] = "Required environment variable '{$envVar}' is not set";
                continue;
            }

            // Apply specific validation rules
            if (isset($this->validationRules[$envVar])) {
                $rule = $this->validationRules[$envVar];
                if (!$this->validateValue($value, $rule)) {
                    $errors[] = "Environment variable '{$envVar}' has invalid format for rule '{$rule}'";
                }
            }
        }

        // Additional security checks
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD');
        if ($password && strlen($password) < 8) {
            $errors[] = 'Database password should be at least 8 characters long for security';
        }

        $user = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER');
        if ($user === 'root' && !$this->isDevEnvironment()) {
            $errors[] = 'Using root database user in production is not recommended';
        }

        return $errors;
    }

    private function validateValue(string $value, string $rule): bool
    {
        return match ($rule) {
            'numeric' => is_numeric($value) && (int)$value > 0,
            'hostname' => filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false ||
                         filter_var($value, FILTER_VALIDATE_IP) !== false,
            'alphanum_underscore' => preg_match('/^[a-zA-Z0-9_]+$/', $value) === 1,
            default => true
        };
    }

    private function isDevEnvironment(): bool
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: ($_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: 'production');
        return in_array(strtolower($env), ['dev', 'development', 'local', 'test'], true);
    }

    public function validateOrThrow(): void
    {
        $errors = $this->validate();
        if (!empty($errors)) {
            throw new \RuntimeException(
                'Environment validation failed: ' . implode(', ', $errors)
            );
        }
    }
}
