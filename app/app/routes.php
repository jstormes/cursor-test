<?php

declare(strict_types=1);

use App\Application\Actions\Tree\ViewTreeAction;
use App\Application\Actions\Tree\ViewTreeJsonAction;
use App\Application\Actions\Tree\ViewTreesAction;
use App\Application\Actions\Tree\ViewTreesJsonAction;
use App\Application\Actions\Tree\ViewTreeByIdAction;
use App\Application\Actions\Tree\ViewTreeByIdReadOnlyAction;
use App\Application\Actions\Tree\ViewTreeByIdJsonAction;
use App\Application\Actions\Tree\AddNodeAction;
use App\Application\Actions\Tree\AddNodeJsonAction;
use App\Application\Actions\Tree\AddTreeAction;
use App\Application\Actions\Tree\AddTreeJsonAction;
use App\Application\Actions\Tree\DeleteTreeAction;
use App\Application\Actions\Tree\DeleteTreeJsonAction;
use App\Application\Actions\Tree\RestoreTreeAction;
use App\Application\Actions\Tree\RestoreTreeJsonAction;
use App\Application\Actions\Tree\ViewDeletedTreesAction;
use App\Application\Actions\Tree\ViewDeletedTreesJsonAction;
use App\Application\Actions\Tree\DeleteNodeAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', ViewTreesAction::class);

    $app->get('/trees', ViewTreesAction::class);
    $app->get('/trees/json', ViewTreesJsonAction::class);
    $app->get('/trees/deleted', ViewDeletedTreesAction::class);
    $app->get('/trees/deleted/json', ViewDeletedTreesJsonAction::class);
    $app->get('/tree', ViewTreeAction::class);
    $app->get('/tree/json', ViewTreeJsonAction::class);
    
    // Add tree routes (specific routes before parameterized)
    $app->map(['GET', 'POST'], '/tree/add', AddTreeAction::class);
    $app->post('/tree/add/json', AddTreeJsonAction::class);
    
    // View tree routes (parameterized routes after specific)
    $app->get('/tree/{id}/view', ViewTreeByIdReadOnlyAction::class);
    $app->get('/tree/{id}', ViewTreeByIdAction::class);
    $app->get('/tree/{id}/json', ViewTreeByIdJsonAction::class);
    
    // Delete and restore tree routes
    $app->map(['GET', 'POST'], '/tree/{id}/delete', DeleteTreeAction::class);
    $app->post('/tree/{id}/delete/json', DeleteTreeJsonAction::class);
    $app->map(['GET', 'POST'], '/tree/{id}/restore', RestoreTreeAction::class);
    $app->post('/tree/{id}/restore/json', RestoreTreeJsonAction::class);
    
    // Add node routes
    $app->map(['GET', 'POST'], '/tree/{treeId}/add-node', AddNodeAction::class);
    $app->post('/tree/{treeId}/add-node/json', AddNodeJsonAction::class);
    
    // Delete node routes
    $app->map(['GET', 'POST'], '/tree/{treeId}/node/{nodeId}/delete', DeleteNodeAction::class);
};
