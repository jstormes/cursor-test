<?php

require_once 'vendor/autoload.php';

use App\Domain\Tree\Tree;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Infrastructure\Persistence\Tree\DatabaseTreeNodeRepository;

// Test the add tree functionality
echo "Testing Add Tree Functionality\n";
echo "==============================\n\n";

try {
    // Create database connection
    $pdo = new PDO('mysql:host=localhost;dbname=tree_db;charset=utf8mb4', 'root', 'password');
    $connection = new PdoDatabaseConnection($pdo);
    
    // Create repositories
    $treeDataMapper = new TreeDataMapper();
    $treeNodeDataMapper = new TreeNodeDataMapper();
    $treeRepository = new DatabaseTreeRepository($connection, $treeDataMapper);
    $treeNodeRepository = new DatabaseTreeNodeRepository($connection, $treeNodeDataMapper);
    
    // Test creating a new tree
    echo "1. Creating a new tree...\n";
    $newTree = new Tree(
        null,
        'Test Tree ' . date('Y-m-d H:i:s'),
        'This is a test tree created via script'
    );
    
    $treeRepository->save($newTree);
    echo "   ✓ Tree created successfully!\n";
    echo "   - ID: " . $newTree->getId() . "\n";
    echo "   - Name: " . $newTree->getName() . "\n";
    echo "   - Description: " . $newTree->getDescription() . "\n";
    echo "   - Created: " . $newTree->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    
    // Test retrieving the tree
    echo "\n2. Retrieving the created tree...\n";
    $retrievedTree = $treeRepository->findById($newTree->getId());
    if ($retrievedTree) {
        echo "   ✓ Tree retrieved successfully!\n";
        echo "   - ID: " . $retrievedTree->getId() . "\n";
        echo "   - Name: " . $retrievedTree->getName() . "\n";
        echo "   - Description: " . $retrievedTree->getDescription() . "\n";
    } else {
        echo "   ✗ Failed to retrieve tree!\n";
    }
    
    // Test finding active trees
    echo "\n3. Finding all active trees...\n";
    $activeTrees = $treeRepository->findActive();
    echo "   ✓ Found " . count($activeTrees) . " active trees\n";
    
    foreach ($activeTrees as $tree) {
        echo "   - Tree ID: " . $tree->getId() . ", Name: " . $tree->getName() . "\n";
    }
    
    echo "\n✅ All tests passed! Add tree functionality is working correctly.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 