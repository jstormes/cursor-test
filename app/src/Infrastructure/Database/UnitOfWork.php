<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface UnitOfWork
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;
}
