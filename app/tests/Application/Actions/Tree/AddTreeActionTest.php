<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\AddTreeAction;
use App\Application\Validation\TreeValidator;
use App\Application\Validation\ValidationResult;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\Tree;
use App\Infrastructure\Rendering\CssProviderInterface;
use App\Infrastructure\Time\ClockInterface;
use App\Tests\Utilities\MockClock;
use Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class AddTreeActionTest extends TestCase
{
    private AddTreeAction $action;
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private StreamInterface $stream;
    private LoggerInterface $logger;
    private TreeRepository $treeRepository;
    private TreeValidator $validator;
    private CssProviderInterface $cssProvider;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->treeRepository = $this->createMock(TreeRepository::class);
        $this->validator = $this->createMock(TreeValidator::class);
        $this->cssProvider = $this->createMock(CssProviderInterface::class);
        $this->clock = $this->createMock(ClockInterface::class);

        $this->action = new AddTreeAction(
            $this->logger,
            $this->treeRepository,
            $this->validator,
            $this->cssProvider,
            $this->clock
        );
    }

    public function testGetRequestShowsForm(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Add New Tree') &&
                       str_contains($html, '<form method="POST"') &&
                       str_contains($html, 'name="name"') &&
                       str_contains($html, 'name="description"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestCreatesTree(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'A test tree description'
            ]);

        // Mock validation success
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->validator->expects($this->once())
            ->method('sanitize')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'A test tree description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                return $tree instanceof Tree &&
                       $tree->getName() === 'Test Tree' &&
                       $tree->getDescription() === 'A test tree description';
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree Created Successfully') &&
                       str_contains($html, 'Test Tree');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithEmptyName(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => '',
                'description' => 'A description'
            ]);

        // Mock validation failure
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $validationResult->expects($this->once())
            ->method('getErrors')
            ->willReturn(['name' => ['Tree name is required']]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree name is required');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithTooLongName(): void
    {
        $longName = str_repeat('A', 256);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => $longName,
                'description' => 'A description'
            ]);

        // Mock validation failure
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $validationResult->expects($this->once())
            ->method('getErrors')
            ->willReturn(['name' => ['Tree name must not exceed 255 characters']]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree name must not exceed 255 characters');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithTooLongDescription(): void
    {
        $longDescription = str_repeat('A', 1001);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Valid Tree Name',
                'description' => $longDescription
            ]);

        // Mock validation failure
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $validationResult->expects($this->once())
            ->method('getErrors')
            ->willReturn(['description' => ['Description must not exceed 1000 characters']]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Description must not exceed 1000 characters');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithDuplicateName(): void
    {
        $existingTree = new Tree(1, 'Test Tree', 'Existing', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'New description'
            ]);

        // Mock validation success
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->validator->expects($this->once())
            ->method('sanitize')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => 'New description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$existingTree]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'A tree with this name already exists');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithCaseInsensitiveDuplicateName(): void
    {
        $existingTree = new Tree(1, 'Test Tree', 'Existing', new DateTime(), new DateTime(), true, new MockClock());

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'TEST TREE',
                'description' => 'New description'
            ]);

        // Mock validation success
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->validator->expects($this->once())
            ->method('sanitize')
            ->willReturn([
                'name' => 'TEST TREE',
                'description' => 'New description'
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([$existingTree]);

        $this->treeRepository->expects($this->never())
            ->method('save');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'A tree with this name already exists');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestWithEmptyDescription(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => ''
            ]);

        // Mock validation success
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($validationResult);

        $this->validator->expects($this->once())
            ->method('sanitize')
            ->willReturn([
                'name' => 'Test Tree',
                'description' => null
            ]);

        $this->treeRepository->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->treeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($tree) {
                return $tree instanceof Tree &&
                       $tree->getName() === 'Test Tree' &&
                       $tree->getDescription() === null;
            }));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Tree Created Successfully');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testUnsupportedHttpMethod(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('DELETE');

        $this->response->expects($this->once())
            ->method('withStatus')
            ->with(405)
            ->willReturnSelf();

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetRequestExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        // First call throws exception, second call for error HTML succeeds
        $this->response->expects($this->exactly(2))
            ->method('getBody')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \Exception('Stream error')),
                $this->stream
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error showing add tree form: Stream error'));

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Error Adding Tree') &&
                       str_contains($html, 'Stream error');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testPostRequestExceptionHandling(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willThrowException(new \Exception('Parse error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error creating tree: Parse error'));

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, 'Error Adding Tree') &&
                       str_contains($html, 'An error occurred while creating the tree. Please try again.');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFormHtmlStructure(): void
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->stream);

        $this->response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'text/html')
            ->willReturnSelf();

        $this->stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($html) {
                return str_contains($html, '<!DOCTYPE html>') &&
                       str_contains($html, '<html lang="en">') &&
                       str_contains($html, '<head>') &&
                       str_contains($html, '<body>') &&
                       str_contains($html, '<style>') &&
                       str_contains($html, 'maxlength="255"') &&
                       str_contains($html, 'maxlength="1000"');
            }));

        $result = $this->action->__invoke($this->request, $this->response, []);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
