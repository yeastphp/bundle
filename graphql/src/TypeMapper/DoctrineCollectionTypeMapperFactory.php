<?php

namespace Yeast\Graphql\TypeMapper;

use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;


class DoctrineCollectionTypeMapperFactory implements RootTypeMapperFactoryInterface {
    public function create(RootTypeMapperInterface $next, RootTypeMapperFactoryContext $context): RootTypeMapperInterface {
        return new DoctrineCollectionTypeMapper($next, $context);
    }
}