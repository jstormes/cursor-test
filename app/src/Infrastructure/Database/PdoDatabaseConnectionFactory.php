<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

final class PdoDatabaseConnectionFactory implements DatabaseConnectionFactoryInterface
{
    #[\Override]
    public function create(array $config): DatabaseConnection
    {
        return new PdoDatabaseConnection($config);
    }
}
