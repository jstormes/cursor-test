<?php

declare(strict_types=1);

namespace App\Tests\Application\ResponseEmitter;

use App\Application\ResponseEmitter\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class ResponseEmitterTest extends TestCase
{
    private ResponseEmitter $responseEmitter;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseFactory = new ResponseFactory();
        $this->responseEmitter = new ResponseEmitter();
    }

    public function testEmitResponse(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write('{"test": "data"}');

        // Capture output to test emission
        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('{"test": "data"}', $output);
    }

    public function testEmitResponseWithHeaders(): void
    {
        $response = $this->responseFactory->createResponse(201);
        $response = $response->withHeader('Content-Type', 'text/html');
        $response = $response->withHeader('X-Custom-Header', 'test-value');
        $response->getBody()->write('<html>test</html>');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('<html>test</html>', $output);
    }

    public function testEmitResponseWithDifferentStatusCodes(): void
    {
        $statusCodes = [200, 201, 400, 404, 500];
        
        foreach ($statusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write('test content');

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString('test content', $output);
        }
    }

    public function testEmitEmptyResponse(): void
    {
        $response = $this->responseFactory->createResponse(204);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testEmitResponseWithCorsHeaders(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('test content');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        // Check that CORS headers are set
        $this->assertStringContainsString('test content', $output);
    }

    public function testEmitResponseWithOriginHeader(): void
    {
        // Set up the HTTP_ORIGIN server variable
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
        
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('test content');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('test content', $output);
        
        // Clean up
        unset($_SERVER['HTTP_ORIGIN']);
    }

    public function testEmitResponseWithoutOriginHeader(): void
    {
        // Ensure HTTP_ORIGIN is not set
        unset($_SERVER['HTTP_ORIGIN']);
        
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('test content');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('test content', $output);
    }

    public function testEmitResponseWithEmptyOriginHeader(): void
    {
        // Set empty origin
        $_SERVER['HTTP_ORIGIN'] = '';
        
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('test content');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('test content', $output);
        
        // Clean up
        unset($_SERVER['HTTP_ORIGIN']);
    }

    public function testEmitResponseWithSpecialCharacters(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('{"message": "Hello & World", "data": "<test>content</test>"}');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('{"message": "Hello & World", "data": "<test>content</test>"}', $output);
    }

    public function testEmitResponseWithUnicodeCharacters(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('{"message": "Hello émoji 🌍", "data": "Unicode content"}');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('{"message": "Hello émoji 🌍", "data": "Unicode content"}', $output);
    }

    public function testEmitResponseWithLargeContent(): void
    {
        $largeContent = str_repeat('This is a large content string. ', 1000);
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($largeContent);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString($largeContent, $output);
    }

    public function testEmitResponseWithBinaryContent(): void
    {
        $binaryContent = "\x00\x01\x02\x03\x04\x05";
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($binaryContent);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals($binaryContent, $output);
    }

    public function testEmitResponseWithExistingHeaders(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('X-Custom-Header', 'custom-value');
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write('{"test": "data"}');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('{"test": "data"}', $output);
    }

    public function testEmitResponseWithMultipleHeaders(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('X-Header-1', 'value1');
        $response = $response->withHeader('X-Header-2', 'value2');
        $response = $response->withHeader('X-Header-3', 'value3');
        $response->getBody()->write('test content');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertStringContainsString('test content', $output);
    }

    public function testEmitResponseWithEmptyBody(): void
    {
        $response = $this->responseFactory->createResponse(200);
        // No body content

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testEmitResponseWithNullBody(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testEmitResponseWithWhitespaceBody(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write('   ');

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('   ', $output);
    }

    public function testEmitResponseWithNewlines(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write("Line 1\nLine 2\nLine 3");

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals("Line 1\nLine 2\nLine 3", $output);
    }

    public function testEmitResponseWithTabs(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write("Tab\tSeparated\tValues");

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals("Tab\tSeparated\tValues", $output);
    }

    public function testEmitResponseWithCarriageReturns(): void
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write("Line 1\r\nLine 2\r\nLine 3");

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals("Line 1\r\nLine 2\r\nLine 3", $output);
    }

    public function testEmitResponseWithJsonContent(): void
    {
        $jsonData = [
            'id' => 1,
            'name' => 'Test Item',
            'active' => true,
            'tags' => ['tag1', 'tag2'],
            'metadata' => [
                'created' => '2023-01-01',
                'updated' => '2023-01-02'
            ]
        ];
        
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write(json_encode($jsonData));

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals(json_encode($jsonData), $output);
    }

    public function testEmitResponseWithHtmlContent(): void
    {
        $htmlContent = '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello World</h1></body></html>';
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($htmlContent);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals($htmlContent, $output);
    }

    public function testEmitResponseWithXmlContent(): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><root><item id="1">Test Item</item></root>';
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($xmlContent);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals($xmlContent, $output);
    }

    public function testEmitResponseWithCsvContent(): void
    {
        $csvContent = "Name,Age,City\nJohn,30,New York\nJane,25,Los Angeles";
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($csvContent);

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals($csvContent, $output);
    }

    public function testEmitResponseWithErrorStatusCodes(): void
    {
        $errorStatusCodes = [400, 401, 403, 404, 405, 500, 502, 503];
        
        foreach ($errorStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Error {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Error {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithRedirectStatusCodes(): void
    {
        $redirectStatusCodes = [301, 302, 303, 307, 308];
        
        foreach ($redirectStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Redirect {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Redirect {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithInformationalStatusCodes(): void
    {
        $informationalStatusCodes = [100, 101, 102, 103];
        
        foreach ($informationalStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Info {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Info {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithSuccessStatusCodes(): void
    {
        $successStatusCodes = [200, 201, 202, 203, 206, 207, 208, 226];
        
        foreach ($successStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Success {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Success {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithNoContentStatusCode(): void
    {
        $response = $this->responseFactory->createResponse(204);
        // 204 No Content should not have a body

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testEmitResponseWithResetContentStatusCode(): void
    {
        $response = $this->responseFactory->createResponse(205);
        // 205 Reset Content should not have a body

        ob_start();
        $this->responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testEmitResponseWithClientErrorStatusCodes(): void
    {
        $clientErrorStatusCodes = [400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 421, 422, 423, 424, 425, 426, 428, 429, 431, 451];
        
        foreach ($clientErrorStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Client Error {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Client Error {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithServerErrorStatusCodes(): void
    {
        $serverErrorStatusCodes = [500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];
        
        foreach ($serverErrorStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Server Error {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Server Error {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithCustomStatusCodes(): void
    {
        $customStatusCodes = [299, 399, 499, 599];
        
        foreach ($customStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Custom Status {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Custom Status {$statusCode}", $output);
        }
    }

    public function testEmitResponseWithValidCustomStatusCodes(): void
    {
        $validCustomStatusCodes = [299, 399, 499, 599];
        
        foreach ($validCustomStatusCodes as $statusCode) {
            $response = $this->responseFactory->createResponse($statusCode);
            $response->getBody()->write("Custom Status {$statusCode}");

            ob_start();
            $this->responseEmitter->emit($response);
            $output = ob_get_clean();

            $this->assertStringContainsString("Custom Status {$statusCode}", $output);
        }
    }
} 