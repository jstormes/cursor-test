<?php

require_once 'vendor/autoload.php';

use App\Domain\Tree\Tree;
use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Infrastructure\Persistence\Tree\DatabaseTreeNodeRepository;

// Test adding nodes to empty trees
echo "Testing Add Node to Empty Tree\n";
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
    
    // Test creating a new empty tree
    echo "1. Creating a new empty tree...\n";
    $newTree = new Tree(
        null,
        'Test Empty Tree ' . date('Y-m-d H:i:s'),
        'This is a test empty tree'
    );
    
    $treeRepository->save($newTree);
    echo "   ✓ Empty tree created successfully!\n";
    echo "   - ID: " . $newTree->getId() . "\n";
    echo "   - Name: " . $newTree->getName() . "\n";
    
    // Test that the tree has no nodes initially
    echo "\n2. Checking that tree has no nodes...\n";
    $nodes = $treeNodeRepository->findByTreeId($newTree->getId());
    echo "   ✓ Tree has " . count($nodes) . " nodes (should be 0)\n";
    
    // Test adding a root node to empty tree
    echo "\n3. Adding a root node to empty tree...\n";
    $rootNode = new SimpleNode(
        null,
        'First Root Node',
        $newTree->getId(),
        null, // No parent = root node
        0
    );
    
    $treeNodeRepository->save($rootNode);
    echo "   ✓ Root node created successfully!\n";
    echo "   - Node ID: " . $rootNode->getId() . "\n";
    echo "   - Node Name: " . $rootNode->getName() . "\n";
    echo "   - Parent ID: " . ($rootNode->getParentId() ?? 'null') . " (should be null for root)\n";
    
    // Test adding a child node
    echo "\n4. Adding a child node...\n";
    $childNode = new ButtonNode(
        null,
        'Child Button Node',
        $newTree->getId(),
        $rootNode->getId(), // Parent is the root node
        1
    );
    
    $childNode->setButtonText('Click Me');
    $childNode->setButtonAction('alert("Hello!")');
    
    $treeNodeRepository->save($childNode);
    echo "   ✓ Child node created successfully!\n";
    echo "   - Node ID: " . $childNode->getId() . "\n";
    echo "   - Node Name: " . $childNode->getName() . "\n";
    echo "   - Parent ID: " . $childNode->getParentId() . " (should be " . $rootNode->getId() . ")\n";
    echo "   - Button Text: " . $childNode->getButtonText() . "\n";
    echo "   - Button Action: " . $childNode->getButtonAction() . "\n";
    
    // Test retrieving all nodes for the tree
    echo "\n5. Retrieving all nodes for the tree...\n";
    $allNodes = $treeNodeRepository->findByTreeId($newTree->getId());
    echo "   ✓ Found " . count($allNodes) . " nodes in tree\n";
    
    foreach ($allNodes as $node) {
        echo "   - Node: " . $node->getName() . " (ID: " . $node->getId() . ", Parent: " . ($node->getParentId() ?? 'null') . ")\n";
    }
    
    echo "\n✅ All tests passed! Adding nodes to empty trees works correctly.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 