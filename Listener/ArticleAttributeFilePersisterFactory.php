<?php /** @noinspection PhpUnusedParameterInspection */

namespace MxcDropshipInnocigs\Listener;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Import\PropertyMapper;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleAttributeFilePersisterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $propertyMapper = $container->get(PropertyMapper::class);
        $log = $container->get('logger');
        return new ArticleAttributeFilePersister($propertyMapper, $log);
    }
}