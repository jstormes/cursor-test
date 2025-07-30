<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions;

use App\Application\Actions\Action;
use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class ActionTest extends TestCase
{
    private Action $action;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseFactory = new ResponseFactory();
        $this->action = new TestAction($this->logger, $this->responseFactory);
    }

    public function testActionErrorCreation(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not found');
        
        $this->assertEquals(ActionError::RESOURCE_NOT_FOUND, $error->getType());
        $this->assertEquals('Not found', $error->getDescription());
    }

    public function testActionErrorWithNullDescription(): void
    {
        $error = new ActionError(ActionError::SERVER_ERROR);
        
        $this->assertEquals(ActionError::SERVER_ERROR, $error->getType());
        $this->assertNull($error->getDescription());
    }

    public function testActionErrorSetType(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not found');
        $error->setType(ActionError::VALIDATION_ERROR);
        
        $this->assertEquals(ActionError::VALIDATION_ERROR, $error->getType());
    }

    public function testActionErrorSetDescription(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not found');
        $error->setDescription('Updated description');
        
        $this->assertEquals('Updated description', $error->getDescription());
    }

    public function testActionErrorJsonSerialize(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not found');
        $json = $error->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertEquals(ActionError::RESOURCE_NOT_FOUND, $json['type']);
        $this->assertEquals('Not found', $json['description']);
    }

    public function testActionPayloadCreation(): void
    {
        $data = ['test' => 'value'];
        $payload = new ActionPayload(200, $data);
        
        $this->assertEquals(200, $payload->getStatusCode());
        $this->assertEquals($data, $payload->getData());
        $this->assertNull($payload->getError());
    }

    public function testActionPayloadWithError(): void
    {
        $error = new ActionError(ActionError::SERVER_ERROR, 'Server error');
        $payload = new ActionPayload(500, null, $error);
        
        $this->assertEquals(500, $payload->getStatusCode());
        $this->assertNull($payload->getData());
        $this->assertEquals($error, $payload->getError());
    }

    public function testActionPayloadWithDataAndError(): void
    {
        $data = ['partial' => 'data'];
        $error = new ActionError(ActionError::VALIDATION_ERROR, 'Partial error');
        $payload = new ActionPayload(207, $data, $error);
        
        $this->assertEquals(207, $payload->getStatusCode());
        $this->assertEquals($data, $payload->getData());
        $this->assertEquals($error, $payload->getError());
    }

    public function testActionErrorTypes(): void
    {
        $types = [
            ActionError::BAD_REQUEST,
            ActionError::INSUFFICIENT_PRIVILEGES,
            ActionError::NOT_ALLOWED,
            ActionError::NOT_IMPLEMENTED,
            ActionError::RESOURCE_NOT_FOUND,
            ActionError::SERVER_ERROR,
            ActionError::UNAUTHENTICATED,
            ActionError::VALIDATION_ERROR,
            ActionError::VERIFICATION_ERROR
        ];

        foreach ($types as $type) {
            $error = new ActionError($type, 'Test error');
            $this->assertEquals($type, $error->getType());
        }
    }
}

// Test implementation of Action for testing
class TestAction extends Action
{
    public function action(): ResponseInterface
    {
        return $this->respondWithData(['test' => 'value']);
    }
}
