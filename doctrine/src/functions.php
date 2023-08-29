<?php


namespace Yeast\Doctrine;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Mapping\Entity;

use ReflectionClass;

use function DI\factory;



function repositories(string ...$entities): array
{
    $definitions = [];

    foreach ($entities as $entity) {
        $ref    = new ReflectionClass($entity);
        $attrib = $ref->getAttributes(Entity::class);

        if (count($attrib) != 1) {
            continue;
        }

        $attrib = $attrib[0];

        /** @var Entity $obj */
        $obj = $attrib->newInstance();
        if ($obj->repositoryClass === null) {
            continue;
        }

        $definitions[$obj->repositoryClass] = factory(fn(EntityManager $em, string $entity) => $em->getRepository($entity))->parameter('entity', $entity);
    }

    return $definitions;
}