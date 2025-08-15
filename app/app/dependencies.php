<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Application\Services\TreeService;
use App\Domain\Tree\TreeRepository;
use App\Domain\Tree\TreeNodeRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Database\DatabaseUnitOfWork;
use App\Infrastructure\Database\PdoDatabaseConnection;
use App\Infrastructure\Database\TreeDataMapper;
use App\Infrastructure\Database\TreeNodeDataMapper;
use App\Infrastructure\Database\UserDataMapper;
use App\Infrastructure\Database\UnitOfWork;
use App\Infrastructure\Persistence\Tree\DatabaseTreeRepository;
use App\Infrastructure\Persistence\Tree\DatabaseTreeNodeRepository;
use App\Infrastructure\Persistence\User\DatabaseUserRepository;
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

        // Database connection
        DatabaseConnection::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $dbSettings = $settings->get('database');
            return new PdoDatabaseConnection($dbSettings);
        },

        // Unit of Work
        UnitOfWork::class => function (ContainerInterface $c) {
            return new DatabaseUnitOfWork(
                $c->get(DatabaseConnection::class),
                $c->get(TreeDataMapper::class),
                $c->get(TreeNodeDataMapper::class),
                $c->get(UserDataMapper::class)
            );
        },

        // Data Mappers
        UserDataMapper::class => function (ContainerInterface $c) {
            return new UserDataMapper();
        },

        TreeDataMapper::class => function (ContainerInterface $c) {
            return new TreeDataMapper();
        },

        TreeNodeDataMapper::class => function (ContainerInterface $c) {
            return new TreeNodeDataMapper();
        },

        // Repositories
        UserRepository::class => function (ContainerInterface $c) {
            return new DatabaseUserRepository(
                $c->get(DatabaseConnection::class),
                $c->get(UserDataMapper::class)
            );
        },

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

        // Services
        TreeService::class => function (ContainerInterface $c) {
            return new TreeService(
                $c->get(TreeRepository::class),
                $c->get(TreeNodeRepository::class),
                $c->get(UnitOfWork::class)
            );
        },
    ]);
};
