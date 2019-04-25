<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class AssociatedProductsMapper implements ClassConfigAwareInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    protected $productGroups;

    public function map(array $products)
    {
        $this->productGroups = [];
        $this->prepareProductGroups($products);
        $this->deriveRelatedProducts();
        $this->deriveSimilarProducts();

        $this->dumpProductNames();
        $this->dumpRelatedProducts();
        $this->dumpSimilarProducts();
    }

    public function prepareProductGroups(array $products)
    {
        $this->productGroups = [];
        foreach ($products as $product) {
            $type = $product->getType();
            $commonName = $product->getCommonName();

            switch ($commonName) {
                case 'K2 & K3':
                    {
                        // special case where one product name indicates spare part for two products
                        $this->productGroups[$type]['K2'][] = $product;
                        $this->productGroups[$type]['K3'][] = $product;
                    }
                    break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'Aromamizer Supreme RDTA V2':
                    $this->productGroups[$type][$commonName . '.1'][] = $product;
                // intentional fall through
                default:
                    $this->productGroups[$type][$commonName][] = $product;
            }
        }
    }

    protected function getAsscociatedProducts(Product $product, array $config) : ArrayCollection
    {
        $associatedProducts = new ArrayCollection();
        foreach ($config['groups'] as $groupName) {
            foreach ($this->productGroups[$groupName] as $cName => $group) {
                /** @var Product $relatedProduct */
                foreach ($group as $relatedProduct) {
                    if ($config['match_common_name'] && $product->getCommonName() !== $cName) continue;
                    if ($associatedProducts->contains($relatedProduct)) continue;

                    $associatedProducts->add($relatedProduct);
                }
            }
        }
        return $associatedProducts;
    }

    protected function deriveRelatedProducts()
    {
        if (! isset($this->classConfig['related_product_groups'])) return;

        foreach ($this->classConfig['related_product_groups'] as $group => $setting) {
            foreach ($this->productGroups[$group] as $products) {
                /** @var Product $product */
                foreach ($products as $product) {
                    $relatedProducts = $this->getAsscociatedProducts($product, $setting);
                    $product->setRelatedProducts($relatedProducts);
                }
            }
        }
    }

    protected function isSimilarFlavoredProduct(Product $product1, Product $product2)
    {
        $flavor1 = array_map('trim', explode(',', $product1->getFlavor()));
        $flavor2 = array_map('trim', explode(',', $product2->getFlavor()));
        $similarFlavors = $this->classConfig['similar_flavors'] ?? [];
        foreach ($similarFlavors as $flavor) {
            if (in_array($flavor, $flavor1) && in_array($flavor, $flavor2)) {
                return true;
            }
        }
        $common = array_intersect($flavor1, $flavor2);
        $min = min(count($flavor1), count($flavor2));
        $count = count($common);
        $requiredMatches = $min === 1 ? 1 : $min - 1;
        return ($count === $requiredMatches);
    }

    protected function deriveSimilarFlavoredProducts($group)
    {
        if (! isset($this->productGroups[$group])) return;

        $products = [];
        foreach ($this->productGroups[$group] as $groupProducts) {
            foreach($groupProducts as $product) {
                $products[] = $product;
            };
        }
        /** @var Product $product1 */
        foreach ($products as $product1) {
            /** @var ArrayCollection $similarProducts */
            $similarProducts = $product1->getSimilarProducts();
            foreach ($products as $product2) {
                if ($product1 === $product2 || ! $this->isSimilarFlavoredProduct($product1, $product2)) continue;
                if (! $similarProducts->contains($product2)) {
                    $product1->addSimilarProduct($product2);
                }
            }
        }
    }

    protected function deriveSimilarProducts()
    {
        $similarGroups = $this->classConfig['similar_product_groups'] ?? [];
        foreach ($similarGroups as $group => $setting) {
            if (! $this->productGroups[$group]) continue;
            foreach ($this->productGroups[$group] as $products) {
                /** @var Product $product */
                foreach ($products as $product) {
                    $similarProducts = $this->getAsscociatedProducts($product, $setting);
                    $product->setSimilarProducts($similarProducts);
                }
            }
        }

        foreach (['SHAKE_VAPE', 'LIQUID', 'AROMA'] as $group) {
            $this->deriveSimilarFlavoredProducts($group);
        }
    }

    public function dumpRelatedProducts() {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        /** @var Product $product */
        $relatedProductList = [];
        foreach ($products as $number => $product) {
            $relatedProducts = $product->getRelatedProducts();
            if ($relatedProducts->isEmpty()) continue;
            $list = [];
            foreach ($relatedProducts as $relatedProduct) {
                $list[] = $relatedProduct->getName();
            }
            $relatedProductList[$number] = [
                'name' => $product->getName(),
                'related_products' => $list,
            ];
        }
        (new ArrayReport())(['peRelatedProducts' => $relatedProductList]);
    }

    public function dumpSimilarProducts() {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        /** @var Product $product */
        $similarProductList = [];
        foreach ($products as $number => $product) {
            $similarProducts = $product->getSimilarProducts();
            if ($similarProducts->isEmpty()) continue;
            $list = [];
            foreach ($similarProducts as $similarProduct) {
                $list[] = [
                    'name' => $similarProduct->getName(),
                    'flavor' => $similarProduct->getFlavor(),
                ];
            }
            $similarProductList[$number] = [
                'name' => $product->getName(),
                'flavor' => $product->getFlavor(),
                'similar_products' => $list,
            ];
        }
        (new ArrayReport())(['peSimilarProducts' => $similarProductList]);
    }

    protected function dumpProductNames() {
        /** @noinspection PhpUndefinedMethodInspection */
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        $productNames = [];
        foreach ($products as $number => $product) {
            /** @var Product $product */
            $name = $product->getCommonName();
            $productNames[$name] = true;
        }
        ksort($productNames);
        (new ArrayReport())(['peProducts' => array_keys($productNames)]);
    }

}