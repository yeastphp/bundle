<?php

namespace Yeast\Config;

use Yeast\Loafpan\Attribute\Field;
use Yeast\Loafpan\Attribute\Unit;
use Yeast\ModuleBase;


#[Unit("The central configuration file of yeast.")]
class YeastConfig
{
    /**
     * @var array<class-string<ModuleBase>>
     */
    public array $resolvedModules = [];

    public ?object $app = null;

    /**
     * @param  string|null  $cacheDir
     * @param  array<class-string<ModuleBase>>  $specifiedModules
     */
    public function __construct(
      #[Field("Enable global and static functions for ease of use")]
      private bool $homeCooking = true,
      #[Field("Where to store cache")]
      private ?string $cacheDir = null,
      #[Field("Which modules to load", type: "list<string>", name: "modules")]
      private array $specifiedModules = [],
    ) {
    }

    /**
     * @return array<class-string<ModuleBase>>
     */
    public function getSpecifiedModules(): array
    {
        return $this->specifiedModules;
    }

    public function setResolvedModules(array $resolvedModules): void
    {
        $this->resolvedModules = $resolvedModules;
    }

    public function getResolvedModules(): array
    {
        return $this->resolvedModules;
    }

    public function getCacheDir(): ?string
    {
        return $this->cacheDir;
    }

    /**
     * @internal
     */
    public function setCacheDir(?string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function isHomeCooking(): bool
    {
        return $this->homeCooking;
    }
}