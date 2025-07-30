<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Database;

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\DatabaseUnitOfWork;
use Tests\TestCase;

class DatabaseUnitOfWorkTest extends TestCase
{
    private DatabaseUnitOfWork $unitOfWork;
    private DatabaseConnection $mockConnection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConnection = $this->createMock(DatabaseConnection::class);
        $this->unitOfWork = new DatabaseUnitOfWork($this->mockConnection);
    }

    public function testRegisterNew(): void
    {
        $entity = new Tree(null, 'Test Tree', 'A test tree');

        $this->unitOfWork->registerNew($entity);

        // Since the processing is not implemented yet, we just test that no exception is thrown
        $this->assertTrue(true);
    }

    public function testRegisterDirty(): void
    {
        $entity = new Tree(1, 'Test Tree', 'A test tree');

        $this->unitOfWork->registerDirty($entity);

        // Since the processing is not implemented yet, we just test that no exception is thrown
        $this->assertTrue(true);
    }

    public function testRegisterDeleted(): void
    {
        $entity = new Tree(1, 'Test Tree', 'A test tree');

        $this->unitOfWork->registerDeleted($entity);

        // Since the processing is not implemented yet, we just test that no exception is thrown
        $this->assertTrue(true);
    }

    public function testBeginTransaction(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('beginTransaction');

        $this->unitOfWork->beginTransaction();
    }

    public function testCommit(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('commit');

        $this->unitOfWork->commit();
    }

    public function testCommitWithException(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('commit')
            ->willThrowException(new \Exception('Database error'));

        $this->mockConnection->expects($this->once())
            ->method('rollback');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->unitOfWork->commit();
    }

    public function testRollback(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('rollback');

        $this->unitOfWork->rollback();
    }

    public function testInTransaction(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $result = $this->unitOfWork->inTransaction();

        $this->assertTrue($result);
    }
} 