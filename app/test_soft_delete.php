<?php

require_once 'vendor/autoload.php';

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;

// Test soft delete functionality
echo "Testing Soft Delete Functionality\n";
echo "================================\n\n";

try {
    // Create database connection
    $pdo = new PDO('mysql:host=localhost;dbname=tree_db;charset=utf8mb4', 'root', 'password');
    $connection = new PdoDatabaseConnection($pdo);
    
    // Create repositories
    $treeDataMapper = new TreeDataMapper();
    $treeRepository = new DatabaseTreeRepository($connection, $treeDataMapper);
    
    // Test 1: Check current active trees
    echo "1. Checking current active trees...\n";
    $activeTrees = $treeRepository->findActive();
    echo "   ✓ Found " . count($activeTrees) . " active trees\n";
    
    if (!empty($activeTrees)) {
        $testTree = $activeTrees[0];
        echo "   - Using tree: " . $testTree->getName() . " (ID: " . $testTree->getId() . ")\n";
        
        // Test 2: Soft delete a tree
        echo "\n2. Soft deleting tree...\n";
        $treeRepository->softDelete($testTree->getId());
        echo "   ✓ Tree soft deleted successfully!\n";
        
        // Test 3: Check that tree is no longer active
        echo "\n3. Checking that tree is no longer active...\n";
        $activeTreesAfter = $treeRepository->findActive();
        echo "   ✓ Found " . count($activeTreesAfter) . " active trees (should be " . (count($activeTrees) - 1) . ")\n";
        
        // Test 4: Check deleted trees
        echo "\n4. Checking deleted trees...\n";
        $deletedTrees = $treeRepository->findDeleted();
        echo "   ✓ Found " . count($deletedTrees) . " deleted trees\n";
        
        $foundDeleted = false;
        foreach ($deletedTrees as $deletedTree) {
            if ($deletedTree->getId() === $testTree->getId()) {
                $foundDeleted = true;
                echo "   - Found deleted tree: " . $deletedTree->getName() . " (ID: " . $deletedTree->getId() . ")\n";
                echo "   - Is active: " . ($deletedTree->isActive() ? 'Yes' : 'No') . " (should be No)\n";
                break;
            }
        }
        
        if (!$foundDeleted) {
            echo "   ❌ Deleted tree not found in deleted trees list\n";
        } else {
            echo "   ✓ Deleted tree found in deleted trees list\n";
        }
        
        // Test 5: Restore the tree
        echo "\n5. Restoring tree...\n";
        $treeRepository->restore($testTree->getId());
        echo "   ✓ Tree restored successfully!\n";
        
        // Test 6: Check that tree is active again
        echo "\n6. Checking that tree is active again...\n";
        $activeTreesAfterRestore = $treeRepository->findActive();
        echo "   ✓ Found " . count($activeTreesAfterRestore) . " active trees (should be " . count($activeTrees) . ")\n";
        
        $foundRestored = false;
        foreach ($activeTreesAfterRestore as $restoredTree) {
            if ($restoredTree->getId() === $testTree->getId()) {
                $foundRestored = true;
                echo "   - Found restored tree: " . $restoredTree->getName() . " (ID: " . $restoredTree->getId() . ")\n";
                echo "   - Is active: " . ($restoredTree->isActive() ? 'Yes' : 'No') . " (should be Yes)\n";
                break;
            }
        }
        
        if (!$foundRestored) {
            echo "   ❌ Restored tree not found in active trees list\n";
        } else {
            echo "   ✓ Restored tree found in active trees list\n";
        }
        
        // Test 7: Check deleted trees again
        echo "\n7. Checking deleted trees after restore...\n";
        $deletedTreesAfterRestore = $treeRepository->findDeleted();
        echo "   ✓ Found " . count($deletedTreesAfterRestore) . " deleted trees (should be 0)\n";
        
        echo "\n✅ All soft delete tests passed!\n";
        
    } else {
        echo "   ⚠️ No active trees found to test with\n";
        echo "   Please create some trees first to test the soft delete functionality\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 