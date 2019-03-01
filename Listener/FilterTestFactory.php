<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\Filter\GroupRepository;
use Zend\ServiceManager\Factory\FactoryInterface;

class FilterTestFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $config = $container->get('config')->plugin->$requestedName;
        $repository = $container->get(GroupRepository::class);
        $log = $container->get('logger');
        return new FilterTest($repository, $config, $log);
    }
}