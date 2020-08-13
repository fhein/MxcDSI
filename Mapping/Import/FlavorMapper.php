<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Report\ArrayReport;
use MxcCommons\Config\Factory;
use MxcCommons\Toolbox\Config\Config;

class FlavorMapper implements ProductMapperInterface, ModelManagerAwareInterface, ClassConfigAwareInterface
{
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    protected $categoryFile = __DIR__ . '/../../Config/FlavorMapper.config.php';

    /** @var array */
    protected $mappings;

    /** @var array */
    protected $categoriesByFlavor;

    /** @var array */
    protected $report;

    public function __construct(array $config)
    {
        $this->mappings = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $flavor = @$this->mappings[$product->getIcNumber()]['flavor'];
        if (! $flavor) return;

        list($flavor, $flavorCategory) = $this->remap($flavor);

        $product->setFlavor($flavor);
        $product->setFlavorCategory($flavorCategory);
    }

    /**
     * Assign the product flavor from article configuration.
     *
     * @param string $flavors
     * @return array
     */
    public function remap(string $flavors)
    {
        $flavors = explode(',', $flavors);
        $flavors = array_map('trim', $flavors);
        $flavorCategories = [];
        $categoriesByFlavor = $this->getCategoriesByFlavor();
        foreach ($flavors as $flavor)
        {
            $categories = $categoriesByFlavor[$flavor] ?? null;
            if (! $categories) {
                $this->report['flavor_category_missing'][] = $flavor;
                $categories = ['sonstige'];
            }
            foreach ($categories as $category) {
                $flavorCategories[$category] = true;
            }
        }

        $flavors = implode(', ', $flavors);
        $flavorCategories = implode(', ', array_keys($flavorCategories));
        return [$flavors, $flavorCategories];
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

    protected function updateCategories() {
        $categoriesByFlavor = $this->getCategoriesByFlavor();
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getProductsWithFlavorSet();
        /** @var Product $product */
        foreach ($products as $product) {
            $flavors = $product->getFlavor();
            $flavors = array_map('trim', explode(',',$flavors));
            foreach ($flavors as $flavor) {
                if ($flavor !== '' && @$categoriesByFlavor[$flavor] === null) {
                    $this->classConfig['Sonstige'][] = $flavor;
                    $this->categoriesByFlavor[$flavor] = ['Sonstige'];
                }
            }
        }
        ksort($this->classConfig);
        foreach ($this->classConfig as &$category) {
            sort($category);
            $category = array_values($category);
        }
        Config::toFile($this->categoryFile, $this->classConfig);
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
        $this->updateCategories();
    }

}
