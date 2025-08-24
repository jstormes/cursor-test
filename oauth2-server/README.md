# OAuth2 Authorization Server - Slim Framework 4

A complete OAuth2 Authorization Server implementation built with **Slim Framework 4** and the [`league/oauth2-server`](https://github.com/thephpleague/oauth2-server) package.

This application provides a fully functional OAuth2 server with support for all major grant types, built using modern PHP practices and clean architecture principles.

## Features

✅ **Complete OAuth2 Implementation**
- Client Credentials Grant
- Password Grant (Resource Owner Password Credentials)
- Refresh Token Grant  
- Device Authorization Grant

✅ **Modern Architecture**
- Slim Framework 4
- PHP-DI Dependency Injection
- Clean Architecture with proper separation of concerns
- Docker-based development environment

✅ **Security & Standards**
- JWT access tokens with RSA256 signing
- Secure client credential validation (bcrypt)
- Configurable token expiry times
- Proper error handling and OAuth2-compliant responses

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### Installation

1. **Clone and setup the project:**
```bash
git clone <repository-url> oauth2-server
cd oauth2-server
```

2. **Start the Docker environment:**
```bash
docker-compose up -d
```

3. **Install dependencies (automatically generates RSA keys):**
```bash
docker exec cursor-test-oauth2-server-1 composer install
```

> **Note:** RSA key pair (private.key & public.key) will be automatically generated during `composer install`. If you need to regenerate them manually, run: `docker exec cursor-test-oauth2-server-1 composer keys:generate`

The OAuth2 server will be available at **http://localhost:8087**

## OAuth2 Grant Types & Testing

### Client Credentials Grant
Used for machine-to-machine authentication where no user interaction is required.

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
Used when the client can securely handle user credentials (e.g., first-party applications).

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
Used to obtain new access tokens using a previously issued refresh token.

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
Used for devices with limited input capabilities (e.g., smart TVs, IoT devices).

**Step 1: Request Device Authorization**
```bash
curl -X "POST" "http://localhost:8087/oauth2/device_code/device_authorization" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123" \
    --data-urlencode "scope=basic email"
```

**Step 2: Exchange Device Code for Access Token**
```bash
curl -X "POST" "http://localhost:8087/oauth2/device_code/access_token" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -H "Accept: application/json" \
    --data-urlencode "grant_type=urn:ietf:params:oauth:grant-type:device_code" \
    --data-urlencode "device_code=<DEVICE_CODE_FROM_STEP_1>" \
    --data-urlencode "client_id=myawesomeapp" \
    --data-urlencode "client_secret=abc123"
```

## Development

### Docker Commands
```bash
# Start services
docker-compose up -d

# Stop services  
docker-compose down

# View logs
docker logs cursor-test-oauth2-server-1

# Access container shell
docker exec cursor-test-oauth2-server-1 sh

# Install/update dependencies (auto-generates keys)
docker exec cursor-test-oauth2-server-1 composer install
docker exec cursor-test-oauth2-server-1 composer update

# Generate/regenerate RSA keys manually
docker exec cursor-test-oauth2-server-1 composer keys:generate

# Run tests
docker exec cursor-test-oauth2-server-1 composer test

# Check code style
docker exec cursor-test-oauth2-server-1 vendor/bin/phpcs

# Fix code style
docker exec cursor-test-oauth2-server-1 vendor/bin/phpcbf
```

### Test Credentials

**Default Client:**
- Client ID: `myawesomeapp`  
- Client Secret: `abc123`

**Default User:**
- Username: `alex`
- Password: `whisky`

## Architecture Overview

### Directory Structure
```
oauth2-server/
├── app/                    # Slim 4 configuration
│   ├── dependencies.php   # OAuth2 server configurations
│   ├── routes.php         # API endpoints
│   ├── settings.php       # Application settings
│   └── middleware.php     # Middleware configuration
├── src/
│   ├── Application/
│   │   └── Actions/OAuth2/ # OAuth2 grant action classes
│   ├── Entities/          # OAuth2 entities (tokens, clients, etc.)
│   └── Repositories/      # OAuth2 repository implementations
├── public/
│   └── index.php          # Application entry point
└── docker-compose.yml     # Docker configuration
```

### Key Components

**OAuth2 Grant Actions:**
- `ClientCredentialsAction` - Handles client credentials grants
- `PasswordGrantAction` - Handles password grants
- `RefreshTokenGrantAction` - Handles refresh token grants  
- `DeviceAuthorizationAction` - Handles device authorization requests
- `DeviceAccessTokenAction` - Handles device token exchanges

**Repositories:**
- `ClientRepository` - Client validation and retrieval
- `UserRepository` - User authentication
- `AccessTokenRepository` - Access token management
- `RefreshTokenRepository` - Refresh token management
- `DeviceCodeRepository` - Device code management
- `ScopeRepository` - OAuth2 scope management

## Token Configuration

- **Access Tokens:** 1 hour expiry (JWT with RSA256 signing)
- **Refresh Tokens:** 1 month expiry
- **Device Codes:** 10 minutes expiry
- **Encryption Key:** Configurable in dependencies.php

## Security Features

- Client credentials are hashed using bcrypt
- JWT tokens signed with RSA256 private key
- Proper OAuth2 error responses
- Input validation and sanitization
- Docker container isolation

## Production Deployment

1. **Generate strong encryption key:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

2. **Update `app/dependencies.php` with your encryption key**

3. **Generate production RSA key pair:**
```bash
# Option 1: Use composer script (generates 2048-bit keys)
composer keys:generate

# Option 2: Manual generation (4096-bit for production)
openssl genrsa -out private.key 4096
openssl rsa -in private.key -pubout -out public.key
chmod 600 private.key
```

4. **Configure your clients and users in the respective repositories**

5. **Enable container compilation in `public/index.php`**

6. **Set up proper environment variables and secrets management**

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `docker exec cursor-test-oauth2-server-1 composer test`
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Documentation

For detailed OAuth2 specification information, visit:
- [OAuth2 RFC 6749](https://tools.ietf.org/html/rfc6749)
- [League OAuth2 Server Documentation](https://oauth2.thephpleague.com/)

## Support

- Create an issue for bug reports or feature requests
- Check existing documentation and examples
- Review the OAuth2 specification for implementation details