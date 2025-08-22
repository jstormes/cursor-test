<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Rendering;

use App\Infrastructure\Rendering\StaticCssProvider;
use App\Infrastructure\Rendering\CssProviderInterface;
use Tests\TestCase;

class StaticCssProviderTest extends TestCase
{
    private StaticCssProvider $cssProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cssProvider = new StaticCssProvider();
    }

    public function testImplementsCssProviderInterface(): void
    {
        $this->assertInstanceOf(CssProviderInterface::class, $this->cssProvider);
    }

    public function testGetMainCssReturnsString(): void
    {
        $css = $this->cssProvider->getMainCSS();
        
        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testGetMainCssContainsExpectedSelectors(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // Test basic layout selectors
        $this->assertStringContainsString('body {', $css);
        $this->assertStringContainsString('.header {', $css);
        $this->assertStringContainsString('.navigation {', $css);

        // Test component selectors
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.btn-primary {', $css);
        $this->assertStringContainsString('.btn-secondary {', $css);

        // Test tree-specific selectors
        $this->assertStringContainsString('.tree ul {', $css);
        $this->assertStringContainsString('.tree li {', $css);
        $this->assertStringContainsString('.tree li div {', $css);

        // Test tree list selectors
        $this->assertStringContainsString('.tree-list {', $css);
        $this->assertStringContainsString('.tree-item {', $css);
        $this->assertStringContainsString('.tree-item h3 {', $css);
    }

    public function testGetMainCssContainsStyleProperties(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // Test typography
        $this->assertStringContainsString('font-family:', $css);
        $this->assertStringContainsString('font-size:', $css);
        $this->assertStringContainsString('font-weight:', $css);

        // Test colors and gradients
        $this->assertStringContainsString('color:', $css);
        $this->assertStringContainsString('background:', $css);
        $this->assertStringContainsString('linear-gradient', $css);

        // Test layout properties
        $this->assertStringContainsString('margin:', $css);
        $this->assertStringContainsString('padding:', $css);
        $this->assertStringContainsString('border:', $css);
        $this->assertStringContainsString('border-radius:', $css);

        // Test effects
        $this->assertStringContainsString('transition:', $css);
        $this->assertStringContainsString('transform:', $css);
        $this->assertStringContainsString('box-shadow:', $css);
    }

    public function testGetMainCssContainsGradientColors(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // Test specific gradient colors used in the design
        $this->assertStringContainsString('#667eea', $css);
        $this->assertStringContainsString('#764ba2', $css);
        $this->assertStringContainsString('#1e3a8a', $css);
        $this->assertStringContainsString('#6c757d', $css);
    }

    public function testGetMainCssContainsHoverEffects(): void
    {
        $css = $this->cssProvider->getMainCSS();

        $this->assertStringContainsString(':hover', $css);
        $this->assertStringContainsString('translateY', $css);
    }

    public function testGetMainCssContainsTreeStructure(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // Test tree line drawing CSS
        $this->assertStringContainsString('::before', $css);
        $this->assertStringContainsString('::after', $css);
        $this->assertStringContainsString(':only-child', $css);
        $this->assertStringContainsString(':first-child', $css);
        $this->assertStringContainsString(':last-child', $css);

        // Test tree positioning
        $this->assertStringContainsString('position: relative', $css);
        $this->assertStringContainsString('position: absolute', $css);
        $this->assertStringContainsString('border-top:', $css);
        $this->assertStringContainsString('border-left:', $css);
        $this->assertStringContainsString('border-right:', $css);
    }

    public function testGetSimplePageCssReturnsString(): void
    {
        $css = $this->cssProvider->getSimplePageCSS();
        
        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testGetSimplePageCssContainsBasicStyles(): void
    {
        $css = $this->cssProvider->getSimplePageCSS();

        $this->assertStringContainsString('body {', $css);
        $this->assertStringContainsString('font-family:', $css);
        $this->assertStringContainsString('text-align: center', $css);
        $this->assertStringContainsString('padding:', $css);
        $this->assertStringContainsString('background:', $css);
        
        $this->assertStringContainsString('.message {', $css);
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.btn:hover {', $css);
    }

    public function testGetSimplePageCssContainsColors(): void
    {
        $css = $this->cssProvider->getSimplePageCSS();

        $this->assertStringContainsString('#666', $css);
        $this->assertStringContainsString('#007bff', $css);
        $this->assertStringContainsString('#0056b3', $css);
        $this->assertStringContainsString('#f8f9fa', $css);
        $this->assertStringContainsString('white', $css);
    }

    public function testGetErrorPageCssReturnsString(): void
    {
        $css = $this->cssProvider->getErrorPageCSS();
        
        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testGetErrorPageCssContainsErrorStyles(): void
    {
        $css = $this->cssProvider->getErrorPageCSS();

        $this->assertStringContainsString('body {', $css);
        $this->assertStringContainsString('.error {', $css);
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.btn:hover {', $css);
        
        // Test error-specific color
        $this->assertStringContainsString('#dc3545', $css);
    }

    public function testGetSuccessPageCssReturnsString(): void
    {
        $css = $this->cssProvider->getSuccessPageCSS();
        
        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testGetSuccessPageCssContainsSuccessStyles(): void
    {
        $css = $this->cssProvider->getSuccessPageCSS();

        $this->assertStringContainsString('body {', $css);
        $this->assertStringContainsString('.success {', $css);
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.btn:hover {', $css);
        
        // Test success-specific color
        $this->assertStringContainsString('#28a745', $css);
    }

    public function testCssIsStaticAndCached(): void
    {
        // Test that multiple calls return the same reference (static caching)
        $css1 = $this->cssProvider->getMainCSS();
        $css2 = $this->cssProvider->getMainCSS();
        
        $this->assertSame($css1, $css2, 'Main CSS should be cached statically');

        $simpleCss1 = $this->cssProvider->getSimplePageCSS();
        $simpleCss2 = $this->cssProvider->getSimplePageCSS();
        
        $this->assertSame($simpleCss1, $simpleCss2, 'Simple page CSS should be cached statically');

        $errorCss1 = $this->cssProvider->getErrorPageCSS();
        $errorCss2 = $this->cssProvider->getErrorPageCSS();
        
        $this->assertSame($errorCss1, $errorCss2, 'Error page CSS should be cached statically');

        $successCss1 = $this->cssProvider->getSuccessPageCSS();
        $successCss2 = $this->cssProvider->getSuccessPageCSS();
        
        $this->assertSame($successCss1, $successCss2, 'Success page CSS should be cached statically');
    }

    public function testDifferentCssTypesReturnDifferentContent(): void
    {
        $mainCss = $this->cssProvider->getMainCSS();
        $simpleCss = $this->cssProvider->getSimplePageCSS();
        $errorCss = $this->cssProvider->getErrorPageCSS();
        $successCss = $this->cssProvider->getSuccessPageCSS();

        // Ensure they are all different
        $this->assertNotEquals($mainCss, $simpleCss);
        $this->assertNotEquals($mainCss, $errorCss);
        $this->assertNotEquals($mainCss, $successCss);
        $this->assertNotEquals($simpleCss, $errorCss);
        $this->assertNotEquals($simpleCss, $successCss);
        $this->assertNotEquals($errorCss, $successCss);
    }

    public function testMainCssContainsCompleteTreeStyling(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // Test that all necessary tree components are styled
        $expectedTreeSelectors = [
            '.tree ul',
            '.tree li',
            '.tree li::before',
            '.tree li::after',
            '.tree li:only-child::after',
            '.tree li:only-child::before',
            '.tree li:only-child',
            '.tree li:first-child::before',
            '.tree li:last-child::after',
            '.tree li:last-child::before',
            '.tree li:first-child::after',
            '.tree ul ul::before',
            '.tree li div'
        ];

        foreach ($expectedTreeSelectors as $selector) {
            $this->assertStringContainsString($selector, $css, "CSS should contain selector: $selector");
        }
    }

    public function testCssContainsResponsiveDesignElements(): void
    {
        $css = $this->cssProvider->getMainCSS();

        // While the current CSS doesn't have @media queries, test for mobile-friendly properties
        $this->assertStringContainsString('border-radius:', $css);
        $this->assertStringContainsString('transition:', $css);
        $this->assertStringContainsString('box-shadow:', $css);
    }

    public function testButtonStylesAreConsistent(): void
    {
        $mainCss = $this->cssProvider->getMainCSS();
        $simpleCss = $this->cssProvider->getSimplePageCSS();
        $errorCss = $this->cssProvider->getErrorPageCSS();
        $successCss = $this->cssProvider->getSuccessPageCSS();

        // Test that all CSS variants have button styles
        $this->assertStringContainsString('.btn {', $mainCss);
        $this->assertStringContainsString('.btn {', $simpleCss);
        $this->assertStringContainsString('.btn {', $errorCss);
        $this->assertStringContainsString('.btn {', $successCss);

        // Test hover states
        $this->assertStringContainsString(':hover', $mainCss);
        $this->assertStringContainsString('.btn:hover {', $simpleCss);
        $this->assertStringContainsString('.btn:hover {', $errorCss);
        $this->assertStringContainsString('.btn:hover {', $successCss);
    }

    public function testCssIsValidAndNoSyntaxErrors(): void
    {
        $cssVariants = [
            'main' => $this->cssProvider->getMainCSS(),
            'simple' => $this->cssProvider->getSimplePageCSS(),
            'error' => $this->cssProvider->getErrorPageCSS(),
            'success' => $this->cssProvider->getSuccessPageCSS()
        ];

        foreach ($cssVariants as $type => $css) {
            // Test basic CSS structure
            $this->assertStringContainsString('{', $css, "$type CSS should contain opening braces");
            $this->assertStringContainsString('}', $css, "$type CSS should contain closing braces");
            $this->assertStringContainsString(':', $css, "$type CSS should contain property-value separators");
            $this->assertStringContainsString(';', $css, "$type CSS should contain statement terminators");
            
            // Test that braces are balanced
            $openBraces = substr_count($css, '{');
            $closeBraces = substr_count($css, '}');
            $this->assertEquals($openBraces, $closeBraces, "$type CSS should have balanced braces");
        }
    }

    public function testSpecializedPageCssContainsUniqueElements(): void
    {
        $errorCss = $this->cssProvider->getErrorPageCSS();
        $successCss = $this->cssProvider->getSuccessPageCSS();

        // Error page should have error-specific styling
        $this->assertStringContainsString('.error', $errorCss);
        $this->assertStringContainsString('#dc3545', $errorCss); // Bootstrap danger color

        // Success page should have success-specific styling
        $this->assertStringContainsString('.success', $successCss);
        $this->assertStringContainsString('#28a745', $successCss); // Bootstrap success color
    }

    public function testProviderCanBeInstantiatedMultipleTimes(): void
    {
        $provider1 = new StaticCssProvider();
        $provider2 = new StaticCssProvider();

        // Static caching should work across instances
        $css1 = $provider1->getMainCSS();
        $css2 = $provider2->getMainCSS();

        $this->assertSame($css1, $css2, 'Static caching should work across instances');
    }

    public function testCssDoesNotContainInvalidContent(): void
    {
        $cssVariants = [
            $this->cssProvider->getMainCSS(),
            $this->cssProvider->getSimplePageCSS(),
            $this->cssProvider->getErrorPageCSS(),
            $this->cssProvider->getSuccessPageCSS()
        ];

        foreach ($cssVariants as $css) {
            // Should not contain script tags or JavaScript
            $this->assertStringNotContainsString('<script', $css);
            $this->assertStringNotContainsString('javascript:', $css);
            $this->assertStringNotContainsString('</script>', $css);
            
            // Should not contain HTML tags
            $this->assertStringNotContainsString('<div', $css);
            $this->assertStringNotContainsString('<html', $css);
            $this->assertStringNotContainsString('<body', $css);
            
            // Should not be empty
            $this->assertNotEmpty(trim($css));
        }
    }
}