<?php

namespace Yeast\Http\Config;

use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;


#[Unit]
class HttpConfig {
    /** @var array<string, MountConfig> */
    #[Field(type: 'map<' . MountConfig::class . '>')]
    public array $mounts = [];
}