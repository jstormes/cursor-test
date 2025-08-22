<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface DatabaseConnectionFactoryInterface
{
    /**
     * Create a database connection from configuration
     */
    public function create(array $config): DatabaseConnection;
}
