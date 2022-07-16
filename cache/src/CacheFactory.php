<?php

namespace Yeast\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Stash\Driver\Apc;
use Stash\Driver\BlackHole;
use Stash\Driver\FileSystem;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;
use Yeast\Kernel;


class CacheFactory {
    private ?DriverInterface $driver = null;
    private Module $module;

    public function getDriver(): DriverInterface {
        if ($this->driver === null) {
            $this->driver = $this->selectDriver();
        }

        return $this->driver;
    }

    private function selectDriver(): DriverInterface {
        if ($this->module->isDisabled()) {
            return new BlackHole();
        }

        if (Apc::isAvailable()) {
            return new Apc(
              [
                'namespace' => 'yeast',
              ]
            );
        } else {
            return new FileSystem(
              [
                'path' => $this->kernel->getCacheDir() . '/stash/',
                'encoder' => FileSystem\SerializerEncoder::class,
                // LOL, don't do this, opens up to cache poisoning attacks
                // 'keyHashFunction' => fn($data) => $data,
              ]
            );
        }
    }

    public function __construct(private Kernel $kernel) {
        $this->module = $this->kernel->module(Module::class);
    }

    public function createPsr16Cache(string $namespace): CacheInterface {
        return new Psr16Wrapper($this->createPsr6Pool($namespace));
    }

    public function createPsr6Pool(string $namespace): CacheItemPoolInterface {
        $pool = new Pool($this->getDriver());
        $pool->setNamespace($namespace);

        return $pool;
    }
}