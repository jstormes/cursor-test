<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected LoggerInterface $logger;

    protected Request $request;

    protected Response $response;

    protected array $args;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = 200): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON response');
        }
        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }

    protected function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    protected function generateTreeNotFoundHTML(int $treeId): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; 
            color: white; text-decoration: none; border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>Tree Not Found</h1>
    <p class="error">Tree with ID {$treeId} was not found in the database.</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    protected function generateErrorHTML(string $errorMessage, string $title = 'Error', string $backUrl = '/trees'): Response
    {
        $safeMessage = $this->escapeHtml($errorMessage);
        $safeTitle = $this->escapeHtml($title);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; 
            color: white; text-decoration: none; border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>{$safeTitle}</h1>
    <p class="error">{$safeMessage}</p>
    <a href="{$backUrl}" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }

    protected function generateNodeNotFoundHTML(int $treeId, int $nodeId): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Node Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; 
            color: white; text-decoration: none; border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>Node Not Found</h1>
    <p class="error">Node with ID {$nodeId} was not found in tree {$treeId}.</p>
    <a href="/trees" class="btn">Back to Trees List</a>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);
        return $this->response->withHeader('Content-Type', 'text/html');
    }
}
