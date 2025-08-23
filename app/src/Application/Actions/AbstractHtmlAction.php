<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Infrastructure\Rendering\HtmlRendererInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

abstract class AbstractHtmlAction extends Action
{
    protected HtmlRendererInterface $htmlRenderer;

    public function __construct(
        LoggerInterface $logger,
        HtmlRendererInterface $htmlRenderer
    ) {
        parent::__construct($logger);
        $this->htmlRenderer = $htmlRenderer;
    }

    /**
     * Handle errors and return HTML error response
     */
    protected function handleError(\Exception $e, string $title = 'Error'): Response
    {
        $this->logger->error($title . ': ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        $html = $this->htmlRenderer->renderError($e->getMessage(), $title);
        $this->response->getBody()->write($html);
        return $this->response
            ->withStatus(500)
            ->withHeader('Content-Type', 'text/html');
    }

    /**
     * Return an HTML response
     */
    protected function respondWithHtml(string $html, int $statusCode = 200): Response
    {
        $this->response->getBody()->write($html);
        return $this->response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'text/html');
    }

    /**
     * Handle validation errors and return HTML response
     */
    protected function handleValidationError(array $errors, string $title = 'Validation Error'): Response
    {
        $errorMessage = implode('<br>', $errors);
        $html = $this->htmlRenderer->renderError($errorMessage, $title);
        
        return $this->respondWithHtml($html, 400);
    }
}