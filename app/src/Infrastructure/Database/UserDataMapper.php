<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\User\User;

class UserDataMapper implements DataMapper
{
    public function mapToEntity(array $data): User
    {
        return new User(
            (int) $data['id'],
            $data['username'],
            $data['first_name'],
            $data['last_name']
        );
    }

    public function mapToArray(object $entity): array
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be an instance of User');
        }

        return [
            'id' => $entity->getId(),
            'username' => $entity->getUsername(),
            'first_name' => $entity->getFirstName(),
            'last_name' => $entity->getLastName(),
        ];
    }

    public function mapToEntities(array $data): array
    {
        return array_map([$this, 'mapToEntity'], $data);
    }
} 