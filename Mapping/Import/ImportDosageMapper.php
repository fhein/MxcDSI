<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ImportDosageMapper extends BaseImportMapper implements ImportArticleMapperInterface
{
    /**
     * ImportDosageMapper constructor.
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
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        if ($article->getType() !== 'AROMA') {
            return;
        }
        $icNumber = $article->getIcNumber();
        $dosage = $this->config[$icNumber]['dosage'];
        if ($dosage !== null) {
            $article->setDosage($dosage);
            return;
        }

        $description = preg_replace('~\n~', '', $article->getDescription());
        $search = '~.*Dosierung[^\d]*(\d+).*(-|(bis)) *(\d+).*~';
        $replace = '$1-$4';
        $dosage = preg_replace($search, $replace, $description);

        if ($dosage === $description) {
            return;
        }

        $article->setDosage($dosage);
    }}