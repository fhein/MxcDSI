<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class DosageMapper extends BaseImportMapper implements ProductMapperInterface
{
    /**
     * DosageMapper constructor.
     *
     * @param ImportMappings $importMapping
     * @param LoggerInterface $log
     */
    public function __construct(ImportMappings $importMapping, LoggerInterface $log)
    {
        parent::__construct($importMapping->getConfig(), $log);
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
        if ($dosage !== null) {
            $product->setDosage($dosage);
            return;
        }

        $description = preg_replace('~\n~', '', $product->getDescription());
        $search = '~.*Dosierung[^\d]*(\d+).*(-|(bis)) *(\d+).*~';
        $replace = '$1-$4';
        $dosage = preg_replace($search, $replace, $description);

        if ($dosage === $description) {
            return;
        }

        $product->setDosage($dosage);
    }}