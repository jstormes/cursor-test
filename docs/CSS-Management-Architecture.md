# CSS Management Architecture

This document explains how CSS is managed in the tree visualization system, including the separation of concerns between different tree view modes and general application styling.

## Overview

The CSS management system provides a clean, maintainable architecture that separates tree-specific styling from general application CSS. It supports two distinct tree viewing modes: **Standard** (read-only) and **Edit** (interactive).

## Architecture Diagram

```
┌─────────────────────────────────────────┐
│           CSS Architecture              │
├─────────────────────────────────────────┤
│                                         │
│  CssProviderInterface                   │
│  ┌─────────────────────────────────┐    │
│  │ • getMainCSS()                  │    │
│  │ • getSimplePageCSS()            │    │
│  │ • getErrorPageCSS()             │    │
│  │ • getSuccessPageCSS()           │    │
│  │ • getTreeCSS(type)              │    │
│  └─────────────────────────────────┘    │
│              │                          │
│              ▼                          │
│  StaticCssProvider                      │
│  ┌─────────────────────────────────┐    │
│  │ Main CSS: Layout, Navigation,   │    │
│  │           Forms, Buttons        │    │
│  │                                 │    │
│  │ Tree CSS Delegation:            │    │
│  │ ┌─────────────┬─────────────┐   │    │
│  │ │ Standard    │ Edit        │   │    │
│  │ │ Tree CSS    │ Tree CSS    │   │    │
│  │ └─────────────┴─────────────┘   │    │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘
```

## Core Components

### 1. CSS Provider Interface

**File**: `src/Infrastructure/Rendering/CssProviderInterface.php`

Defines the contract for all CSS providers:

```php
interface CssProviderInterface
{
    public function getMainCSS(): string;
    public function getSimplePageCSS(): string;
    public function getErrorPageCSS(): string;
    public function getSuccessPageCSS(): string;
    public function getTreeCSS(string $treeViewType = 'standard'): string;
}
```

### 2. Main CSS Provider

**File**: `src/Infrastructure/Rendering/StaticCssProvider.php`

Orchestrates all CSS types and delegates tree CSS to specialized providers:

```php
final class StaticCssProvider implements CssProviderInterface
{
    private StandardTreeCssProvider $standardTreeCss;
    private EditTreeCssProvider $editTreeCss;

    public function getTreeCSS(string $treeViewType = 'standard'): string
    {
        return match ($treeViewType) {
            'edit' => $this->editTreeCss->getTreeCSS(),
            'standard' => $this->standardTreeCss->getTreeCSS(),
            default => $this->standardTreeCss->getTreeCSS(),
        };
    }
}
```

### 3. Specialized Tree CSS Providers

#### Standard Tree CSS Provider

**File**: `src/Infrastructure/Rendering/StandardTreeCssProvider.php`

**Purpose**: Provides clean, read-only tree visualization CSS

**Key Features**:
- Basic tree structure (connecting lines, node positioning)
- Hover effects for visual feedback
- Responsive design
- No interactive elements

**CSS Highlights**:
```css
/* Standard Tree View CSS - Read Only */
.tree {
    overflow-x: auto;
    overflow-y: visible;
}

.tree li div {
    border: 1px solid #1e3a8a;
    padding: 15px 10px;
    color: #1e3a8a;
    background-color: #ffffff;
    /* ... */
}

.tree li div:hover {
    background: #1e3a8a; 
    color: #ffffff;
}
```

#### Edit Tree CSS Provider

**File**: `src/Infrastructure/Rendering/EditTreeCssProvider.php`

**Purpose**: Provides interactive tree editing CSS with add/remove functionality

**Key Features**:
- All standard tree features
- Interactive add/remove icons
- Hover effects for interactive elements
- Form element integration
- Special styling for container nodes

**CSS Highlights**:
```css
/* Edit Tree View CSS - Interactive */

/* Add icon styling */
.tree li div .add-icon {
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #1e3a8a;
    color: white;
    /* ... */
}

/* Remove icon styling */
.tree li div .remove-icon {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 20px;
    border-radius: 5px;
    background-color: #dc3545;
    color: white;
    /* ... */
}

/* Interactive form elements */
.tree li div input[type="checkbox"] {
    margin: 0 4px 0 0;
    transform: scale(1.1);
    accent-color: #1e3a8a;
}
```

## Usage in Components

### Standard Tree View (Read-only)

**File**: `src/Infrastructure/Rendering/TreeHtmlRenderer.php`

Used by the `TreeHtmlRenderer` for displaying trees without edit capabilities:

```php
private function renderPage(string $title, string $content, ?string $customCSS = null): string
{
    $mainCSS = $this->cssProvider->getMainCSS();
    $treeCSS = $this->cssProvider->getTreeCSS('standard');
    
    $css = $customCSS ?: ($mainCSS . "\n\n" . $treeCSS);
    // ...
}
```

### Edit Tree View (Interactive)

**File**: `src/Application/Actions/Tree/ViewTreeByIdAction.php`

Used by edit actions that allow tree manipulation:

