<?php

namespace Yeast\Cache;

use DI\Container;
use DI\Definition\Definition;
use DI\Definition\Helper\DefinitionHelper;

use function DI\factory;


function cache(): DefinitionHelper {
    return factory(function(Container $container, Definition $definition) {
        return $container->get(CacheFactory::class)->createPsr6Pool(preg_replace(':[^a-z0-9A-Z]+:', '0', $definition->getName()));
    });
}

function simpleCache(): DefinitionHelper {
    return factory(function(Container $container, Definition $definition) {
        return $container->get(CacheFactory::class)->createPsr16Cache(preg_replace(':[^a-z0-9A-Z]+:', '0', $definition->getName()));
    });
}