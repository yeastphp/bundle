<?php

namespace Yeast\Demo\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;


#[Entity]
class Foo {
    #[Column, Id, GeneratedValue('AUTO')]
    public int $id;
}