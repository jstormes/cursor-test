<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface UnitOfWork
{
    /**
     * Register a new entity for insertion
     */
    public function registerNew(object $entity): void;

    /**
     * Register a modified entity for update
     */
    public function registerDirty(object $entity): void;

    /**
     * Register an entity for deletion
     */
    public function registerDeleted(object $entity): void;

    /**
     * Begin a transaction
     */
    public function beginTransaction(): void;

    /**
     * Commit all changes
     */
    public function commit(): void;

    /**
     * Rollback all changes
     */
    public function rollback(): void;

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool;
} 