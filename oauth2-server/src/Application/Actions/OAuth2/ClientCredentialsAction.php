<?php

declare(strict_types=1);

namespace App\Application\Actions\OAuth2;

use App\Application\Actions\Action;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class ClientCredentialsAction extends Action
{
    private AuthorizationServer $authorizationServer;

    public function __construct(LoggerInterface $logger, AuthorizationServer $authorizationServer)
    {
        parent::__construct($logger);
        $this->authorizationServer = $authorizationServer;
    }

    protected function action(): Response
    {
        try {
            // Try to respond to the access token request
            return $this->authorizationServer->respondToAccessTokenRequest($this->request, $this->response);
        } catch (OAuthServerException $exception) {
            // All instances of OAuthServerException can be formatted into a HTTP response
            return $exception->generateHttpResponse($this->response);
        } catch (\Exception $exception) {
            // Unknown exception - let the error handler deal with it
            throw $exception;
        }
    }
}