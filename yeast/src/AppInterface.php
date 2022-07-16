<?php

namespace Yeast;

use DI\ContainerBuilder;


interface AppInterface {
    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel);
}