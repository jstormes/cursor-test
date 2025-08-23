<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

abstract class AbstractJsonAction extends Action
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Handle errors and return JSON error response
     */
    protected function handleError(\Exception $e, string $title = 'Error'): Response
    {
        $this->logger->error($title . ': ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        $error = [
            'error' => true,
            'message' => $e->getMessage(),
            'title' => $title,
            'type' => get_class($e)
        ];

        // In development mode, include more details
        if (getenv('APP_ENV') === 'development') {
            $error['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ];
        }

        return $this->respondWithJson($error, 500);
    }

    /**
     * Return a JSON response
     */
    protected function respondWithJson($data, int $statusCode = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($payload);
        
        return $this->response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle validation errors and return JSON response
     */
    protected function handleValidationError(array $errors, string $title = 'Validation Error'): Response
    {
        $error = [
            'error' => true,
            'title' => $title,
            'validation_errors' => $errors
        ];
        
        return $this->respondWithJson($error, 400);
    }

    /**
     * Return success response with data
     */
    protected function respondWithSuccess($data = null, string $message = 'Success'): Response
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->respondWithJson($response);
    }
}