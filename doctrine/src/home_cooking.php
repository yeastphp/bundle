<?php

namespace Yeast\Doctrine\HomeCooking;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Yeast\Doctrine;

use function Yeast\HomeCooking\kernel;


function em(): EntityManager {
    return kernel()->module(Doctrine::class)->getEntityManager();
}

/**
 * @template T
 *
 * @param  class-string<T>  $entity
 *
 * @return EntityRepository<T>
 */
function repo(string $entity): EntityRepository {
    return em()->getRepository($entity);
}