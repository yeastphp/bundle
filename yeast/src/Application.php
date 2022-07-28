<?php

namespace Yeast;


use DI\ContainerBuilder;


abstract class Application {
    public function __construct(protected Kernel $kernel) {
    }

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel) {
    }
}