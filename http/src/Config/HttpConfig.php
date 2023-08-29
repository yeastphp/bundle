<?php

namespace Yeast\Http\Config;

use Psr\Http\Server\MiddlewareInterface;
use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;


#[Unit]
class HttpConfig {
    /** @var array<string, MountConfig> */
    #[Field(type: 'map<' . MountConfig::class . '>')]
    public array $mounts = [];

    /** @var array<class-string<MiddlewareInterface>> */
    #[Field(type: 'list<string>')]
    public array $middleware = [];
}