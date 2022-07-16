<?php

namespace Yeast\Cache\Config;

use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;


#[Unit]
class CacheConfig {
    #[Field]
    public bool $disable = false;
}