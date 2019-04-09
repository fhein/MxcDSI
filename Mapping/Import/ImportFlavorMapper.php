<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ImportFlavorMapper extends BaseImportMapper implements ImportArticleMapperInterface
{
    /**
     * ImportFlavorMapper constructor.
     *
     * @param ImportMappings $importMapping
     * @param LoggerInterface $log
     */
    public function __construct(ImportMappings $importMapping, LoggerInterface $log)
    {
        parent::__construct($importMapping->getConfig(), $log);
    }

    /**
     * Assign the product flavor from article configuration.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        if ($article->getFlavor() !== null) return;

        $flavor = explode(',', $this->config[$article->getIcNumber()]['flavor']);
        $flavor = array_map('trim', $flavor);
        $flavor = implode(', ', $flavor);
        $article->setFlavor($flavor);
    }}