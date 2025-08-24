# OAuth2 Server - CLAUDE.md

This file provides guidance to Claude Code when working with this OAuth2 server project.

## IMPORTANT: Docker-Only Development

**This project MUST always run in Docker containers. No local PHP, database, or development tools should be installed or used outside of Docker.** All development commands must be executed through Docker containers to ensure consistency across environments.

## Development Commands

### Docker Environment Setup
```bash
# Start OAuth2 server environment
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs slim

# Access container shell
docker-compose exec slim sh

# Rebuild containers
docker-compose up --build
```

### PHP Development (run inside Docker container)
```bash
# Access the container first
docker-compose exec slim sh

# Then run these commands inside the container:
cd /var/www

# Install dependencies
composer install

# Regenerate autoload after namespace changes
composer dump-autoload

# Run tests
composer test
# Or directly with PHPUnit
vendor/bin/phpunit

# Code style checking
vendor/bin/phpcs

# Auto-fix coding standard violations
vendor/bin/phpcbf

# Static analysis
vendor/bin/phpstan analyse
```

### One-liner commands (from host)
```bash
# Install dependencies from host
docker exec cursor-test-oauth2-server-1 composer install

# Regenerate autoload from host
docker exec cursor-test-oauth2-server-1 composer dump-autoload

# Run tests from host
docker exec cursor-test-oauth2-server-1 composer test

# Run code style check from host
docker exec cursor-test-oauth2-server-1 vendor/bin/phpcs

# Auto-fix coding standards from host
docker exec cursor-test-oauth2-server-1 vendor/bin/phpcbf
```

## Architecture Overview

This is a **OAuth2 Authorization Server** built with **Slim Framework 4** implementing the OAuth2 specification using the `league/oauth2-server` package.

## Testing OAuth2 Endpoints

The server runs on http://localhost:8087 when Docker is started.

### Client Credentials Grant
```bash
curl -X "POST" "http://localhost:8087/oauth2/access_token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "grant_type=client_credentials" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123" \
    --data-urlencode "scope=basic email"
```

### Password Grant
```bash
curl -X "POST" "http://localhost:8087/oauth2/password/access_token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "grant_type=password" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123" \
    --data-urlencode "username=alex" \
    --data-urlencode "password=whisky" \
    --data-urlencode "scope=basic email"
```

### Refresh Token Grant
```bash
curl -X "POST" "http://localhost:8087/oauth2/refresh_token/access_token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "grant_type=refresh_token" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123" \
    --data-urlencode "refresh_token=<REFRESH_TOKEN_FROM_PASSWORD_GRANT>"
```

### Device Authorization Grant

#### Step 1: Request Device Authorization
```bash
curl -X "POST" "http://localhost:8087/oauth2/device_code/device_authorization" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123" \
    --data-urlencode "scope=basic email"
```

#### Step 2: Exchange Device Code for Access Token
```bash
curl -X "POST" "http://localhost:8087/oauth2/device_code/access_token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "grant_type=urn:ietf:params:oauth:grant-type:device_code" \
    --data-urlencode "device_code=<DEVICE_CODE_FROM_STEP_1>" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123"
```

## Important Development Rules

**NEVER install PHP locally** - All PHP execution must happen in containers:
- Web requests: Access via http://localhost:8087
- CLI commands: Execute inside container with `docker exec cursor-test-oauth2-server-1 sh`
- Tests: Run inside container with `docker exec cursor-test-oauth2-server-1 composer test`
- Composer: Use inside container with `docker exec cursor-test-oauth2-server-1 composer install`

## Container Details
- **cursor-test-oauth2-server-1**: Main PHP server container (PHP 7 Alpine) - port 8087
- Working directory: `/var/www` (maps to project root)
- Container name: `cursor-test-oauth2-server-1`