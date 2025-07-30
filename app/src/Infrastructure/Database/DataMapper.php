<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

interface DataMapper
{
    /**
     * Map database row to domain entity
     */
    public function mapToEntity(array $data): object;

    /**
     * Map domain entity to database row
     */
    public function mapToArray(object $entity): array;

    /**
     * Map multiple database rows to domain entities
     */
    public function mapToEntities(array $data): array;
} 