<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\UserDataMapper;

class DatabaseUserRepository implements UserRepository
{
    public function __construct(
        private DatabaseConnection $connection,
        private UserDataMapper $dataMapper
    ) {}

    public function findAll(): array
    {
        $sql = 'SELECT id, username, first_name, last_name FROM users WHERE is_active = 1 ORDER BY id';
        $statement = $this->connection->query($sql);
        $data = $statement->fetchAll();
        
        return $this->dataMapper->mapToEntities($data);
    }

    public function findUserOfId(int $id): User
    {
        $sql = 'SELECT id, username, first_name, last_name FROM users WHERE id = ? AND is_active = 1';
        $statement = $this->connection->query($sql, [$id]);
        $data = $statement->fetch();
        
        if (!$data) {
            throw new UserNotFoundException();
        }
        
        return $this->dataMapper->mapToEntity($data);
    }
} 