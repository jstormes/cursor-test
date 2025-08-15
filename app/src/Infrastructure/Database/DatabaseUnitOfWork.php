<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

class DatabaseUnitOfWork implements UnitOfWork
{
    private array $newEntities = [];
    private array $dirtyEntities = [];
    private array $deletedEntities = [];

    public function __construct(
        private DatabaseConnection $connection
    ) {
    }

    #[\Override]
    public function registerNew(object $entity): void
    {
        $this->newEntities[] = $entity;
    }

    #[\Override]
    public function registerDirty(object $entity): void
    {
        $this->dirtyEntities[] = $entity;
    }

    #[\Override]
    public function registerDeleted(object $entity): void
    {
        $this->deletedEntities[] = $entity;
    }

    #[\Override]
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    #[\Override]
    public function commit(): void
    {
        try {
            // Process new entities
            foreach ($this->newEntities as $entity) {
                $this->processNewEntity($entity);
            }

            // Process dirty entities
            foreach ($this->dirtyEntities as $entity) {
                $this->processDirtyEntity($entity);
            }

            // Process deleted entities
            foreach ($this->deletedEntities as $entity) {
                $this->processDeletedEntity($entity);
            }

            $this->connection->commit();
            $this->clear();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    #[\Override]
    public function rollback(): void
    {
        $this->connection->rollback();
        $this->clear();
    }

    #[\Override]
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    private function processNewEntity(object $entity): void
    {
        // This would be implemented by specific repositories
        // For now, we'll leave it as a placeholder
    }

    private function processDirtyEntity(object $entity): void
    {
        // This would be implemented by specific repositories
        // For now, we'll leave it as a placeholder
    }

    private function processDeletedEntity(object $entity): void
    {
        // This would be implemented by specific repositories
        // For now, we'll leave it as a placeholder
    }

    private function clear(): void
    {
        $this->newEntities = [];
        $this->dirtyEntities = [];
        $this->deletedEntities = [];
    }
}
