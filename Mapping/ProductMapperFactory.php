<?php

namespace MxcDropshipInnocigs\Mapping;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\ObjectAugmentationTrait;
use MxcDropshipInnocigs\Mapping\Shopware\AssociatedArticlesMapper;
use MxcDropshipInnocigs\Mapping\Shopware\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\OptionMapper;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProductMapperFactory implements FactoryInterface
{
    use ObjectAugmentationTrait;
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
        $optionMapper = $container->get(OptionMapper::class);
        $articleTool = $container->get(ArticleTool::class);
        $imageMapper = $container->get(ImageMapper::class);
        $categoryMapper = $container->get(CategoryMapper::class);
        $detailMapper = $container->get(DetailMapper::class);
        $associatedArticlesMapper = $container->get(AssociatedArticlesMapper::class);
        $articleMapper = new ProductMapper(
            $articleTool,
            $optionMapper,
            $detailMapper,
            $imageMapper,
            $categoryMapper,
            $associatedArticlesMapper
        );
        return $this->augment($container, $articleMapper);
    }
}