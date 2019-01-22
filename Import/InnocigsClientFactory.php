<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigTrait;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Mapping\PropertyMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class InnocigsClientFactory implements FactoryInterface
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
        $importClient = $container->get(ImportClient::class);
        $log = $container->get('logger');
        $modelManager = $container->get('modelManager');
        $importModifier = $container->get(ImportModifier::class);
        $propertyMapper = $container->get(PropertyMapper::class);
        return new InnocigsClient($modelManager, $apiClient, $importClient, $propertyMapper, $importModifier, $config, $log);
    }
}