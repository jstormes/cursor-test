<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\Tree\ViewTreeAction;
use App\Application\Actions\Tree\ViewTreeJsonAction;
use App\Application\Actions\Tree\ViewTreesAction;
use App\Application\Actions\Tree\ViewTreeByIdAction;
use App\Application\Actions\Tree\ViewTreeByIdJsonAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/trees', ViewTreesAction::class);
    $app->get('/tree', ViewTreeAction::class);
    $app->get('/tree/json', ViewTreeJsonAction::class);
    $app->get('/tree/{id}', ViewTreeByIdAction::class);
    $app->get('/tree/{id}/json', ViewTreeByIdJsonAction::class);
};
