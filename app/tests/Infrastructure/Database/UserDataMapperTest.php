<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Domain\User\User;
use App\Infrastructure\Database\UserDataMapper;
use Tests\TestCase;

class UserDataMapperTest extends TestCase
{
    private UserDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new UserDataMapper();
    }

    public function testMapToEntity(): void
    {
        $data = [
            'id' => 1,
            'username' => 'john.doe',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ];

        $user = $this->mapper->mapToEntity($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('john.doe', $user->getUsername());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
    }

    public function testMapToArray(): void
    {
        $user = new User(1, 'john.doe', 'John', 'Doe');

        $data = $this->mapper->mapToArray($user);

        $this->assertEquals([
            'id' => 1,
            'username' => 'john.doe',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ], $data);
    }

    public function testMapToArrayWithInvalidEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of User');

        $this->mapper->mapToArray(new \stdClass());
    }

    public function testMapToEntities(): void
    {
        $data = [
            [
                'id' => 1,
                'username' => 'john.doe',
                'first_name' => 'John',
                'last_name' => 'Doe'
            ],
            [
                'id' => 2,
                'username' => 'jane.smith',
                'first_name' => 'Jane',
                'last_name' => 'Smith'
            ]
        ];

        $users = $this->mapper->mapToEntities($data);

        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
        $this->assertEquals('john.doe', $users[0]->getUsername());
        $this->assertEquals('jane.smith', $users[1]->getUsername());
    }
}
