<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface UnitOfWork
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function registerNew(object $entity): void;

    public function registerDirty(object $entity): void;

    public function registerDeleted(object $entity): void;
}
