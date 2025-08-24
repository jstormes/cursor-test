<?php

declare(strict_types=1);

use App\Application\Middleware\PerformanceMiddleware;
use App\Application\Settings\SettingsInterface;
use App\Application\Services\TreeService;
use App\Application\Services\ValidationService;
use App\Application\Validation\TreeValidator;
use App\Application\Validation\TreeNodeValidator;
use App\Application\Configuration\EnvironmentValidator;
use App\Domain\Tree\TreeNodeFactory;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Infrastructure\Factory\DefaultTreeNodeFactory;
use App\Infrastructure\Cache\CacheInterface;
use App\Infrastructure\Cache\InMemoryCache;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\DatabaseConnectionFactoryInterface;
use App\Infrastructure\Database\DatabaseUnitOfWork;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Infrastructure\Database\PdoDatabaseConnectionFactory;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Database\UnitOfWork;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Infrastructure\Persistence\Tree\DatabaseTreeNodeRepository;
use App\Infrastructure\Rendering\CssProviderInterface;
use App\Infrastructure\Rendering\HtmlRendererInterface;
use App\Infrastructure\Rendering\StaticCssProvider;
use App\Infrastructure\Rendering\TreeHtmlRenderer;
use App\Infrastructure\Services\TreeStructureBuilder;
use App\Infrastructure\Time\ClockInterface;
use App\Infrastructure\Time\SystemClock;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
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

        // Clock interface
        ClockInterface::class => function (ContainerInterface $c) {
            return new SystemClock();
        },

        // CSS Provider
        CssProviderInterface::class => function (ContainerInterface $c) {
            return new StaticCssProvider();
        },

        // HTML renderer
        HtmlRendererInterface::class => function (ContainerInterface $c) {
            return new TreeHtmlRenderer($c->get(CssProviderInterface::class));
        },

        // Cache interface
        CacheInterface::class => function (ContainerInterface $c) {
            return new InMemoryCache($c->get(ClockInterface::class));
        },

        // Database connection factory
        DatabaseConnectionFactoryInterface::class => function (ContainerInterface $c) {
            return new PdoDatabaseConnectionFactory();
        },

        // Database connection
        DatabaseConnection::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $dbSettings = $settings->get('database');
            $factory = $c->get(DatabaseConnectionFactoryInterface::class);
            return $factory->create($dbSettings);
        },

        // Unit of Work
        UnitOfWork::class => function (ContainerInterface $c) {
            return new DatabaseUnitOfWork(
                $c->get(DatabaseConnection::class),
                $c->get(ClockInterface::class),
                $c->get(TreeDataMapper::class),
                $c->get(TreeNodeDataMapper::class)
            );
        },

        // Data Mappers

        TreeDataMapper::class => function (ContainerInterface $c) {
            return new TreeDataMapper($c->get(ClockInterface::class));
        },

        TreeNodeDataMapper::class => function (ContainerInterface $c) {
            return new TreeNodeDataMapper();
        },

        // Repositories
        TreeRepository::class => function (ContainerInterface $c) {
            return new DatabaseTreeRepository(
                $c->get(DatabaseConnection::class),
                $c->get(TreeDataMapper::class)
            );
        },

        TreeNodeRepository::class => function (ContainerInterface $c) {
            return new DatabaseTreeNodeRepository(
                $c->get(DatabaseConnection::class),
                $c->get(TreeNodeDataMapper::class)
            );
        },

        // Factory
        TreeNodeFactory::class => function (ContainerInterface $c) {
            return new DefaultTreeNodeFactory();
        },

        // Services
        TreeService::class => function (ContainerInterface $c) {
            return new TreeService(
                $c->get(TreeRepository::class),
                $c->get(TreeNodeRepository::class),
                $c->get(UnitOfWork::class),
                $c->get(TreeNodeFactory::class),
                $c->get(TreeValidator::class),
                $c->get(TreeNodeValidator::class),
                $c->get(ClockInterface::class)
            );
        },

        ValidationService::class => function (ContainerInterface $c) {
            return new ValidationService(
                $c->get(TreeValidator::class),
                $c->get(TreeNodeValidator::class)
            );
        },

        TreeStructureBuilder::class => function (ContainerInterface $c) {
            return new TreeStructureBuilder();
        },

        // Validators
        TreeValidator::class => function (ContainerInterface $c) {
            return new TreeValidator();
        },

        TreeNodeValidator::class => function (ContainerInterface $c) {
            return new TreeNodeValidator();
        },

        // Middleware
        PerformanceMiddleware::class => function (ContainerInterface $c) {
            return new PerformanceMiddleware($c->get(LoggerInterface::class));
        },

        // Configuration
        EnvironmentValidator::class => function (ContainerInterface $c) {
            return new EnvironmentValidator();
        },
    ]);
};
