<?php

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Import\Report\PropertyMapper as Reporter;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;

    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

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
        $flavorist = $container->get(Flavorist::class);
        $propertyDerivator = $container->get(PropertyDerivator::class);
        $log = $container->get('logger');

        return new PropertyMapper($modelManager, $propertyDerivator, $flavorist, $reporter, $config, $log);
    }
}

