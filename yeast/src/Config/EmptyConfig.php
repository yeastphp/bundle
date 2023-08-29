<?php

namespace Yeast\Config;

use Yeast\Loafpan\Attribute\Expander;
use Yeast\Loafpan\Attribute\Unit;


#[Unit("An empty config that accepts everything")]
class EmptyConfig
{

    #[Expander]
    public static function expand($visitor) {
        return new EmptyConfig();
    }
}