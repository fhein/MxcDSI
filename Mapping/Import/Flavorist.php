<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use Zend\Config\Factory;

class Flavorist implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    protected $categories = [];

    protected $reversedCategories = [];

    protected $categoryFile = __DIR__ . '/../../Config/flavor.categories.config.php';
    protected $flavorFile = __DIR__ . '/../../Config/flavor.config.php';

    public function __construct()
    {
        $this->getFlavorCategories();
    }

    public function updateFlavors()
    {
        if (file_exists($this->flavorFile)) {
            /** @noinspection PhpIncludeInspection */
            $currentFlavors = include $this->flavorFile;
        } else {
            $currentFlavors = [];
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getFlavoredProducts();
        /** @var Product $product */
        $newFlavors = [];
        foreach ($products as $product) {
            $number = $product->getIcNumber();
            if ($product->getFlavor() !== null) {
                $flavor = array_map('trim', explode(',', $product->getFlavor()));
            } else {
                $flavor = @$currentFlavors[$number]['flavor'];
            }
            $newFlavors[$number] = [
                'number' => $number,
                'name'   => $product->getName(),
                'flavor' => $flavor
            ];
        }
        ksort($newFlavors);
        Factory::toFile($this->flavorFile, $newFlavors);
    }

    public function updateCategories() {
        $this->revertCategories();
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getProductsWithFlavorSet();
        /** @var Product $product */
        foreach ($products as $product) {
            $flavors = $product->getFlavor();
            $flavors = array_map('trim', explode(',',$flavors));
            foreach ($flavors as $flavor) {
                if (@$this->reversedCategories[$flavor] === null) {
                    $this->categories['Sonstige'][] = $flavor;
                    $this->reversedCategories[$flavor] = ['Sonstige'];
                }
            }
        }
        $this->sortCategories();
        Factory::toFile($this->categoryFile, $this->categories);
    }

    protected function revertCategories()
    {
        if (! empty($this->reversedCategories)) return;

        $this->reversedCategories = [];
        foreach ($this->categories as $category => $flavors) {
            foreach ($flavors as $flavor) {
                $this->reversedCategories[$flavor][] = $category;
            }
        }
    }

    protected function sortCategories() {
        ksort($this->categories);
        foreach ($this->categories as &$category) {
            sort($category);
            $category = array_values($category);
        }
    }

    public function getCategories(string $flavors) {
        $this->revertCategories();
        $flavors = array_map('trim', explode(',', $flavors));
        $categories = [];
        foreach ($flavors as $flavor) {
            $groups = $this->reversedCategories[$flavor];
            foreach ($groups as $group) {
                $categories[$group] = true;
            }
        }
        return array_keys($categories);
    }

    protected function getFlavorCategories() {
        if (empty($this->categories)) {
            if (file_exists($this->categoryFile)) {
                /** @noinspection PhpIncludeInspection */
                $this->categories = include $this->categoryFile;
            }
        }
        return $this->categories;
    }

}