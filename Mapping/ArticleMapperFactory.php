<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Toolbox\Media\MediaTool;
use Zend\Log\Logger;
use Zend\ServiceManager\Factory\FactoryInterface;

class ArticleMapperFactory implements FactoryInterface
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
        $log = $container->get(Logger::class);
        $attributeMapper = $container->get(ArticleOptionMapper::class);
        $client = $container->get(ImportMapper::class);
        $mediaTool = $container->get(MediaTool::class);
        $modelManager = $container->get('modelManager');
        $entityValidator = $container->get(InnocigsEntityValidator::class);
        $articleMapper = new ArticleMapper(
            $modelManager,
            $attributeMapper,
            $mediaTool,
            $client,
            $entityValidator,
            $log
        );
        return $articleMapper;
    }
}