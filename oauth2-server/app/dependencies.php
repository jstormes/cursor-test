<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\DeviceCodeGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use OAuth2ServerExamples\Repositories\AccessTokenRepository;
use OAuth2ServerExamples\Repositories\ClientRepository;
use OAuth2ServerExamples\Repositories\DeviceCodeRepository;
use OAuth2ServerExamples\Repositories\RefreshTokenRepository;
use OAuth2ServerExamples\Repositories\ScopeRepository;
use OAuth2ServerExamples\Repositories\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        // Base repositories
        ClientRepository::class => function (ContainerInterface $c) {
            return new ClientRepository();
        },
        ScopeRepository::class => function (ContainerInterface $c) {
            return new ScopeRepository();
        },
        AccessTokenRepository::class => function (ContainerInterface $c) {
            return new AccessTokenRepository();
        },
        RefreshTokenRepository::class => function (ContainerInterface $c) {
            return new RefreshTokenRepository();
        },
        UserRepository::class => function (ContainerInterface $c) {
            return new UserRepository();
        },
        DeviceCodeRepository::class => function (ContainerInterface $c) {
            return new DeviceCodeRepository();
        },
        
        // Client Credentials Grant Authorization Server
        AuthorizationServer::class => function (ContainerInterface $c) {
            $server = new AuthorizationServer(
                $c->get(ClientRepository::class),
                $c->get(AccessTokenRepository::class),
                $c->get(ScopeRepository::class),
                'file://' . __DIR__ . '/../private.key',
                'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
            );

            $server->enableGrantType(
                new ClientCredentialsGrant(),
                new \DateInterval('PT1H')
            );

            return $server;
        },
        
        // Password Grant Authorization Server
        'PasswordGrantServer' => function (ContainerInterface $c) {
            $server = new AuthorizationServer(
                $c->get(ClientRepository::class),
                $c->get(AccessTokenRepository::class),
                $c->get(ScopeRepository::class),
                'file://' . __DIR__ . '/../private.key',
                'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
            );

            $grant = new PasswordGrant(
                $c->get(UserRepository::class),
                $c->get(RefreshTokenRepository::class)
            );
            $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens expire after 1 month

            $server->enableGrantType($grant, new \DateInterval('PT1H'));

            return $server;
        },
        
        // Refresh Token Grant Authorization Server
        'RefreshTokenGrantServer' => function (ContainerInterface $c) {
            $server = new AuthorizationServer(
                $c->get(ClientRepository::class),
                $c->get(AccessTokenRepository::class),
                $c->get(ScopeRepository::class),
                'file://' . __DIR__ . '/../private.key',
                'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
            );

            $grant = new RefreshTokenGrant($c->get(RefreshTokenRepository::class));
            $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens expire after 1 month

            $server->enableGrantType($grant, new \DateInterval('PT1H'));

            return $server;
        },
        
        // Device Code Grant Authorization Server  
        'DeviceCodeGrantServer' => function (ContainerInterface $c) {
            $server = new AuthorizationServer(
                $c->get(ClientRepository::class),
                $c->get(AccessTokenRepository::class),
                $c->get(ScopeRepository::class),
                'file://' . __DIR__ . '/../private.key',
                'lxZFUEsBCJ2Yb14IF2ygAHI5N4+ZAUXXaSeeJm6+twsUmIen'
            );

            $grant = new DeviceCodeGrant(
                $c->get(DeviceCodeRepository::class),
                $c->get(RefreshTokenRepository::class),
                new \DateInterval('PT10M'), // device code TTL
                'http://localhost:8087/device', // verification URI
                5 // retry interval in seconds
            );

            $server->enableGrantType($grant, new \DateInterval('PT1H'));

            return $server;
        },

        // Action factories with specific servers
        \App\Application\Actions\OAuth2\PasswordGrantAction::class => function (ContainerInterface $c) {
            return new \App\Application\Actions\OAuth2\PasswordGrantAction(
                $c->get(LoggerInterface::class),
                $c->get('PasswordGrantServer')
            );
        },
        
        \App\Application\Actions\OAuth2\RefreshTokenGrantAction::class => function (ContainerInterface $c) {
            return new \App\Application\Actions\OAuth2\RefreshTokenGrantAction(
                $c->get(LoggerInterface::class),
                $c->get('RefreshTokenGrantServer')
            );
        },
        
        \App\Application\Actions\OAuth2\DeviceAuthorizationAction::class => function (ContainerInterface $c) {
            return new \App\Application\Actions\OAuth2\DeviceAuthorizationAction(
                $c->get(LoggerInterface::class),
                $c->get('DeviceCodeGrantServer')
            );
        },
        
        \App\Application\Actions\OAuth2\DeviceAccessTokenAction::class => function (ContainerInterface $c) {
            return new \App\Application\Actions\OAuth2\DeviceAccessTokenAction(
                $c->get(LoggerInterface::class),
                $c->get('DeviceCodeGrantServer')
            );
        },
    ]);
};
