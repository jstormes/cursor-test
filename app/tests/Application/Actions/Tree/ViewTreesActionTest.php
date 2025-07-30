<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreesAction;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use Tests\TestCase;
use DateTime;

class ViewTreesActionTest extends TestCase
{
    public function testActionReturnsHtmlResponse(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testGeneratedHtmlContainsTreesList(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('Trees List', $html);
        $this->assertStringContainsString('Showing all active trees from the database', $html);
        $this->assertStringContainsString('Total trees found:', $html);
    }

    public function testGeneratedHtmlContainsCss(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('body {', $html);
        $this->assertStringContainsString('font-family', $html);
        $this->assertStringContainsString('background', $html);
    }

    public function testGeneratedHtmlContainsNavigationLinks(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('href="/tree"', $html);
        $this->assertStringContainsString('href="/tree/json"', $html);
        $this->assertStringContainsString('View Tree Structure', $html);
        $this->assertStringContainsString('View JSON Data', $html);
    }

    public function testGeneratedHtmlHasProperStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html lang="en">', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('<div class="container">', $html);
        $this->assertStringContainsString('<div class="stats">', $html);
        $this->assertStringContainsString('<div class="actions">', $html);
    }

    public function testGeneratedHtmlIsValidHtml(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Basic HTML structure validation
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<head', $html);
        $this->assertStringContainsString('</head>', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testGeneratedHtmlContainsResponsiveDesign(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('@media (max-width: 768px)', $html);
        $this->assertStringContainsString('grid-template-columns', $html);
        $this->assertStringContainsString('minmax(350px, 1fr)', $html);
    }

    public function testGeneratedHtmlContainsErrorHandling(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should contain error handling CSS classes
        $this->assertStringContainsString('.error-message', $html);
        $this->assertStringContainsString('.error-details', $html);
        $this->assertStringContainsString('.no-trees', $html);
    }

    public function testGeneratedHtmlContainsTreeCards(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should contain tree card structure
        $this->assertStringContainsString('.tree-card', $html);
        $this->assertStringContainsString('.tree-header', $html);
        $this->assertStringContainsString('.tree-content', $html);
        $this->assertStringContainsString('.tree-actions', $html);
        $this->assertStringContainsString('.tree-meta', $html);
    }

    public function testGeneratedHtmlContainsButtonStyles(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('.btn', $html);
        $this->assertStringContainsString('.btn-primary', $html);
        $this->assertStringContainsString('.btn-secondary', $html);
        $this->assertStringContainsString('.btn-small', $html);
    }

    public function testGeneratedHtmlContainsModernStyling(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('linear-gradient', $html);
        $this->assertStringContainsString('border-radius', $html);
        $this->assertStringContainsString('box-shadow', $html);
        $this->assertStringContainsString('transition', $html);
        $this->assertStringContainsString('transform', $html);
    }
} 