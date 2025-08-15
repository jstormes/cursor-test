# Tree Structure Manager

A PHP web application for creating and displaying tree structures using the **Composite Design Pattern** and **Visitor Pattern** for HTML rendering. Built with Clean Architecture principles and comprehensive testing.

## 🌳 Project Overview

This application demonstrates advanced object-oriented design patterns in PHP:
- **Composite Pattern**: For building hierarchical tree structures with nodes
- **Visitor Pattern**: For flexible HTML rendering of tree structures
- **Repository Pattern**: For data access abstraction
- **Unit of Work**: For transaction management

## 🚀 Quick Start

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- [PhpStorm](https://www.jetbrains.com/phpstorm/) (optional, for debugging)

### 1. Clone and Start
```bash
git clone <repository-url>
cd cursor-test
docker-compose up -d
```

### 2. Access Your Application
- **Development server**: http://localhost:8088
- **Production server**: http://localhost:9088
- **PhpMyAdmin**: http://localhost:7088

## 📁 Project Structure

```
cursor-test/
├── app/                         # Application root
│   ├── public/                  # Web root directory
│   │   ├── index.php           # Main entry point
│   │   └── .htaccess           # Apache configuration
│   ├── src/                    # Source code (Clean Architecture)
│   │   ├── Domain/             # Domain layer (entities, value objects)
│   │   │   ├── Tree/           # Tree domain with composite pattern
│   │   │   └── User/           # User domain entities
│   │   ├── Application/        # Application layer (use cases, actions)
│   │   │   ├── Actions/        # Slim framework action classes
│   │   │   ├── Services/       # Business logic coordination
│   │   │   └── Middleware/     # Cross-cutting concerns
│   │   └── Infrastructure/     # Infrastructure layer (database, repositories)
│   │       ├── Database/       # Database abstraction layer
│   │       └── Persistence/    # Concrete repository implementations
│   ├── tests/                  # PHPUnit test suite (478+ tests)
│   ├── var/                    # Application cache and logs
│   ├── vendor/                 # Composer dependencies
│   ├── composer.json           # PHP dependencies
│   └── phpunit.xml            # PHPUnit configuration
├── config/docker/              # Docker configuration files
├── database/                   # Database initialization scripts
├── docker-compose.yml          # Main Docker configuration
├── Dockerfile                  # Docker image definition
└── CLAUDE.md                   # Development commands and architecture guide
```

## 🔧 Key Features

### Tree Structure Management
- **Composite Pattern Implementation**: Hierarchical tree nodes with `AbstractTreeNode`, `SimpleNode`, and `ButtonNode`
- **Visitor Pattern Rendering**: `HtmlTreeNodeRenderer` for flexible HTML output generation
- **Soft Delete Support**: Trees can be archived and restored without data loss
- **CRUD Operations**: Full create, read, update, delete functionality for trees and nodes
- **JSON & HTML APIs**: RESTful endpoints with both JSON and HTML responses

### Architecture & Design Patterns
- **Clean Architecture**: Domain, Application, and Infrastructure layers
- **Repository Pattern**: Data access abstraction with interfaces
- **Unit of Work**: Transaction management across multiple operations
- **Data Mapper**: Object-relational mapping for database persistence
- **Dependency Injection**: Slim Framework with PSR-11 container

### Development Environment
- **PHP 8.x** with common extensions
- **MariaDB** database server with tree/user tables
- **PhpMyAdmin** for database management
- **Hot reload** - code changes reflect immediately
- **xDebug integration** for debugging support

### Database Access
- **Host**: localhost, **Port**: 5000
- **Username**: root, **Password**: password
- **Database**: app
- **Tables**: trees, tree_nodes, users with relationships

## 🎯 Design Patterns in Action

### Composite Pattern
The tree structure uses the composite pattern to handle nodes uniformly:
```php
// AbstractTreeNode.php - Component interface
abstract class AbstractTreeNode {
    abstract public function accept(TreeNodeVisitor $visitor): string;
    abstract public function addChild(AbstractTreeNode $child): void;
}

// SimpleNode.php & ButtonNode.php - Leaf implementations
// TreeNode.php - Composite implementation with children
```

### Visitor Pattern
HTML rendering is handled through the visitor pattern:
```php
// HtmlTreeNodeRenderer.php - Concrete visitor
class HtmlTreeNodeRenderer implements TreeNodeVisitor {
    public function visitSimpleNode(SimpleNode $node): string { /* HTML generation */ }
    public function visitButtonNode(ButtonNode $node): string { /* Button HTML */ }
    public function visitTreeNode(TreeNode $node): string { /* Container HTML */ }
}
```

### Repository Pattern
Data access is abstracted through repository interfaces:
```php
// TreeRepositoryInterface.php - Abstract repository
interface TreeRepositoryInterface {
    public function findById(int $id): ?Tree;
    public function save(Tree $tree): void;
    public function delete(int $id): void;
}
```

## ✅ Testing & Quality

### Test Coverage
- **478+ Unit Tests** with PHPUnit
- **84%+ Line Coverage** across all layers
- **Domain, Application, Infrastructure** test separation
- **Test-driven development** approach

### Code Quality Tools
- **PHPStan Level 4**: Static analysis with zero errors
- **Psalm Level 3**: Advanced type checking
- **PHP_CodeSniffer**: PSR-12 coding standards
- **PHPMD**: Code complexity and design quality analysis

### Quality Metrics
- ✅ **Tests**: 478/478 passing (100%)
- ✅ **Coverage**: 84.10% line coverage  
- ✅ **PHPStan**: 0 errors (Level 4)
- ✅ **Psalm**: 0 errors (Level 3)

## 🚀 Development Commands

All commands should be run through Docker. See `CLAUDE.md` for complete command reference.

```bash
# Start development environment
docker-compose up -d

# Run tests with coverage
docker-compose exec php-dev bash -c "cd /app && composer test:coverage"

# Code quality checks
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpstan analyse"
docker-compose exec php-dev bash -c "cd /app && vendor/bin/psalm"
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpcs"

# Access application shell
docker-compose exec php-dev bash
```

## 🐛 Debugging with PhpStorm

### 1. Install xDebug Helper Extension
- **Chrome**: [xDebug Helper](https://chromewebstore.google.com/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc)
- **Firefox**: [xDebug Helper](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)
- **Edge**: [xDebug Helper](https://microsoftedge.microsoft.com/addons/detail/xdebug-helper/ggnngifabofaddiejjeagbaebkejomen)

### 2. Configure xDebug Helper
- Set IDE key to: `PHPSTORM`
- Set Trace Trigger to: `XDEBUG_TRACE`
- Set Profile Trigger to: `XDEBUG_PROFILE`

### 3. Setup PhpStorm
1. Open Settings (`Ctrl+Alt+S`)
2. Go to Build, Execution, Deployment → Docker
3. Add Docker server with name: `Docker`

### 4. Start Debugging
1. Click the bug icon in PhpStorm to start listening
2. Press `Ctrl+Shift+X` in browser and select "debug"
3. Set breakpoints in your PHP code
4. Refresh the page to trigger debugging

## 🌐 API Endpoints

The application provides RESTful endpoints for tree management:

### Tree Operations
- `GET /trees` - List all trees (HTML view)
- `GET /trees/{id}` - View specific tree with rendered nodes
- `POST /trees` - Create new tree
- `PUT /trees/{id}` - Update tree
- `DELETE /trees/{id}` - Soft delete tree

### JSON API
- `GET /api/trees` - JSON list of trees
- `GET /api/trees/{id}` - JSON tree data
- `POST /api/trees` - Create tree via JSON
- `PUT /api/trees/{id}` - Update tree via JSON

### Node Management
- Tree nodes are managed through the composite structure
- Visitor pattern handles HTML rendering automatically
- Support for `SimpleNode`, `ButtonNode`, and nested `TreeNode` types

## 📖 Learning Objectives

This project demonstrates:

### Design Patterns
- **Composite Pattern**: How to build tree structures with uniform node handling
- **Visitor Pattern**: Separating algorithms (HTML rendering) from data structure
- **Repository Pattern**: Data access abstraction and testability
- **Unit of Work**: Transaction management across multiple repositories

### Clean Architecture
- **Domain Layer**: Business logic and entities independent of frameworks
- **Application Layer**: Use cases and application services
- **Infrastructure Layer**: Database, web framework, external services

### PHP Best Practices
- **Type safety** with strict typing and PHPStan/Psalm
- **Test-driven development** with comprehensive test coverage
- **SOLID principles** in class design and dependency management
- **PSR standards** for coding style and HTTP handling

## 🛠️ Common Commands

```bash
# Start development environment
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs php-dev

# Access container shell for development
docker-compose exec php-dev bash

# Run full test suite with coverage
docker-compose exec php-dev bash -c "cd /app && composer test:coverage"

# Run all quality checks
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpstan analyse && vendor/bin/psalm && vendor/bin/phpcs"
```

## 🆘 Troubleshooting

### Port Already in Use
If you get port conflicts, edit the ports in `docker-compose.yml`:
```yaml
ports:
  - "8089:80"  # Change 8088 to another port
```

### Tests Failing
1. Ensure all Docker containers are running: `docker-compose ps`
2. Check PHP container logs: `docker-compose logs php-dev`
3. Verify database is initialized: Check PhpMyAdmin at http://localhost:7088

### Tree Display Issues
1. Check that database tables are properly created
2. Verify tree nodes have proper parent-child relationships
3. Test HTML rendering with simple tree structures first

### Application Not Loading
1. Verify `app/public/index.php` exists and is accessible
2. Check Slim Framework routes in application configuration
3. Ensure database connection is established

## 📚 Additional Resources

### Project Documentation
- **[API.md](API.md)** - Complete RESTful API documentation with examples
- **[CLAUDE.md](CLAUDE.md)** - Development commands and architecture guide

### Design Patterns
- [Composite Pattern](https://refactoring.guru/design-patterns/composite) - Building tree structures
- [Visitor Pattern](https://refactoring.guru/design-patterns/visitor) - Separating algorithms from objects
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html) - Data access abstraction

### Clean Architecture
- [Clean Architecture Guide](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [PHP Clean Architecture](https://github.com/Maks3w/php-clean-architecture)

### Development Tools
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Psalm Documentation](https://psalm.dev/docs/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## 📝 Project Purpose

This project serves as a comprehensive example of:
- Advanced PHP object-oriented programming
- Design pattern implementation in real-world scenarios
- Clean Architecture principles in web applications
- Test-driven development with high coverage
- Modern PHP development practices and tooling

Perfect for learning, teaching, or as a foundation for tree-based applications.

