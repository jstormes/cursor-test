<?php

declare(strict_types=1);

namespace App\Tests\Application\Configuration;

use App\Application\Configuration\EnvironmentValidator;
use Tests\TestCase;

class EnvironmentValidatorTest extends TestCase
{
    private EnvironmentValidator $validator;
    private array $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validator = new EnvironmentValidator();
        
        // Store original environment variables from both sources
        $this->originalEnv = [
            'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: null,
            'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: null,
            'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: null,
            'MYSQL_USER' => $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: null,
            'MYSQL_PASSWORD' => $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: null,
            'APP_ENV' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: null,
            'ENVIRONMENT' => $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: null,
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment variables
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null || $value === false) {
                unset($_ENV[$key]);
                putenv("$key="); // Set to empty instead of clearing
            } else {
                $_ENV[$key] = $value;
                putenv("$key=$value"); // Also set for getenv()
            }
        }
        
        parent::tearDown();
    }

    private function setEnvVars(array $vars): void
    {
        foreach ($vars as $key => $value) {
            $_ENV[$key] = $value;
            putenv("$key=$value"); // Also set for getenv()
        }
    }

    private function clearEnvVars(array $keys): void
    {
        foreach ($keys as $key) {
            unset($_ENV[$key]);
            putenv("$key="); // Set to empty string instead of clearing
        }
    }

    public function testValidateWithAllValidVariables(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123',
            'APP_ENV' => 'development'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithMissingRequiredVariables(): void
    {
        $this->clearEnvVars(['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD']);

        $errors = $this->validator->validate();

        $this->assertCount(5, $errors);
        $this->assertStringContainsString("Required environment variable 'MYSQL_HOST' is not set", implode(', ', $errors));
        $this->assertStringContainsString("Required environment variable 'MYSQL_PORT' is not set", implode(', ', $errors));
        $this->assertStringContainsString("Required environment variable 'MYSQL_DATABASE' is not set", implode(', ', $errors));
        $this->assertStringContainsString("Required environment variable 'MYSQL_USER' is not set", implode(', ', $errors));
        $this->assertStringContainsString("Required environment variable 'MYSQL_PASSWORD' is not set", implode(', ', $errors));
    }

    public function testValidateWithInvalidMysqlPort(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => 'invalid_port',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_PORT' has invalid format for rule 'numeric'", implode(', ', $errors));
    }

    public function testValidateWithZeroMysqlPort(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '0',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_PORT' has invalid format for rule 'numeric'", implode(', ', $errors));
    }

    public function testValidateWithNegativeMysqlPort(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '-1',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_PORT' has invalid format for rule 'numeric'", implode(', ', $errors));
    }

    public function testValidateWithValidIpAddressHost(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => '192.168.1.100',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithValidDomainHost(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'db.example.com',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidHost(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'invalid..host',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_HOST' has invalid format for rule 'hostname'", implode(', ', $errors));
    }

    public function testValidateWithInvalidDatabaseName(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test-db!',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_DATABASE' has invalid format for rule 'alphanum_underscore'", implode(', ', $errors));
    }

    public function testValidateWithValidDatabaseNameWithUnderscores(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db_123',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithInvalidUserName(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test-user@host',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Environment variable 'MYSQL_USER' has invalid format for rule 'alphanum_underscore'", implode(', ', $errors));
    }

    public function testValidateWithShortPassword(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'short'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Database password should be at least 8 characters long for security', implode(', ', $errors));
    }

    public function testValidateWithRootUserInProduction(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'securepassword123',
            'APP_ENV' => 'production'
        ]);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Using root database user in production is not recommended', implode(', ', $errors));
    }

    public function testValidateWithRootUserInDevelopment(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'securepassword123',
            'APP_ENV' => 'development'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateWithRootUserInTestEnvironment(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'securepassword123',
            'APP_ENV' => 'test'
        ]);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors);
    }

    public function testIsDevEnvironmentDetection(): void
    {
        // Test various development environment names
        $devEnvironments = ['dev', 'development', 'local', 'test'];
        
        foreach ($devEnvironments as $env) {
            $this->setEnvVars([
                'MYSQL_HOST' => 'localhost',
                'MYSQL_PORT' => '3306',
                'MYSQL_DATABASE' => 'test_db',
                'MYSQL_USER' => 'root',
                'MYSQL_PASSWORD' => 'securepassword123',
                'APP_ENV' => $env
            ]);

            $errors = $this->validator->validate();
            
            // Should not contain root user warning
            $rootUserError = array_filter($errors, fn($error) => str_contains($error, 'Using root database user in production'));
            $this->assertEmpty($rootUserError, "Environment '$env' should be considered development");
        }
    }

    public function testIsProductionEnvironmentDetection(): void
    {
        // Test production environment names
        $prodEnvironments = ['prod', 'production', 'staging', 'live'];
        
        foreach ($prodEnvironments as $env) {
            $this->setEnvVars([
                'MYSQL_HOST' => 'localhost',
                'MYSQL_PORT' => '3306',
                'MYSQL_DATABASE' => 'test_db',
                'MYSQL_USER' => 'root',
                'MYSQL_PASSWORD' => 'securepassword123',
                'APP_ENV' => $env
            ]);

            $errors = $this->validator->validate();
            
            // Should contain root user warning
            $rootUserError = array_filter($errors, fn($error) => str_contains($error, 'Using root database user in production'));
            $this->assertNotEmpty($rootUserError, "Environment '$env' should be considered production");
        }
    }

    public function testEnvironmentVariableFallback(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'securepassword123',
            'ENVIRONMENT' => 'development' // Using ENVIRONMENT instead of APP_ENV
        ]);
        $this->clearEnvVars(['APP_ENV']);

        $errors = $this->validator->validate();

        $this->assertEmpty($errors); // Should be treated as development
    }

    public function testDefaultToProductionWhenNoEnvSet(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'root',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);
        $this->clearEnvVars(['APP_ENV', 'ENVIRONMENT']);

        $errors = $this->validator->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Using root database user in production is not recommended', implode(', ', $errors));
    }

    public function testValidateOrThrowWithValidEnvironment(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        // Should not throw any exception
        $this->validator->validateOrThrow();
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function testValidateOrThrowWithInvalidEnvironment(): void
    {
        $this->clearEnvVars(['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment validation failed:');

        $this->validator->validateOrThrow();
    }

    public function testValidateOrThrowExceptionMessage(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => 'invalid',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'short'
        ]);

        try {
            $this->validator->validateOrThrow();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('Environment validation failed:', $message);
            $this->assertStringContainsString("MYSQL_PORT' has invalid format", $message);
            $this->assertStringContainsString('Database password should be at least 8 characters', $message);
        }
    }

    public function testValidateWithEmptyStringValues(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => '',
            'MYSQL_PORT' => '',
            'MYSQL_DATABASE' => '',
            'MYSQL_USER' => '',
            'MYSQL_PASSWORD' => ''
        ]);

        $errors = $this->validator->validate();

        $this->assertCount(5, $errors);
        foreach (['MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'] as $var) {
            $this->assertStringContainsString("Required environment variable '$var' is not set", implode(', ', $errors));
        }
    }

    public function testValidateWithWhitespaceValues(): void
    {
        $this->setEnvVars([
            'MYSQL_HOST' => '   ',
            'MYSQL_PORT' => ' ',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);

        $errors = $this->validator->validate();

        // empty() should treat whitespace-only strings as empty
        $this->assertStringContainsString("Required environment variable 'MYSQL_HOST' is not set", implode(', ', $errors));
        $this->assertStringContainsString("Required environment variable 'MYSQL_PORT' is not set", implode(', ', $errors));
    }

    public function testValidateWithValidPortNumbers(): void
    {
        $validPorts = ['1', '80', '3306', '5432', '65535'];
        
        foreach ($validPorts as $port) {
            $this->setEnvVars([
                'MYSQL_HOST' => 'localhost',
                'MYSQL_PORT' => $port,
                'MYSQL_DATABASE' => 'test_db',
                'MYSQL_USER' => 'test_user',
                'MYSQL_PASSWORD' => 'securepassword123'
            ]);

            $errors = $this->validator->validate();
            $portErrors = array_filter($errors, fn($error) => str_contains($error, 'MYSQL_PORT'));
            $this->assertEmpty($portErrors, "Port '$port' should be valid");
        }
    }

    public function testValidateWithCaseInsensitiveEnvironmentNames(): void
    {
        $envs = ['DEV', 'Development', 'LOCAL', 'TeSt'];
        
        foreach ($envs as $env) {
            $this->setEnvVars([
                'MYSQL_HOST' => 'localhost',
                'MYSQL_PORT' => '3306',
                'MYSQL_DATABASE' => 'test_db',
                'MYSQL_USER' => 'root',
                'MYSQL_PASSWORD' => 'securepassword123',
                'APP_ENV' => $env
            ]);

            $errors = $this->validator->validate();
            
            $rootUserError = array_filter($errors, fn($error) => str_contains($error, 'Using root database user in production'));
            $this->assertEmpty($rootUserError, "Environment '$env' should be considered development (case insensitive)");
        }
    }

    public function testValidatorIsStateless(): void
    {
        // First validation
        $this->setEnvVars([
            'MYSQL_HOST' => 'localhost',
            'MYSQL_PORT' => '3306',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'securepassword123'
        ]);
        $errors1 = $this->validator->validate();

        // Second validation with different values
        $this->setEnvVars([
            'MYSQL_HOST' => '',
            'MYSQL_PORT' => 'invalid',
            'MYSQL_DATABASE' => 'test_db',
            'MYSQL_USER' => 'test_user',
            'MYSQL_PASSWORD' => 'short'
        ]);
        $errors2 = $this->validator->validate();

        // Results should be independent
        $this->assertEmpty($errors1);
        $this->assertNotEmpty($errors2);
    }
}