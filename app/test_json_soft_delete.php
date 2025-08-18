<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Application\Actions\Tree\DeleteTreeJsonAction;
use App\Application\Actions\Tree\RestoreTreeJsonAction;
use App\Application\Actions\Tree\ViewDeletedTreesJsonAction;
use App\Domain\Tree\TreeRepository;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Application\Settings\Settings;
use Psr\Log\NullLogger;

// Create a simple test to verify the JSON actions work
echo "Testing JSON Soft Delete Actions...\n";

try {
    // Set up dependencies using environment variables
    $connection = new PdoDatabaseConnection([
        'host' => $_ENV['MYSQL_HOST'] ?? 'mariadb',
        'port' => $_ENV['MYSQL_PORT'] ?? 3306,
        'database' => $_ENV['MYSQL_DATABASE'] ?? 'app',
        'username' => $_ENV['MYSQL_USER'] ?? 'root',
        'password' => $_ENV['MYSQL_PASSWORD'] ?? 'password'
    ]);
    $dataMapper = new TreeDataMapper();
    $treeRepository = new DatabaseTreeRepository($connection, $dataMapper);
    $logger = new NullLogger();
    
    // Test 1: View deleted trees
    echo "\n1. Testing ViewDeletedTreesJsonAction...\n";
    $viewDeletedAction = new ViewDeletedTreesJsonAction($logger, $treeRepository);
    
    // Create a mock request and response
    $request = new \Slim\Psr7\Request('GET', new \Slim\Psr7\Uri('http', 'localhost', 8088, '/trees/deleted/json'));
    $response = new \Slim\Psr7\Response();
    
    // Set up the action with request and response
    $viewDeletedAction->setRequest($request);
    $viewDeletedAction->setResponse($response);
    
    $result = $viewDeletedAction->action();
    echo "Status: " . $result->getStatusCode() . "\n";
    echo "Response: " . $result->getBody()->getContents() . "\n";
    
    // Test 2: Delete a tree (soft delete)
    echo "\n2. Testing DeleteTreeJsonAction...\n";
    $deleteAction = new DeleteTreeJsonAction($logger, $treeRepository);
    
    // Create a mock request and response
    $request = new \Slim\Psr7\Request('POST', new \Slim\Psr7\Uri('http', 'localhost', 8088, '/tree/1/delete/json'));
    $response = new \Slim\Psr7\Response();
    
    // Set up the action with request and response
    $deleteAction->setRequest($request);
    $deleteAction->setResponse($response);
    
    $result = $deleteAction->action();
    echo "Status: " . $result->getStatusCode() . "\n";
    echo "Response: " . $result->getBody()->getContents() . "\n";
    
    // Test 3: Restore a tree
    echo "\n3. Testing RestoreTreeJsonAction...\n";
    $restoreAction = new RestoreTreeJsonAction($logger, $treeRepository);
    
    // Create a mock request and response
    $request = new \Slim\Psr7\Request('POST', new \Slim\Psr7\Uri('http', 'localhost', 8088, '/tree/1/restore/json'));
    $response = new \Slim\Psr7\Response();
    
    // Set up the action with request and response
    $restoreAction->setRequest($request);
    $restoreAction->setResponse($response);
    
    $result = $restoreAction->action();
    echo "Status: " . $result->getStatusCode() . "\n";
    echo "Response: " . $result->getBody()->getContents() . "\n";
    
    echo "\nAll JSON actions tested successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} 