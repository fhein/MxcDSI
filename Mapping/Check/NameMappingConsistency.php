<?php


namespace MxcDropshipInnocigs\Mapping\Check;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Mapping\Import\ImportNameMapper;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;

class NameMappingConsistency
{
    /** @var ImportNameMapper $importNameMapper */
    protected $importNameMapper;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $articles;

    /** @var array */
    protected $models;

    public function __construct(ModelManager $modelManager, ImportNameMapper $importNameMapper, LoggerInterface $log)
    {
        $this->importNameMapper = $importNameMapper;
        $this->modelManager = $modelManager;
        $this->log = $log;
    }

    /**
     * Check if each models name maps to the same product name.
     */
    public function check()
    {
        $articles = $this->getArticles() ?? [];

        /** @var Article $article */
        $topics = [];
        foreach ($articles as $article) {
            $issues = $this->getNameMappingIssues($article);
            if (!empty($issues)) {
                $topics[$article->getIcNumber()] = $issues;
            }
        }
        ksort($topics);
        $report = ['pmNameMappingInconsistencies' => $topics];
        $reporter = new ArrayReport();
        $reporter($report);
        return count($topics);
    }

    /**
     * Helper function
     *
     * @param Article $article
     * @return array
     */
    public function getNameMappingIssues(Article $article): array
    {
        $models = $this->getModels();
        if (!$models) {
            return [];
        }

        $variants = $article->getVariants();
        $map = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $number = $variant->getIcNumber();
            $model = $models[$number];
            $this->importNameMapper->map($model, $article);
            $map[$article->getName()] = $number;
        }
        if (count($map) === 1) {
            return [];
        }
        $issues = [];
        foreach ($map as $name => $number) {
            /** @var Model $model */
            $model = $models[$number];
            $issues[$number] = [
                'imported_name' => $model->getName(),
                'mapped_name'   => $name,
                'options'       => $model->getOptions()
            ];
        }
        return $issues;
    }

    /**
     * Lazy load Articles.
     *
     * @return array|null
     */
    protected function getArticles()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->articles = $this->articles ?? $this->modelManager->getRepository(Article::class)->getAllIndexed();
        return $this->articles;
    }

    /**
     * Lazy load Models.
     *
     * @return array|null
     */
    protected function getModels()
    {
        $this->models = $this->models ?? $this->modelManager->getRepository(Model::class)->getAllIndexed();
        return $this->models;
    }

}