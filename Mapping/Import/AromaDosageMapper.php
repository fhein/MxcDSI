<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class AromaDosageMapper extends BaseImportMapper implements ArticleMapperInterface
{
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