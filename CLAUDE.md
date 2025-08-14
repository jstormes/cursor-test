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

# Static analysis (PHPStan)
vendor/bin/phpstan analyse

# Start PHP development server (if needed)
composer start
# Serves on http://localhost:8080
```

### One-liner commands (from host)
```bash
# Run tests from host without entering container
docker-compose exec php-dev bash -c "cd /app && composer test"

# Run code style check from host
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpcs"

# Run static analysis from host  
docker-compose exec php-dev bash -c "cd /app && vendor/bin/phpstan analyse"

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

## Code Quality
- **PHP_CodeSniffer** with PSR-12 coding standards
- **PHPStan** level 4 static analysis
- Configuration files: `phpcs.xml`, `phpstan.neon.dist`