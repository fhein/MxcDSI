<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class
DescriptionMapper extends BaseImportMapper implements ProductMapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    private $mappings;

    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    protected function getAromaDescription(Product $product)
    {
        $content = strval($product->getContent());
        $capacity = strval($product->getCapacity());
        $supplier = $product->getBrand();
        $flavor = ucfirst($product->getFlavor());
        $description = null;

        $description = $this->classConfig['descriptionAroma'][$supplier] ?? null;
        if ($description !== null) {
            $description = str_replace(
                ['##flavor##', '##dosage##', '##content##', '##capacity##', '##supplier##'],
                [$flavor, $product->getDosage(), $product->getContent(), $product->getCapacity(), $supplier],
                $description
            );
            return $description;
        }

        if ($content === $capacity) {
            $description = $this->classConfig['descriptionAromaDefault'];
            $description = str_replace(
                ['##flavor##', '##dosage##', '##content##', '##supplier##'],
                [$flavor, $product->getDosage(), $product->getContent(), $supplier],
                $description
            );
        } else {
            $description = $this->classConfig['descriptionAromaLongfill'];
            $description = str_replace(
                ['##flavor##', '##content##', '##capacity##', '##supplier##'],
                [$flavor, $product->getContent(), $product->getCapacity(), $supplier],
                $description
            );
        }

        return $description;
    }

    protected function getLiquidDescription(Product $product)
    {
        $name = $product->getName();
        if (strpos($name, 'mg/ml')!== false) {
            $description = @$this->classConfig['descriptionLiquidFixedNicotine'];
            $description = str_replace('##nicotine##', '20', $description);
        } else {
            $description = @$this->classConfig['descriptionLiquidDefault'];
        }
        return $description;
    }

    protected function getShakeVapeDescription(Product $product)
    {
        $name = $product->getName();
        if (is_int(strpos($name, 'Koncept XIX'))) {
            return $this->classConfig['descriptionShakeVape']['Koncept XIX'] ?? null;
        }
        // Note: We assume that all variants have the same content
        // so that we can use the content value of the first
        $variant = $product->getVariants()[0];
        if ($variant === null) {
            $this->log->err('Product ' . $product->getName() . ' has no variant 0.');
            $content = 0;
        } else  {
            $content = $variant->getContent();
        }
        $content = $product->getContent();
        $capacity = $product->getCapacity();

        if (! $content && ! $capacity) {
            return $this->classConfig['descriptionShakeVape'][$product->getBrand()] ?? null;
        }

        $content = explode(',', $content);
        $content = array_map('trim', $content);
        $capacity = explode(',', $capacity);
        $capacity = array_map('trim', $capacity);

        if (count($content) === 1 && count($capacity) === 1) {
            $description = $this->classConfig['descriptionShakeVapeDefault'];
            $fillup = $capacity[0] - $content[0];
            $description = str_replace(
                ['##fillup##', '##content##', '##capacity##'],
                [$fillup, $content[0], $capacity[0]],
                $description
            );

            return $description;
        }

        if (count($content) === 2 && count($capacity) === 2) {
            $description = $this->classConfig['descriptionShakeVapeTwoSizes'];
            $fillup1 = $capacity[0] - $content[0];
            $fillup2 = $capacity[1] - $content[1];
            $description = str_replace(
                ['##fillup1##', '##fillup2##', '##content1##', '##capacity1##', '##content2##', '##capacity2##'],
                [$fillup1, $fillup2, $content[0], $capacity[0], $content[1], $capacity[1]],
                $description
            );

            return $description;
        }

        return @$this->classConfig['descriptionShakeVape'][$product->getBrand()];
    }



    public function map(Model $model, Product $product, bool $remap = true)
    {
        $this->log->enter();
        $description = @$this->mappings[$product->getIcNumber()]['description'];
        if ($remap || ! $description) {
            $this->remap($product);
            $this->log->leave();
            return;
        }
        $product->setDescription($description);
        $this->log->leave();
    }

    public function remap(Product $product)
    {
        $number = $product->getIcNumber();
        $description = $this->classConfig['descriptionsByProductNumber'][$number];
        if ($description !== null) {
            $product->setDescription($description);
            return;
        }

        $type = $product->getType();
        $flavor = ucfirst($product->getFlavor());
        $supplier = $product->getBrand();

        switch ($type) {
            case 'NICSALT_LIQUID':
                $ltype = 'Nikotinsalz';
                $description = $this->getLiquidDescription($product);
                $description = str_replace(['##type##', '##flavor##', '##supplier##'], [$ltype, $flavor, $supplier], $description);
                break;
            case 'LIQUID':
                $ltype = 'E';
                $description = $this->getLiquidDescription($product);
                $description = str_replace(['##type##', '##flavor##', '##supplier##'], [$ltype, $flavor, $supplier], $description);
                break;
            case 'SHAKE_VAPE':
                $description = $this->getShakeVapeDescription($product);
                $description = str_replace(['##flavor##', '##supplier##'], [$flavor, $supplier], $description);
                break;
            case 'AROMA':
                $description = $this->getAromaDescription($product);
                break;
            case 'EASY3_CAP':
                $description = $this->classConfig['descriptionEasy3'];
                $description = str_replace(['##flavor##', '##supplier##'], [$flavor, $supplier], $description);
                break;
            default:
                $description = $this->mappings[$product->getIcNumber()]['description'] ?? $product->getIcDescription();
        }
        $product->setDescription($description);
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}
