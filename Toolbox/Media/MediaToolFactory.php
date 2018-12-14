<?php

namespace MxcDropshipInnocigs\Toolbox\Media;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MediaToolFactory implements FactoryInterface
{
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
        $log = $container->get('logger');
        $authService = $container->get('authService');
        $modelManager = $container->get('modelManager');
        $mediaManager = $container->get('mediaManager');
        return new MediaTool($modelManager, $mediaManager, $authService, $log);
    }
}