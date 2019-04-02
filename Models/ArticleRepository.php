<?php

namespace MxcDropshipInnocigs\Models;

use Zend\Config\Factory;

class ArticleRepository extends BaseEntityRepository
{
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

    protected $dql = [
        'getAllIndexed'                      => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',

        'getArticlesByIds'                   => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.id in (:ids)',

        'getBrokenLinks'                     => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'LEFT JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'WHERE d.id IS NULL',
        'getArticlesToLink'                  => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number ',
//                                                    . 'WHERE a.linked = 0 OR a.linked IS NULL',
        'getLinkedArticlesHavingOptions'     => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN v.options o WHERE o.id IN (:optionIds)',
                                                // get all articles which have an associated Shopware Article
                                                // that have related articles with :relatedIds
        'getAllHavingRelatedArticles'        => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.relatedArticles r  WHERE r.icNumber IN (:relatedIds)',
                                                // get all articles which have an associated Shopware Article
                                                // that have similar articles with :simularIds
        'getAllHavingSimilarArticles'        => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.article = a.id '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                                    . 'JOIN a.similarArticles s  WHERE s.icNumber IN (:similarIds)',
        'getFlavoredArticles'                => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber WHERE a.flavor IS NOT NULL',
        'getShopwareArticle'                 => 'SELECT DISTINCT s FROM Shopware\Models\Article\Article s '
                                                    . 'JOIN Shopware\Models\Article\Detail d WITH d.article = s.id '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
                                                    . 'JOIN MxcDropshipInnocigs\Models\Article a WHERE v.article = a.id '
                                                    . 'WHERE a.number = :number',
        'removeOrphaned'                     => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.variants is empty',

        'getProperties'                      => 'SELECT :properties FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
    ];

    private $mappedProperties = [
        'icNumber',
        'number',
        'name',
        'commonName',
        'type',
        'category',
        'supplier',
        'brand',
        'piecesPerPack',
        'flavor',
        'dosage',
        'base',
    ];

    public function getAllIndexed()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getArticlesByIds(array $ids) {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('ids', $ids)
            ->getResult();
    }

    public function getAllHavingRelatedArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('relatedIds', $relatedIds)
            ->getResult();
    }

    public function getAllHavingSimilarArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getBrokenLinks()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    // get all articles with $linked = false having a related shopware article
    public function getArticlesToLink() {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getFlavoredArticles()
    {
        return $this->getQuery(__FUNCTION__)->getResult();
    }

    public function getLinkedArticlesHavingOptions($optionIds) {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('optionIds', $optionIds)->getResult();
    }

    public function getValidVariants(Article $article) : array
    {
        $validVariants = [];
        $variants = $article->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if ($variant->isValid()) {
                $validVariants[] = $variant;
            }
        }
        return $validVariants;
    }


    public function getShopwareArticle(Article $article)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('number', $article->getNumber())->getResult()[0];
    }

    public function removeOrphaned()
    {
        $orphans = $this->getQuery(__FUNCTION__)->getResult();
        /** @var Article $orphan */
        $em = $this->getEntityManager();
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned article \'' . $orphan->getName() . '\'');
            $em->remove($orphan);
        }
    }

    public function getProperties(array $properties)
    {
        $properties = array_map(function($property) { return 'a.' . $property; }, $properties);
        $properties = implode(', ', $properties);

        $dql = $this->dql[__FUNCTION__];
        $dql = str_replace(':properties', $properties, $dql);
        return $this->getEntityManager()
            ->createQuery($dql)
            ->getResult();
    }

    public function getMappedProperties() {
        return $this->getProperties($this->mappedProperties);
    }

    public function exportMappedProperties(string $articleConfigFile = null)
    {
        $articleConfigFile = $articleConfigFile ?? $this->articleConfigFile;
        $propertyMappings = $this->getMappedProperties();
        if (! empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            Factory::toFile($articleConfigFile, $propertyMappings);

            $this->log->debug(sprintf("Exported %s article mappings to Config\\%s.",
                count($propertyMappings),
                basename($articleConfigFile)
            ));
        }
    }

    /**
     * An article validates true if either it's $accepted member is true
     * and at least one of the article's variants validates true
     *
     * @param Article $article
     * @return bool
     */
    public function validateArticle(Article $article) : bool
    {
        if (! $article->isAccepted()) {
            return false;
        }
        $variants = $article->getVariants();
        foreach($variants as $variant) {
            if ($variant->isValid()) {
                return true;
            }
        }
        return false;
    }
}
