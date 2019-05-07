<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class FlavorMapper implements ProductMapperInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;
    /**
     * FlavorMapper constructor.
     *
     * @param ProductMappings $importMapping
     */

    /** @var array */
    protected $config;

    /** @var array */
    protected $categoriesByFlavor;

    /** @var array */
    protected $report;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Assign the product flavor from article configuration.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {
        $flavors = @$this->config[$product->getIcNumber()]['flavor'];
        if (! $flavors) return;

        $flavors = explode(',', $flavors);
        $flavors = array_map('trim', $flavors);
        $flavorCategories = [];
        $categoriesByFlavor = $this->getCategoriesByFlavor();
        foreach ($flavors as $flavor)
        {
            $categories = $categoriesByFlavor[$flavor] ?? null;
            if (! $categories) {
                $this->report['flavor_category_missing'] = $flavor;
                $categories = ['unknown'];
            }
            foreach ($categories as $category) {
                $flavorCategories[$category] = true;
            }
        }

        $flavors = implode(', ', $flavors);
        $product->setFlavor($flavors);
        $flavorCategories = implode(', ', array_keys($flavorCategories));
        $product->setFlavorCategory($flavorCategories);
    }

    protected function getCategoriesByFlavor()
    {
        if (! $this->categoriesByFlavor) {
           $this->categoriesByFlavor = [];
            foreach ($this->classConfig as $category => $flavors) {
                foreach ($flavors as $flavor) {
                    $this->categoriesByFlavor[$flavor][] = $category;
                }
            }
        }
        return $this->categoriesByFlavor;
    }

    public function report()
    {
        $report = new ArrayReport();
        /** @noinspection PhpUndefinedMethodInspection */
        $missingFlavors = $this->modelManager->getRepository(Product::class)->getProductsWithFlavorMissing();
        $report([
            'pmMissingFlavors' => $missingFlavors,
            'pmMissingFlavorCategory' => $this->report['flavor_category_missing'] ?? []
        ]);
    }

}