```php
private function generateHTML(string $treeHtml, Tree $tree): string
{
    $mainCSS = $this->cssProvider->getMainCSS();
    $treeCSS = $this->cssProvider->getTreeCSS('edit');
    $css = $mainCSS . "\n\n" . $treeCSS;
    // ...
}
```

## CSS Separation Strategy

### Main CSS Responsibilities
- **Layout**: Page structure, headers, navigation
- **Forms**: Input fields, form containers, validation styling
- **Buttons**: General button styling (.btn, .btn-primary, .btn-secondary)
- **Lists**: Tree list containers (.tree-list, .tree-item)
- **Responsive**: General responsive design rules

**Example Main CSS Content**:
```css
body { 
    font-family: Arial, sans-serif; 
    margin: 0; 
    padding: 0; 
    background: #f8f9fa; 
}

.header {
    text-align: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 5px;
    transition: all 0.3s ease;
}
```

### Tree CSS Responsibilities
- **Tree Structure**: Node positioning, connecting lines
- **Visual Hierarchy**: Parent-child relationships
- **Interactive Elements**: Add/remove icons (edit mode only)
- **Tree-specific Responsive**: Tree layout adjustments for mobile

## Key Management Features

### 1. **Separation of Concerns**
- **Main CSS**: General application styling (headers, navigation, forms, buttons)
- **Tree CSS**: Specialized for tree visualization only
- Clear boundaries prevent CSS conflicts and improve maintainability

### 2. **Two Tree Modes**
- **`'standard'`**: Clean, read-only tree display for viewing
- **`'edit'`**: Interactive tree with add/remove icons and hover effects for editing

### 3. **CSS Combination Strategy**
```php
$css = $mainCSS . "\n\n" . $treeCSS;
```
Both main and tree CSS are concatenated for complete styling, ensuring all necessary styles are included.

### 4. **Performance Optimization**
- **Static Caching**: Each CSS type is cached using static properties with null coalescing
- **Lazy Loading**: CSS is only generated when first requested
- **Single Request**: All CSS is combined and served in one `<style>` block

### 5. **Extensibility**
New tree view types can be easily added:
1. Create a new specialized CSS provider (e.g., `CompactTreeCssProvider`)
2. Add it to `StaticCssProvider` constructor and switch statement
3. Use it with `getTreeCSS('compact')`

## Testing Strategy

The CSS management system is fully tested with:

### Unit Tests
- **StaticCssProviderTest**: Tests all CSS generation methods
- **CSS Content Validation**: Ensures required selectors and properties are present
- **Tree Mode Differentiation**: Verifies standard vs edit CSS differences

### Integration Tests
- **TreeHtmlRendererTest**: Tests CSS integration in rendering pipeline
- **ViewTreeByIdActionTest**: Tests CSS injection in edit actions
- **Mock Integration**: Tests with realistic CSS content for callback validation

## Migration Summary

### Before Refactoring
- All CSS (including tree CSS) was embedded in `StaticCssProvider.getMainCSS()`
- No separation between read-only and interactive tree views
- Hard to maintain and customize tree-specific styling
- Tests were tightly coupled to specific CSS content

### After Refactoring
- ✅ Clean separation between main application CSS and tree CSS
- ✅ Two specialized tree CSS providers for different use cases
- ✅ Flexible, extensible architecture for new tree view types
- ✅ Maintainable, testable codebase with proper dependency injection
- ✅ All 799 tests passing with proper mock expectations

## File Structure

```
src/Infrastructure/Rendering/
├── CssProviderInterface.php          # CSS provider contract
├── StaticCssProvider.php             # Main CSS orchestrator  
├── StandardTreeCssProvider.php       # Read-only tree CSS
├── EditTreeCssProvider.php          # Interactive tree CSS
└── TreeHtmlRenderer.php             # Uses standard tree CSS

src/Application/Actions/Tree/
└── ViewTreeByIdAction.php            # Uses edit tree CSS

tests/Infrastructure/Rendering/
├── StaticCssProviderTest.php         # CSS provider tests
└── TreeHtmlRendererTest.php         # Renderer integration tests

tests/Application/Actions/Tree/
└── ViewTreeByIdActionTest.php        # Action integration tests
```

## Best Practices

1. **Keep CSS Providers Focused**: Each provider should have a single responsibility
2. **Use Dependency Injection**: Inject CSS providers rather than instantiating directly
3. **Test CSS Content**: Validate that required selectors and properties exist
4. **Cache Appropriately**: Use static caching for performance but ensure testability
5. **Document CSS Modes**: Clearly document what each tree view type is for
6. **Maintain Separation**: Keep tree CSS separate from general application CSS

## Future Enhancements

Potential improvements to the CSS management system:

1. **Theme Support**: Add theme parameter to CSS methods for light/dark modes
2. **CSS Compression**: Minify CSS in production environments
3. **External CSS Files**: Option to serve CSS from external files instead of inline
4. **CSS Variables**: Use CSS custom properties for better theme customization
5. **Additional Tree Modes**: Compact view, list view, or custom organization chart modes

---

*This documentation reflects the CSS management architecture as of the latest refactoring. For implementation details, refer to the source code in the `src/Infrastructure/Rendering/` directory.*