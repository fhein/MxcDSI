<?php

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;
    /** @var string $articleConfigFile */
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

    /** @var LoggerInterface $log */
    protected $log;

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $modelManager = $container->get('modelManager');
        $reporter = $container->get(Reporter::class);
        $config = $this->getClassConfig($container, $requestedName);
        $config = $config->toArray();
        $config['articles'] = $this->getArticleConfiguration();
        $flavorist = $container->get(Flavorist::class);
        $propertyDerivator = $container->get(PropertyDerivator::class);
        $log = $container->get('logger');

        return new PropertyMapper($modelManager, $propertyDerivator, $flavorist, $reporter, $config, $log);
    }

    protected function getArticleConfiguration()
    {
        if (file_exists($this->articleConfigFile)) {
            $fn = $this->articleConfigFile;
        } else {
            $distFile = $this->articleConfigFile . '.dist';
            if (file_exists($distFile)) {
                $fn = $distFile;
            } else {
                return [];
            }
        }
        /** @noinspection PhpIncludeInspection */
        $config = include $fn;
        return $config;
    }
}

