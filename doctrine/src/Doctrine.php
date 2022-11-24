<?php

namespace Yeast;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Console\Helper\HelperSet;
use Yeast\Cache;
use Yeast\Doctrine\Config\DoctrineConfig;

use function DI\get;


class Doctrine extends ModuleBase {
    public const NAME         = "doctrine";
    public const CONFIG       = DoctrineConfig::class;
    public const HOME_COOKING = __DIR__ . '/home_cooking.php';

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
        $builder->addDefinitions(
          [
            'yeast.doctrine.cache'        => Cache\cache(),
            EntityManager::class          => function(Container $container) {
                $kernel = $container->get(Kernel::class);

                $cache          = DoctrineProvider::wrap($container->get('yeast.doctrine.cache'));
                $doctrineConfig = Setup::createAttributeMetadataConfiguration(
                  [$kernel->getApplicationDir() . '/src/Entity'],
                  $kernel->isDebug(),
                  $kernel->getCacheDir() . '/doctrine/proxies',
                  $cache
                );

                $doctrineConfig->setMiddlewares([new Middleware($kernel->getLogger()->withName('doctrine'))]);

                /** @var DoctrineConfig $config */
                $config = $container->get('module.doctrine.config');


                if (is_string($config->connection)) {
                    $config->connection = ['url' => $config->connection];
                }

                if ($config->namingStrategy === 'underscore' || $config->namingStrategy === 'under_score') {
                    $doctrineConfig->setNamingStrategy(new UnderscoreNamingStrategy(numberAware: true));
                }

                return EntityManager::create($config->connection, $doctrineConfig);
            },
            EntityManagerInterface::class => get(EntityManager::class),
          ]
        );
    }

    public static function getDependencies(): array {
        return [Cache\Module::class];
    }

    public function __construct(private EntityManager $manager) {
    }

    public function getEntityManager(): EntityManagerInterface {
        return $this->manager;
    }

    public function getConsoleHelperSet(): HelperSet {
        return ConsoleRunner::createHelperSet($this->getEntityManager());
    }
}