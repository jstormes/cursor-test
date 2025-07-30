<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\UserDataMapper;
use App\Infrastructure\Persistence\User\DatabaseUserRepository;
use Tests\TestCase;
use PDOStatement;

class DatabaseUserRepositoryTest extends TestCase
{
    private DatabaseUserRepository $repository;
    private DatabaseConnection $mockConnection;
    private UserDataMapper $mockDataMapper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConnection = $this->createMock(DatabaseConnection::class);
        $this->mockDataMapper = $this->createMock(UserDataMapper::class);
        $this->repository = new DatabaseUserRepository($this->mockConnection, $this->mockDataMapper);
    }

    public function testFindAll(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'username' => 'john.doe', 'first_name' => 'John', 'last_name' => 'Doe'],
                ['id' => 2, 'username' => 'jane.smith', 'first_name' => 'Jane', 'last_name' => 'Smith']
            ]);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, username, first_name, last_name FROM users WHERE is_active = 1 ORDER BY id')
            ->willReturn($mockStatement);

        $expectedUsers = [
            new User(1, 'john.doe', 'John', 'Doe'),
            new User(2, 'jane.smith', 'Jane', 'Smith')
        ];

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntities')
            ->willReturn($expectedUsers);

        $result = $this->repository->findAll();

        $this->assertEquals($expectedUsers, $result);
    }

    public function testFindUserOfIdSuccess(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 1, 'username' => 'john.doe', 'first_name' => 'John', 'last_name' => 'Doe']);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, username, first_name, last_name FROM users WHERE id = ? AND is_active = 1', [1])
            ->willReturn($mockStatement);

        $expectedUser = new User(1, 'john.doe', 'John', 'Doe');

        $this->mockDataMapper->expects($this->once())
            ->method('mapToEntity')
            ->willReturn($expectedUser);

        $result = $this->repository->findUserOfId(1);

        $this->assertEquals($expectedUser, $result);
    }

    public function testFindUserOfIdNotFound(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->with('SELECT id, username, first_name, last_name FROM users WHERE id = ? AND is_active = 1', [999])
            ->willReturn($mockStatement);

        $this->expectException(UserNotFoundException::class);

        $this->repository->findUserOfId(999);
    }
} 