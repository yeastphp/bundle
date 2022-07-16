<?php

namespace Yeast\Doctrine\Config;

use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;


#[Unit]
class DoctrineConfig {
    public function __construct(
      #[Field("A DSN or an object with the configuration for Doctrine", type: 'map<string, mixed>|string')]
      public string|array $connection = [],
      public string $namingStrategy = 'underscore',
    ) {
    }
}