<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Report\ArrayReport;

class DosageMapper extends BaseImportMapper implements ProductMapperInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    /** @var array */
    protected $config;
    /**
     * DosageMapper constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Aromas typically come with a dosage recommendation by the supplier.
     * This recommendation is provided manually via our article configuration.
     *
     * In some cases InnoCigs article descriptions mention the dosage recommendation
     * in the text.
     *
     * This function checks if a manual configuration is available and otherwise
     * tries to extract the dosage recommendation from the article's description.
     *
     * @param Model $model
     * @param Product $product
     */
    public function map(Model $model, Product $product)
    {
        if ($product->getType() !== 'AROMA') {
            return;
        }
        $icNumber = $product->getIcNumber();
        $dosage = $this->config[$icNumber]['dosage'] ?? null;
        if (! $dosage) {
            // try to find dosage recommendation in product description
            $description = preg_replace('~\n~', '', $product->getDescription());
            $search = '~.*Dosierung[^\d]*(\d+).*(-|(bis)) *(\d+).*~';
            $replace = '$1-$4';
            $dosage = preg_replace($search, $replace, $description);
            if ($dosage === $description) return;
        }
        $product->setDosage($dosage);
    }

    public function report()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $dosageMissing = $this->modelManager->getRepository(Product::class)->getProductsWithDosageMissing();
        (new ArrayReport())([
           'pmMissingDosage' => $dosageMissing,
        ]);
    }
}