<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tree;

use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use App\Infrastructure\Cache\CacheInterface;

class CachedTreeRepository implements TreeRepository
{
    private const CACHE_TTL = 3600; // 1 hour
    private const ACTIVE_TREES_KEY = 'trees:active';
    private const DELETED_TREES_KEY = 'trees:deleted';

    public function __construct(
        private TreeRepository $repository,
        private CacheInterface $cache
    ) {
    }

    #[\Override]
    public function findById(int $id): ?Tree
    {
        $cacheKey = "tree:{$id}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $tree = $this->repository->findById($id);
        if ($tree !== null) {
            $this->cache->set($cacheKey, $tree, self::CACHE_TTL);
        }

        return $tree;
    }

    #[\Override]
    public function findActive(): array
    {
        $cached = $this->cache->get(self::ACTIVE_TREES_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $trees = $this->repository->findActive();
        $this->cache->set(self::ACTIVE_TREES_KEY, $trees, self::CACHE_TTL);

        return $trees;
    }

    #[\Override]
    public function findDeleted(): array
    {
        $cached = $this->cache->get(self::DELETED_TREES_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $trees = $this->repository->findDeleted();
        $this->cache->set(self::DELETED_TREES_KEY, $trees, self::CACHE_TTL);

        return $trees;
    }

    #[\Override]
    public function save(Tree $tree): void
    {
        $this->repository->save($tree);

        // Invalidate relevant caches
        $this->invalidateTreeCaches($tree);
    }

    #[\Override]
    public function delete(int $id): void
    {
        $this->repository->delete($id);

        // Invalidate caches
        $this->cache->delete("tree:{$id}");
        $this->cache->delete(self::ACTIVE_TREES_KEY);
        $this->cache->delete(self::DELETED_TREES_KEY);
    }

    #[\Override]
    public function softDelete(int $id): void
    {
        $this->repository->softDelete($id);

        // Invalidate caches
        $this->cache->delete("tree:{$id}");
        $this->cache->delete(self::ACTIVE_TREES_KEY);
        $this->cache->delete(self::DELETED_TREES_KEY);
    }

    #[\Override]
    public function restore(int $id): void
    {
        $this->repository->restore($id);

        // Invalidate caches
        $this->cache->delete("tree:{$id}");
        $this->cache->delete(self::ACTIVE_TREES_KEY);
        $this->cache->delete(self::DELETED_TREES_KEY);
    }

    #[\Override]
    public function deleteByTreeId(int $treeId): void
    {
        $this->repository->deleteByTreeId($treeId);
        
        // Invalidate caches
        $this->cache->delete("tree:{$treeId}");
        $this->cache->delete(self::ACTIVE_TREES_KEY);
        $this->cache->delete(self::DELETED_TREES_KEY);
    }

    #[\Override]
    public function findTreeStructure(int $treeId): ?Tree
    {
        $cacheKey = "tree_structure:{$treeId}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $tree = $this->repository->findTreeStructure($treeId);
        if ($tree !== null) {
            $this->cache->set($cacheKey, $tree, self::CACHE_TTL);
        }

        return $tree;
    }

    #[\Override]
    public function findByName(string $name): ?Tree
    {
        $cacheKey = "tree_name:" . md5($name);
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $tree = $this->repository->findByName($name);
        if ($tree !== null) {
            $this->cache->set($cacheKey, $tree, self::CACHE_TTL);
        }

        return $tree;
    }

    #[\Override]
    public function findAll(): array
    {
        $cacheKey = 'trees:all';
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $trees = $this->repository->findAll();
        $this->cache->set($cacheKey, $trees, self::CACHE_TTL);

        return $trees;
    }

    private function invalidateTreeCaches(Tree $tree): void
    {
        if ($tree->getId() !== null) {
            $this->cache->delete("tree:{$tree->getId()}");
            $this->cache->delete("tree_structure:{$tree->getId()}");
        }
        
        // Invalidate name cache if we know the name
        if ($tree->getName()) {
            $this->cache->delete("tree_name:" . md5($tree->getName()));
        }
        
        $this->cache->delete(self::ACTIVE_TREES_KEY);
        $this->cache->delete(self::DELETED_TREES_KEY);
        $this->cache->delete('trees:all');
    }
}
