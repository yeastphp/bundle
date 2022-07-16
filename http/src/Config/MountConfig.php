<?php

namespace Yeast\Http\Config;

use Yeast\Loafpan\Attribute\Expander;
use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;


#[Unit]
class MountConfig {
    public function __construct(
      #[Field]
      public bool|string $enabled = true,
      #[Field]
      public ?bool $debugOnly = null,
      #[Field]
      public ?string $prefix = null,
    ) {
        if ($this->enabled === 'always') {
            $this->debugOnly = false;
            $this->enabled   = true;
        }

        if (is_string($this->enabled)) {
            throw new \RuntimeException("mount.enabled only accepts 'always', true or false");
        }
    }

    #[Expander]
    public static function fromBoolean(bool $enabled): MountConfig {
        return new MountConfig($enabled);
    }

    #[Expander]
    public static function fromString(string $prefix): MountConfig {
        return new MountConfig(prefix: $prefix);
    }
}