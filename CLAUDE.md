# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Docker Environment Setup
```bash
# Start full development environment
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs php-dev

# Access container shell
docker-compose exec php-dev bash

# Rebuild containers
docker-compose up --build
```

### PHP Development (run inside Docker container)
```bash
# Access the container first
docker-compose exec php-dev bash

# Then run these commands inside the container:
cd /app

# Install dependencies
composer install

# Run tests
composer test
# Or directly with PHPUnit
vendor/bin/phpunit

# Run tests with coverage
composer test:coverage

# Code style checking (PHP_CodeSniffer)
vendor/bin/phpcs

# Auto-fix coding standard violations
vendor/bin/phpcbf

# Static analysis (PHPStan)
vendor/bin/phpstan analyse --memory-limit=512M

# Advanced static analysis (Psalm)
vendor/bin/psalm

# Auto-fix type and style issues with Psalm
vendor/bin/psalm --alter --issues=InvalidNullableReturnType,MissingOverrideAttribute,UnusedVariable,PossiblyUnusedMethod,ClassMustBeFinal,MissingParamType

# Code complexity analysis (PHPMD)
vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode

# Start PHP development server (if needed)
composer start
# Serves on http://localhost:8080
```

### One-liner commands (from host)
```bash
# Run tests from host without entering container
docker-compose exec php-dev bash -c "cd /app && composer test"

# Run tests with coverage from host
docker-compose exec php-dev bash -c "cd /app && composer test:coverage"

# Run code style check from host
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpcs"

# Auto-fix coding standards from host
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpcbf"

# Run static analysis from host  
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpstan analyse --memory-limit=512M"

# Run advanced static analysis from host
docker-compose exec php-dev bash -c "cd /app && vendor/bin/psalm"

# Run code complexity analysis from host
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode"

# Install dependencies from host
docker-compose exec php-dev bash -c "cd /app && composer install"
```

## Architecture Overview

This is a **PHP web application** built with the **Slim Framework** following **Clean Architecture** principles with distinct layers:

### Domain Layer (`src/Domain/`)
- **Tree Domain**: Core tree data structures with composite pattern implementation
  - `Tree.php`: Main tree entity with soft delete support
  - `TreeNode.php`, `AbstractTreeNode.php`: Node hierarchy with composite pattern
  - `ButtonNode.php`, `SimpleNode.php`: Concrete node implementations
  - `HtmlTreeNodeRenderer.php`: Visitor pattern for HTML rendering
  - Repository interfaces for data access abstraction

- **User Domain**: User management entities
  - `User.php`: User entity with standard CRUD operations
  - `UserRepository.php`: Repository interface for user data access

### Application Layer (`src/Application/`)
- **Actions**: Slim Framework action classes for HTTP endpoints
  - Tree actions: CRUD operations with both HTML and JSON responses
  - User actions: User management endpoints
- **Services**: Business logic coordination
  - `TreeService.php`: Orchestrates tree operations with transaction management
- **Middleware**: Cross-cutting concerns like session management

### Infrastructure Layer (`src/Infrastructure/`)
- **Database**: PDO-based data access with repository pattern
  - `DatabaseConnection.php`: Database interface abstraction
  - `PdoDatabaseConnection.php`: PDO implementation
  - `UnitOfWork.php`: Transaction management
  - Data mappers for each domain entity
- **Persistence**: Concrete repository implementations using database layer

### Key Patterns Used
- **Repository Pattern**: Data access abstraction
- **Unit of Work**: Transaction management across multiple operations
- **Composite Pattern**: Tree node hierarchy management
- **Visitor Pattern**: Tree rendering with different output formats
- **Data Mapper**: Object-relational mapping

## Database
- **MariaDB** database with soft delete support
- Connection via PDO with transaction support
- Database initialization scripts in `database/` directory
- Access via PhpMyAdmin at http://localhost:7088 (Docker environment)
- Connection details:
  - Host: localhost, Port: 5000
  - Username: root, Password: password

## Development Environment
- **Docker-based** with PHP 8.x, MariaDB, and PhpMyAdmin
- **Hot reload** for code changes
- **xDebug integration** for debugging with PhpStorm
- Development server: http://localhost:8088
- Production-like server: http://localhost:9088

## Testing
- **PHPUnit** for unit testing with comprehensive test coverage
- Tests organized by architectural layers in `tests/` directory
- Test configuration in `phpunit.xml`
- Coverage reporting available via `composer test:coverage`

## Code Quality Tools

This project maintains high code quality through multiple automated tools:

### Static Analysis & Type Checking
- **PHPStan** (Level 4): Advanced static analysis with type checking
- **Psalm** (Level 3): Type inference and advanced static analysis
- Both tools help catch bugs before runtime and ensure type safety

### Coding Standards
- **PHP_CodeSniffer** (PHPCS): Enforces PSR-12 coding standards
- **PHP Code Beautifier** (PHPCBF): Auto-fixes coding standard violations
- Configuration: `phpcs.xml`

### Code Complexity & Design Quality  
- **PHP Mess Detector** (PHPMD): Detects code smells, complexity issues, and design problems
- Checks for: cyclomatic complexity, method length, coupling, unused code

### Testing & Coverage
- **PHPUnit**: Unit testing framework with 513+ tests
- **Test Coverage**: 87%+ line coverage reporting
- Configuration: `phpunit.xml`

### Quality Metrics (Current Status)
- ✅ **Tests**: 513/513 passing (100%), 4 skipped - **All methods restored**
- ✅ **Coverage**: 87.04% line coverage (1807/2076 lines) - **Fully restored**
- ✅ **PHPStan**: 0 errors (Level 4) - **All errors resolved**
- 🔄 **Psalm**: 95 errors found (91.66% type inference) - **34 errors fixed overall**
- ✅ **PHPCS**: 0 errors, 83 warnings (mostly line length) - 2 errors fixed
- ⚠️ **PHPMD**: Complex methods and design issues identified

### Configuration Files
- `phpcs.xml` - PHP_CodeSniffer rules
- `phpstan.neon.dist` - PHPStan configuration
- `psalm.xml` - Psalm configuration (auto-generated)
- `phpunit.xml` - PHPUnit test configuration

### Recommended Workflow
```bash
# Before committing, run full quality check:
composer test                    # Run all tests
vendor/bin/phpcbf                # Auto-fix coding standards
vendor/bin/phpcs                 # Check remaining violations
vendor/bin/phpstan analyse --memory-limit=512M       # Static analysis
vendor/bin/psalm                 # Advanced type checking
vendor/bin/phpmd src text cleancode,codesize,design,naming,unusedcode
```