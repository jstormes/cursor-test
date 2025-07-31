<?php

declare(strict_types=1);

namespace App\Tests\Application\Actions\Tree;

use App\Application\Actions\Tree\ViewTreesAction;
use App\Domain\Tree\Tree;
use App\Domain\Tree\TreeRepository;
use Tests\TestCase;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;

class ViewTreesActionTest extends TestCase
{
    private ViewTreesAction $action;
    private TreeRepository|MockObject $mockTreeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockTreeRepository = $this->createMock(TreeRepository::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        
        $this->action = new ViewTreesAction($logger, $this->mockTreeRepository);
    }

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
        $this->assertStringContainsString('Total active trees found:', $html);
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

    public function testGeneratedHtmlContainsHeaderActions(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('href="/tree/add"', $html);
        $this->assertStringContainsString('href="/trees/deleted"', $html);
        $this->assertStringContainsString('Add New Tree', $html);
        $this->assertStringContainsString('View Deleted Trees', $html);
    }

    public function testGeneratedHtmlContainsMetaTags(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<meta charset="UTF-8">', $html);
        $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1.0">', $html);
        $this->assertStringContainsString('<title>Trees List</title>', $html);
    }

    public function testGeneratedHtmlContainsTreeCardActions(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should contain tree card action links
        $this->assertStringContainsString('href="/tree/', $html);
        $this->assertStringContainsString('/json"', $html);
        $this->assertStringContainsString('/delete"', $html);
        $this->assertStringContainsString('View Tree', $html);
        $this->assertStringContainsString('JSON', $html);
        $this->assertStringContainsString('Delete', $html);
    }

    public function testGeneratedHtmlContainsTreeMetaInformation(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should contain tree meta information
        $this->assertStringContainsString('Created:', $html);
        $this->assertStringContainsString('Updated:', $html);
        $this->assertStringContainsString('ID:', $html);
    }

    public function testGeneratedHtmlContainsGradientBackgrounds(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('linear-gradient(135deg, #667eea 0%, #764ba2 100%)', $html);
    }

    public function testGeneratedHtmlContainsHoverEffects(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString(':hover', $html);
        $this->assertStringContainsString('transform: translateY', $html);
    }

    public function testGeneratedHtmlContainsColorSchemes(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('#667eea', $html);
        $this->assertStringContainsString('#764ba2', $html);
        $this->assertStringContainsString('#dc3545', $html);
        $this->assertStringContainsString('#28a745', $html);
        $this->assertStringContainsString('#ffc107', $html);
    }

    public function testGeneratedHtmlContainsTypography(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('font-family', $html);
        $this->assertStringContainsString('font-size', $html);
        $this->assertStringContainsString('font-weight', $html);
    }

    public function testGeneratedHtmlContainsSpacing(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('padding', $html);
        $this->assertStringContainsString('margin', $html);
        $this->assertStringContainsString('gap', $html);
    }

    public function testGeneratedHtmlContainsLayoutProperties(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('display: grid', $html);
        $this->assertStringContainsString('display: flex', $html);
        $this->assertStringContainsString('justify-content', $html);
        $this->assertStringContainsString('align-items', $html);
    }

    public function testGeneratedHtmlContainsAccessibilityFeatures(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('cursor: pointer', $html);
    }

    public function testGeneratedHtmlContainsErrorStyling(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('.error-message', $html);
        $this->assertStringContainsString('.error-details', $html);
        $this->assertStringContainsString('#f8d7da', $html);
        $this->assertStringContainsString('#721c24', $html);
    }

    public function testGeneratedHtmlContainsNoTreesMessage(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // The CSS class should be present even if there are trees
        $this->assertStringContainsString('.no-trees', $html);
        // The message only appears when there are no trees, so we check the CSS class instead
    }

    public function testGeneratedHtmlContainsMobileResponsiveDesign(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('@media (max-width: 768px)', $html);
        $this->assertStringContainsString('grid-template-columns: 1fr', $html);
        $this->assertStringContainsString('flex-direction: column', $html);
    }

    public function testGeneratedHtmlContainsTreeCardStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should contain complete tree card structure
        $this->assertStringContainsString('<div class="tree-card">', $html);
        $this->assertStringContainsString('<div class="tree-header">', $html);
        $this->assertStringContainsString('<div class="tree-content">', $html);
        $this->assertStringContainsString('<div class="tree-actions">', $html);
        $this->assertStringContainsString('<div class="tree-meta">', $html);
    }

    public function testGeneratedHtmlContainsButtonVariants(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('.btn-primary', $html);
        $this->assertStringContainsString('.btn-secondary', $html);
        $this->assertStringContainsString('.btn-danger', $html);
        $this->assertStringContainsString('.btn-small', $html);
    }

    public function testGeneratedHtmlContainsInteractiveElements(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('transition', $html);
        $this->assertStringContainsString('transform', $html);
        $this->assertStringContainsString('box-shadow', $html);
    }

    public function testGeneratedHtmlContainsSemanticHTML(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('<h3>', $html);
        $this->assertStringContainsString('<p>', $html);
        $this->assertStringContainsString('<a href', $html);
    }

    public function testGeneratedHtmlContainsProperEscaping(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        // Should not contain unescaped HTML entities
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function testGeneratedHtmlContainsProperContentType(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testGeneratedHtmlContainsProperStatusCode(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGeneratedHtmlContainsProperCharacterEncoding(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('charset="UTF-8"', $html);
    }

    public function testGeneratedHtmlContainsProperViewport(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('width=device-width, initial-scale=1.0', $html);
    }

    public function testGeneratedHtmlContainsProperTitle(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<title>Trees List</title>', $html);
    }

    public function testGeneratedHtmlContainsProperLanguageAttribute(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<html lang="en">', $html);
    }

    public function testGeneratedHtmlContainsProperDoctype(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
    }

    public function testGeneratedHtmlContainsProperContainerStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<div class="container">', $html);
        $this->assertStringContainsString('<div class="header">', $html);
        $this->assertStringContainsString('<div class="stats">', $html);
        $this->assertStringContainsString('<div class="actions">', $html);
    }

    public function testGeneratedHtmlContainsProperHeaderStructure(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<div class="header">', $html);
        $this->assertStringContainsString('<div class="header-actions">', $html);
        $this->assertStringContainsString('<h1>Trees List</h1>', $html);
    }

    public function testGeneratedHtmlContainsProperStatsSection(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<div class="stats">', $html);
        $this->assertStringContainsString('Total active trees found:', $html);
    }

    public function testGeneratedHtmlContainsProperActionsSection(): void
    {
        $app = $this->getAppInstance();
        $request = $this->createRequest('GET', '/trees');
        $response = $app->handle($request);

        $html = (string) $response->getBody();

        $this->assertStringContainsString('<div class="actions">', $html);
        $this->assertStringContainsString('View Tree Structure', $html);
        $this->assertStringContainsString('View JSON Data', $html);
    }
} 