<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Infrastructure\Cache\CacheInterface;
use App\Infrastructure\Persistence\Tree\CachedTreeRepository;
use App\Tests\Utilities\MockClock;
use PHPUnit\Framework\TestCase;

class CachedTreeRepositoryTest extends TestCase
{
    private CachedTreeRepository $cachedRepository;
    private TreeRepository $mockRepository;
    private CacheInterface $mockCache;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(TreeRepository::class);
        $this->mockCache = $this->createMock(CacheInterface::class);
        $this->cachedRepository = new CachedTreeRepository($this->mockRepository, $this->mockCache);
    }

    // findById tests
    public function testFindByIdReturnsCachedValueWhenAvailable(): void
    {
        $tree = new Tree(1, 'Test Tree', null, null, null, true, new MockClock());

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree:1')
            ->willReturn($tree);

        $this->mockRepository->expects($this->never())
            ->method('findById');

        $result = $this->cachedRepository->findById(1);

        $this->assertSame($tree, $result);
    }

    public function testFindByIdFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $tree = new Tree(1, 'Test Tree', null, null, null, true, new MockClock());

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree:1')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($tree);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with('tree:1', $tree, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findById(1);

        $this->assertSame($tree, $result);
    }

    public function testFindByIdReturnsNullWhenTreeNotFound(): void
    {
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree:1')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->mockCache->expects($this->never())
            ->method('set');

        $result = $this->cachedRepository->findById(1);

        $this->assertNull($result);
    }

    // findActive tests
    public function testFindActiveReturnsCachedValueWhenAvailable(): void
    {
        $trees = [new Tree(1, 'Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:active')
            ->willReturn($trees);

        $this->mockRepository->expects($this->never())
            ->method('findActive');

        $result = $this->cachedRepository->findActive();

        $this->assertSame($trees, $result);
    }

    public function testFindActiveFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $trees = [new Tree(1, 'Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:active')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findActive')
            ->willReturn($trees);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with('trees:active', $trees, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findActive();

        $this->assertSame($trees, $result);
    }

    // findDeleted tests
    public function testFindDeletedReturnsCachedValueWhenAvailable(): void
    {
        $trees = [new Tree(1, 'Deleted Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Deleted Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:deleted')
            ->willReturn($trees);

        $this->mockRepository->expects($this->never())
            ->method('findDeleted');

        $result = $this->cachedRepository->findDeleted();

        $this->assertSame($trees, $result);
    }

    public function testFindDeletedFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $trees = [new Tree(1, 'Deleted Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Deleted Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:deleted')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findDeleted')
            ->willReturn($trees);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with('trees:deleted', $trees, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findDeleted();

        $this->assertSame($trees, $result);
    }

    // findTreeStructure tests
    public function testFindTreeStructureReturnsCachedValueWhenAvailable(): void
    {
        $tree = new Tree(1, 'Tree with structure', null, null, null, true, new MockClock());

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree_structure:1')
            ->willReturn($tree);

        $this->mockRepository->expects($this->never())
            ->method('findTreeStructure');

        $result = $this->cachedRepository->findTreeStructure(1);

        $this->assertSame($tree, $result);
    }

    public function testFindTreeStructureFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $tree = new Tree(1, 'Tree with structure', null, null, null, true, new MockClock());

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree_structure:1')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findTreeStructure')
            ->with(1)
            ->willReturn($tree);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with('tree_structure:1', $tree, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findTreeStructure(1);

        $this->assertSame($tree, $result);
    }

    public function testFindTreeStructureReturnsNullWhenTreeNotFound(): void
    {
        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('tree_structure:1')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findTreeStructure')
            ->with(1)
            ->willReturn(null);

        $this->mockCache->expects($this->never())
            ->method('set');

        $result = $this->cachedRepository->findTreeStructure(1);

        $this->assertNull($result);
    }

    // findByName tests
    public function testFindByNameReturnsCachedValueWhenAvailable(): void
    {
        $tree = new Tree(1, 'Test Tree', null, null, null, true, new MockClock());
        $expectedCacheKey = 'tree_name:' . md5('Test Tree');

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn($tree);

        $this->mockRepository->expects($this->never())
            ->method('findByName');

        $result = $this->cachedRepository->findByName('Test Tree');

        $this->assertSame($tree, $result);
    }

    public function testFindByNameFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $tree = new Tree(1, 'Test Tree', null, null, null, true, new MockClock());
        $expectedCacheKey = 'tree_name:' . md5('Test Tree');

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByName')
            ->with('Test Tree')
            ->willReturn($tree);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with($expectedCacheKey, $tree, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findByName('Test Tree');

        $this->assertSame($tree, $result);
    }

    public function testFindByNameReturnsNullWhenTreeNotFound(): void
    {
        $expectedCacheKey = 'tree_name:' . md5('Nonexistent Tree');

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByName')
            ->with('Nonexistent Tree')
            ->willReturn(null);

        $this->mockCache->expects($this->never())
            ->method('set');

        $result = $this->cachedRepository->findByName('Nonexistent Tree');

        $this->assertNull($result);
    }

    // findAll tests
    public function testFindAllReturnsCachedValueWhenAvailable(): void
    {
        $trees = [new Tree(1, 'Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:all')
            ->willReturn($trees);

        $this->mockRepository->expects($this->never())
            ->method('findAll');

        $result = $this->cachedRepository->findAll();

        $this->assertSame($trees, $result);
    }

    public function testFindAllFetchesFromRepositoryAndCachesWhenNotCached(): void
    {
        $trees = [new Tree(1, 'Tree 1', null, null, null, true, new MockClock()), new Tree(2, 'Tree 2', null, null, null, true, new MockClock())];

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with('trees:all')
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($trees);

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with('trees:all', $trees, 3600)
            ->willReturn(true);

        $result = $this->cachedRepository->findAll();

        $this->assertSame($trees, $result);
    }

    // save tests
    public function testSaveCallsRepositoryAndInvalidatesRelevantCaches(): void
    {
        $tree = new Tree(1, 'Test Tree', null, null, null, true, new MockClock());

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($tree);

        // Expect cache invalidation calls in correct order
        $this->mockCache->expects($this->exactly(6))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['tree_structure:1'],
                ['tree_name:' . md5('Test Tree')],
                ['trees:active'],
                ['trees:deleted'],
                ['trees:all']
            );

        $this->cachedRepository->save($tree);
    }

    public function testSaveWithNewTreeOnlyInvalidatesListCaches(): void
    {
        $tree = new Tree(null, 'New Tree', null, null, null, true, new MockClock());

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($tree);

        // Expect only list cache invalidation for new trees
        $this->mockCache->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(
                ['tree_name:' . md5('New Tree')],
                ['trees:active'],
                ['trees:deleted'],
                ['trees:all']
            );

        $this->cachedRepository->save($tree);
    }

    // delete tests
    public function testDeleteCallsRepositoryAndInvalidatesCaches(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->mockCache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['trees:active'],
                ['trees:deleted']
            );

        $this->cachedRepository->delete(1);
    }

    // softDelete tests
    public function testSoftDeleteCallsRepositoryAndInvalidatesCaches(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('softDelete')
            ->with(1);

        $this->mockCache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['trees:active'],
                ['trees:deleted']
            );

        $this->cachedRepository->softDelete(1);
    }

    // restore tests
    public function testRestoreCallsRepositoryAndInvalidatesCaches(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('restore')
            ->with(1);

        $this->mockCache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['trees:active'],
                ['trees:deleted']
            );

        $this->cachedRepository->restore(1);
    }

    // deleteByTreeId tests
    public function testDeleteByTreeIdCallsRepositoryAndInvalidatesCaches(): void
    {
        $this->mockRepository->expects($this->once())
            ->method('deleteByTreeId')
            ->with(1);

        $this->mockCache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['trees:active'],
                ['trees:deleted']
            );

        $this->cachedRepository->deleteByTreeId(1);
    }

    // Cache invalidation edge cases
    public function testSaveWithTreeWithoutNameDoesNotInvalidateNameCache(): void
    {
        $tree = new Tree(1, '', null, null, null, true, new MockClock());

        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($tree);

        // Should not include name cache invalidation
        $this->mockCache->expects($this->exactly(5))
            ->method('delete')
            ->withConsecutive(
                ['tree:1'],
                ['tree_structure:1'],
                ['trees:active'],
                ['trees:deleted'],
                ['trees:all']
            );

        $this->cachedRepository->save($tree);
    }

    public function testCacheKeyGenerationForSpecialCharactersInName(): void
    {
        $specialName = 'Tree with @#$%^&*() special chars';
        $expectedCacheKey = 'tree_name:' . md5($specialName);

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByName')
            ->with($specialName)
            ->willReturn(null);

        $result = $this->cachedRepository->findByName($specialName);

        $this->assertNull($result);
    }

    public function testCacheKeyGenerationForUnicodeCharactersInName(): void
    {
        $unicodeName = 'Árbol con acentos 中文 русский';
        $expectedCacheKey = 'tree_name:' . md5($unicodeName);

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($expectedCacheKey)
            ->willReturn(null);

        $this->mockRepository->expects($this->once())
            ->method('findByName')
            ->with($unicodeName)
            ->willReturn(null);

        $result = $this->cachedRepository->findByName($unicodeName);

        $this->assertNull($result);
    }
}
