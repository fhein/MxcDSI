<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Client\ApiClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImportMapperFactory implements FactoryInterface
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
        $apiClient = $container->get(ApiClient::class);
        $log = $container->get('logger');
        $modelManager = $container->get('modelManager');
        $propertyMapper = $container->get(PropertyMapper::class);
        return new ImportMapper($modelManager, $apiClient, $propertyMapper, $config, $log);
    }
}