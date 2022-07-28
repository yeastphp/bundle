<?php

namespace Yeast\Http;

use DI\Attribute\Inject;
use DI\Container;
use DI\ContainerBuilder;
use GuzzleHttp\Psr7\HttpFactory;
use Monolog\Logger;
use Yeast\Http\Config\HttpConfig;
use Yeast\Kernel;
use Yeast\ModuleBase;

use function DI\add;
use function DI\autowire;
use function DI\get;


class Module extends ModuleBase {
    public const CONFIG       = HttpConfig::class;
    public const NAME         = "http";
    public const HOME_COOKING = __DIR__ . '/home_cooking.php';

    /**
     * @var Mount[]
     */
    public array $mounts = [];

    public string $mountHash = "";

    /**
     * @param  HttpConfig  $config
     *
     * @return void
     * @noinspection PhpDocSignatureInspection
     */
    public function loadConfig(object|null $config): void {
        /** @var Mount[] $mounts */
        $mounts        = $this->container->get('yeast.http.mounts');
        $enabledMounts = [];

        $hash = hash_init('sha256');

        $isProduction = $this->kernel->isProduction();

        hash_update($hash, $isProduction ? '1' : '0');

        foreach ($mounts as $mount) {
            if (isset($config->mounts[$mount->name])) {
                $mountConfigValue = $config->mounts[$mount->name];

                if ( ! $mountConfigValue->enabled) {
                    continue;
                }


                if ($mountConfigValue->debugOnly !== null) {
                    $mount->debugOnly = $mountConfigValue->debugOnly;
                }

                if ($mountConfigValue->prefix !== null) {
                    $mount->prefix = $mountConfigValue->prefix;
                }
            }

            if ($mount->debugOnly && $isProduction) {
                continue;
            }

            $this->logger->debug("Mounting[name=$mount->name, type={$mount->type->value}, prefix={$mount->prefix}, namespace=(" . implode(", ", array_map(fn($n) => $n[0], $mount->namespaces)) . "), debugOnly=" . ($mount->debugOnly ? 'true' : 'false') . ", path=" . ($mount->directories === null ? '<auto>' : "(" . implode(", ", $mount->directories) . ")") . "]");

            $enabledMounts[] = $mount;

            $mount->hashUpdate($hash);
        }

        $this->mountHash = hash_final($hash, false);
        $this->mounts    = $enabledMounts;
    }

    public function __construct(private Container $container, private Kernel $kernel, #[Inject("logger.http")] private Logger $logger) {
    }

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
        $builder->addDefinitions(
          [
            'yeast.http.mounts'  => add(
              [
                handler('_app', $kernel->getApplicationNamespace() . '\\Handler'),
                controller('_app', $kernel->getApplicationNamespace() . '\\Controller'),
              ],
            ),
            HttpFactory::class   => autowire(),
            'yeast.http.factory' => get(HttpFactory::class),
          ]
        );

        $def               = [];
        $factoryImplements = class_implements(HttpFactory::class);

        foreach ($factoryImplements as $implementation) {
            $def[$implementation] = get('yeast.http.factory');
        }

        $builder->addDefinitions($def);
    }

    /**
     * @return Mount[]
     */
    public function getMounts(): array {
        return $this->mounts;
    }

    public function getMountHash(): string {
        return $this->mountHash;
    }
}