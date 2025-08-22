<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Session\SessionManagerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware
{
    public function __construct(private SessionManagerInterface $sessionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->sessionManager->start();
            $request = $request->withAttribute('session', $this->sessionManager->all());
        }

        return $handler->handle($request);
    }
}
