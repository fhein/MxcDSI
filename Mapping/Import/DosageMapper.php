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
    protected $mappings;
    /**
     * DosageMapper constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->mappings = $config;
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        if ($product->getType() !== 'AROMA') return;

        $dosage = @$this->mappings[$product->getIcNumber()]['dosage'];
        if ($dosage === null) {
            $dosage = $this->remap($product);
        }
        $product->setDosage($dosage);
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
     * @param Product $product
     * @return string|null
     */
    public function remap(Product $product)
    {
        // try to find dosage recommendation in product description
        $description = preg_replace('~\n~', '', $product->getDescription());
        $search = '~.*Dosierung[^\d]*(\d+).*(-|(bis)) *(\d+).*~';
        $replace = '$1 - $4';
        $dosage = preg_replace($search, $replace, $description);
        if ($dosage === $description) $dosage = null;
        return $dosage;
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