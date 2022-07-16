<?php

namespace Yeast\Http;

use Composer\Autoload\ClassLoader;
use HashContext;


class Mount {
    public array $namespaces = [];

    public function __construct(
      public string $name,
      public MountType $type,
      array $namespaces = [],
      public ?array $directories = null,
      public ?string $prefix = "/",
      public bool $debugOnly = false,
    ) {
        foreach ($namespaces as $namespace) {
            $cleanNamespace = trim($namespace, "\\") . "\\";
            $this->namespaces[] = [$cleanNamespace, strtolower($cleanNamespace)];
        }
    }

    public function resolveDirectories(): void {
        if ($this->directories !== null) {
            return;
        }

        $autoloaders = ClassLoader::getRegisteredLoaders();

        $directoryMap = [];

        foreach ($autoloaders as $loader) {
            $psr4s = $loader->getPrefixesPsr4();

            foreach ($psr4s as $prefix => $dirs) {
                $lower = strtolower($prefix);

                foreach ($this->namespaces as [$namespace, $normalizedNamespace]) {
                    if ( ! str_starts_with($normalizedNamespace, $lower)) {
                        continue;
                    }

                    if ($normalizedNamespace === $lower) {
                        foreach ($dirs as $dir) {
                            $directoryMap[$dir] = true;
                        }

                        break;
                    }

                    $dirPostfix = str_replace('\\', '/', substr($namespace, strlen($prefix)));

                    foreach ($dirs as $dir) {
                        $directoryMap[rtrim($dir, '/') . '/' . $dirPostfix] = true;
                    }
                }
            }
        }

        $this->directories = array_keys($directoryMap);
    }

    public function hashUpdate(HashContext $context) {
        hash_update($context, "\x00\x00" . $this->name . "\x00" . $this->type->value . "\x00" . $this->prefix . "\x00" . ($this->debugOnly ? '1' : '0') . "\x01");

        if ($this->directories !== null) {
            foreach ($this->directories as $directory) {
                hash_update($context, $directory . "\x01");
            }
        }

        hash_update($context, "\x03");

        foreach ($this->namespaces as [$namespace, $_]) {
            hash_update($context, $namespace . "\x02");
        }
    }
}