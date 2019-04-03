<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Mapping\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use Zend\ServiceManager\Factory\FactoryInterface;

class AssociatedArticlesMapperFactory implements FactoryInterface
{
    use ClassConfigTrait;
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
        $config = $this->getClassConfig($container, $requestedName);
        $log = $container->get('logger');
        $modelManager = $container->get('modelManager');
        return new AssociatedArticlesMapper($modelManager, $config, $log);
    }
}