<?php

namespace Yeast\Graphql;

use DI\Container;
use DI\ContainerBuilder;
use GraphQL\Type\Schema;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TheCodingMachine\GraphQLite\Http\Psr15GraphQLMiddlewareBuilder;
use TheCodingMachine\GraphQLite\Http\WebonyxGraphqlMiddleware;
use TheCodingMachine\GraphQLite\SchemaFactory;
use Yeast\Graphql\TypeMapper\DoctrineCollectionTypeMapper;
use Yeast\Graphql\TypeMapper\DoctrineCollectionTypeMapperFactory;
use Yeast\Kernel;
use Yeast\ModuleBase;

use function DI\add;
use function DI\factory;
use function Yeast\Cache\simpleCache;
use function Yeast\Http\controller;
use function Yeast\Http\files;


class Module extends ModuleBase
{
    public const NAME = 'graphql';

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void
    {
        $builder->addDefinitions(
            [
                'yeast.http.mounts' => add(
                    [
                        // GraphQL endpoint
                        controller('graphql', 'Yeast\Graphql\Controller'),

                        // GraphiQL debug frontend
                        files('graphiql', __DIR__ . '/../frontend/dist', prefix: "/_module/graphql", debugOnly: true),
                    ]
                ),

                'yeast.graphql.type-mappers' => add(
                    [
                    ]
                ),
                'yeast.graphql.cache' => simpleCache(),
                SchemaFactory::class => factory(function (Container $container) {
                    $kernel = $container->get(Kernel::class);
                    $factory = new SchemaFactory($container->get('yeast.graphql.cache'), $container);
                    $factory->addControllerNamespace($kernel->getApplicationNamespace() . '\Graphql');
                    $factory->addTypeNamespace($kernel->getApplicationNamespace());
                    $factory->addTypeNamespace($kernel->getApplicationNamespace() . '\Graphql\Type');

                    foreach ($container->get('yeast.graphql.type-mappers') as $typeMapper) {
                        if ($typeMapper === null) {
                            continue;
                        }

                        $factory->addTypeMapper($typeMapper);
                    }

                    if (interface_exists(\Doctrine\Common\Collections\Collection::class)) {
                        $factory->addRootTypeMapperFactory($container->get(DoctrineCollectionTypeMapperFactory::class));
                    }

                    if ($kernel->isProduction()) {
                        $factory->prodMode();
                    }

                    if ($kernel->isDebug()) {
                        $factory->devMode();
                    }

                    return $factory;
                }),

                Schema::class => factory(fn(Container $container) => $container->get(SchemaFactory::class)->createSchema()),
                WebonyxGraphqlMiddleware::class => factory(function (Container $container) {
                    $builder = new Psr15GraphQLMiddlewareBuilder($container->get(Schema::class));
                    $builder->setStreamFactory($container->get(StreamFactoryInterface::class));
                    $builder->setResponseFactory($container->get(ResponseFactoryInterface::class));

                    return $builder->createMiddleware();
                }),
            ]
        );
    }

    public static function getDependencies(): array
    {
        return [\Yeast\Http\Module::class, \Yeast\Cache\Module::class];
    }
}