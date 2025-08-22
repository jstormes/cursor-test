<?php

declare(strict_types=1);

use App\Application\Middleware\PerformanceMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(PerformanceMiddleware::class);
};
