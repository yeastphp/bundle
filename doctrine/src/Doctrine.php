<?php

namespace Yeast;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Console\Helper\HelperSet;
use Yeast\Cache;
use Yeast\Doctrine\Config\DoctrineConfig;

use Yeast\Doctrine\Type\BinaryStringType;
use Yeast\Doctrine\Type\BlobStringType;

use function DI\get;


class Doctrine extends ModuleBase
{
    public const NAME         = "doctrine";
    public const CONFIG       = DoctrineConfig::class;
    public const HOME_COOKING = __DIR__ . '/home_cooking.php';

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void
    {
        $builder->addDefinitions(
          [
            'yeast.doctrine.cache'        => Cache\cache(),
            EntityManager::class          => function (Container $container) {
                $kernel = $container->get(Kernel::class);

                $cache          = $kernel->isDebug() ? null : $container->get('yeast.doctrine.cache');
                $doctrineConfig = ORMSetup::createAttributeMetadataConfiguration(
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

                Type::addType('blob_string', BlobStringType::class);
                Type::addType('binary_string', BinaryStringType::class);

                return EntityManager::create($config->connection, $doctrineConfig);
            },
            EntityManagerInterface::class => get(EntityManager::class),
          ]
        );
    }

    public static function getDependencies(): array
    {
        return [Cache\Module::class];
    }

    public function __construct(private EntityManager $manager)
    {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}