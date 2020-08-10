<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use Doctrine\Common\Collections\ArrayCollection;
use MxcCommons\Plugin\Service\ClassConfigAwareInterface;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Report\ArrayReport;

class AssociatedProductsMapper
    implements ClassConfigAwareInterface, ModelManagerAwareInterface, LoggerAwareInterface
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    protected $productGroups = null;

    public function map(/** @noinspection PhpUnusedParameterInspection */ array $products)
    {
        $this->productGroups = $this->getProductGroups();
        $this->deriveRelatedProducts();
        $this->deriveSimilarProducts();
    }

    public function getProductGroups()
    {
        if (null !== $this->productGroups) return $this->productGroups;
        $this->productGroups = [];
        $products = $this->modelManager->getRepository(Product::class)->getAllIndexed();
        /** @var Product $product */
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
        return $this->productGroups;
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

        // Zunächst prüfen wir gegen eine Liste von Geschmacken.
        // Wenn der Geschmack bei beiden Produkten vorkommt, gelten Sie als ähnlich.
        $similarFlavors = $this->classConfig['similar_flavors'] ?? [];
        foreach ($similarFlavors as $flavor) {
            if (in_array($flavor, $flavor1) && in_array($flavor, $flavor2)) {
                return true;
            }
        }
        // Falls der obige Test fehlschlägt, haben zwei Produkte dann einen ähnlichen Geschmack,
        // wenn die Anzahl der gemeinsamen Geschmacke bis auf eine Abweichung von minimal 1
        // mit der kürzeren Liste übereinstimmt. Diese Heuristik ist bisher nicht auf daraufhin
        // geprüft, ob die so als ähnlich charakterisierten Produkte auch als ähnlich empfunden werden.

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
            }
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

        foreach (['SHAKE_VAPE', 'LIQUID', 'NICSALT_LIQUID', 'AROMA'] as $group) {
            $this->deriveSimilarFlavoredProducts($group);
        }
    }

    public function reportRelatedProducts() {
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
        (new ArrayReport())(['pmRelatedProducts' => $relatedProductList]);
    }

    public function reportSimilarProducts() {
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
        (new ArrayReport())(['pmSimilarProducts' => $similarProductList]);
    }

    public function report()
    {
        $this->reportRelatedProducts();
        $this->reportSimilarProducts();
    }
}