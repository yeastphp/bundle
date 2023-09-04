<?php

namespace Yeast\Graphql\TypeMapper;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\Type as GraphQLType;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Types\Collection;
use phpDocumentor\Reflection\Types\Object_;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryContext;
use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperInterface;


class DoctrineCollectionTypeMapper implements RootTypeMapperInterface {
    private array $cache = [];

    public function __construct(private RootTypeMapperInterface $next, private RootTypeMapperFactoryContext $context) {
    }

    public function toGraphQLOutputType(\phpDocumentor\Reflection\Type $type, ?OutputType $subType, $reflector, DocBlock $docBlockObj): OutputType&GraphQLType {
        if ( ! ($type instanceof Object_) || ($type->getFqsen()?->__toString() ?: "") !== '\Doctrine\Common\Collections\Collection') {
            return $this->next->toGraphQLOutputType($type, $subType, $reflector, $docBlockObj);
        }

        /** @var DocBlock\Tags\TagWithType[] $tags */
        $tags = [];

        if ($reflector instanceof \ReflectionMethod) {
            $tags = $docBlockObj->getTagsWithTypeByName('return');
        }

        if ($reflector instanceof \ReflectionProperty) {
            $tags = $docBlockObj->getTagsWithTypeByName('var');
        }

        if (count($tags) === 0 || ! $tags[0]->getType() instanceof Collection) {
            throw new \RuntimeException("Field with return of type Collection requires a @return or @var item with Collection<T> on $reflector->class::$reflector->name");
        }

        /** @var Collection $collection */
        $collection = $tags[0]->getType();
        $valueType  = $collection->getValueType();

        $type = $this->next->toGraphQLOutputType($valueType, null, null, new DocBlock());

        $typeName               = 'Collection__' . $type->name;
        $this->cache[$typeName] ??= new ObjectType(
          [
            'name'   => $typeName,
            'fields' => [
              'count'   => [
                'type'    => Type::nonNull(Type::int()),
                'resolve' => fn(\Doctrine\Common\Collections\Collection $collection) => $collection->count(),
              ],
              'entries' => [
                'type'    => Type::nonNull(Type::listOf(Type::nonNull($type))),
                'resolve' => fn(\Doctrine\Common\Collections\Collection $collection) => $collection->toArray(),
              ],
            ],
          ]
        );

        return $this->cache[$typeName];
    }

    public function toGraphQLInputType(\phpDocumentor\Reflection\Type $type, ?InputType $subType, string $argumentName, $reflector, DocBlock $docBlockObj): InputType&GraphQLType {
        return $this->next->toGraphQLInputType($type, $subType, $argumentName, $reflector, $docBlockObj);
    }

    public function mapNameToType(string $typeName): NamedType&GraphQLType {
        return $this->cache[$typeName] ?? $this->next->mapNameToType($typeName);
    }
}