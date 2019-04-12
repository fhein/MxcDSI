<?php

namespace MxcDropshipInnocigs\Models;

use Zend\Config\Factory;

class ArticleRepository extends BaseEntityRepository
{
    protected $articleConfigFile = __DIR__ . '/../Config/ImportMappings.config.php';

    protected $dql = [
        'getAllIndexed'                  => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
        'getArticlesByIds'               => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.id in (:ids)',
        'getValidVariants'               => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v '
                                            . 'JOIN v.article a '
                                            . 'JOIN v.options o '
                                            . 'JOIN o.icGroup g '
                                            . 'WHERE a.id = :id '
                                            . 'AND (a.accepted = 1 AND v.accepted = 1 AND o.accepted = 1 AND g.accepted = 1)',
        'getInvalidVariants'             => 'SELECT v FROM MxcDropshipInnocigs\Models\Variant v '
                                            . 'JOIN v.article a '
                                            . 'JOIN v.options o '
                                            . 'JOIN o.icGroup g '
                                            . 'WHERE a.id = :id AND '
                                            . '(a. accepted = 0 OR v.accepted = 0 OR o.accepted = 0 OR g.accepted = 0)',
        'getLinkedArticlesHavingOptions' => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                            . 'JOIN a.variants v '
                                            . 'JOIN v.options o '
                                            . 'JOIN MxcDropshipInnocigs\Models\Group g WITH o.icGroup = g.id '
                                            . 'WHERE o.id IN (:optionIds) AND a.linked = 1',
        // get all articles which have an associated Shopware Article that have related articles with :relatedIds
        'getHavingRelatedArticles'       => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                            . 'JOIN a.variants v '
                                            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                            . 'JOIN a.relatedArticles r  WHERE r.icNumber IN (:relatedIds)',
        // get all articles which have an associated Shopware Article that have similar articles with :simularIds
        'getHavingSimilarArticles'       => 'SELECT DISTINCT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                                            . 'JOIN a.variants v '
                                            . 'JOIN Shopware\Models\Article\Detail d WITH d.number = v.number '
                                            . 'JOIN a.similarArticles s  WHERE s.icNumber IN (:similarIds)',
        'getFlavoredArticles'            => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber WHERE a.flavor IS NOT NULL',
        'getShopwareArticle'             => 'SELECT DISTINCT s FROM Shopware\Models\Article\Article s '
                                            . 'JOIN Shopware\Models\Article\Detail d WITH d.article = s.id '
                                            . 'JOIN MxcDropshipInnocigs\Models\Variant v WITH v.number = d.number '
                                            . 'JOIN MxcDropshipInnocigs\Models\Article a WHERE v.article = a.id '
                                            . 'WHERE a.number = :number',
        // get Shopware articles without variants
        'getOrphanedShopwareArticles'    => 'SELECT a FROM Shopware\Models\Article\Article a '
                                            . 'LEFT JOIN Shopware\Models\Article\Detail d ON d.article = a.id '
                                            . 'WHERE a.id IS NULL',
        'removeOrphaned'                 => 'SELECT a FROM MxcDropshipInnocigs\Models\Article a WHERE a.variants is empty',
        // DQL does not support parameters in SELECT
        'getProperties'                  => 'SELECT :properties FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber',
        'getDosages'                     => 'SELECT a.icNumber, a.name, a.dosage FROM MxcDropshipInnocigs\Models\Article a '
            . 'INDEX BY a.icNumber WHERE a.type = \'AROMA\'',
    ];

    protected $sql = [
        'linkArticles'   => 'UPDATE s_plugin_mxc_dsi_article a, s_plugin_mxc_dsi_variant v, s_articles_details d '
            . 'SET a.linked = 1 '
            . 'WHERE v.article_id = a.id AND d.ordernumber = v.number AND (a.linked = 0 '
            . 'OR a.linked IS NULL)',
        'refreshLinks'   => 'UPDATE s_plugin_mxc_dsi_article a '
            . 'INNER JOIN s_plugin_mxc_dsi_variant v ON v.article_id = a.id '
            . 'LEFT JOIN s_articles_details d ON d.ordernumber = v.number '
            . 'SET a.linked = NOT ISNULL(d.ordernumber), a.active = NOT ISNULL(d.orderNumber)',
        'fixMainDetails' => 'UPDATE s_articles a LEFT JOIN s_articles_details d ON d.id = a.main_detail_id '
            . 'SET a.main_detail_id = ( SELECT id FROM s_articles_details WHERE articleID = a.id LIMIT 1 ) '
            . 'WHERE d.id IS NULL; '
            . 'UPDATE s_articles a, s_articles_details d SET d.kind = 1 WHERE d.id = a.main_detail_id;',
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

    public function getArticlesByIds(array $ids)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('ids', $ids)
            ->getResult();
    }

    public function getValidVariants(Article $icArticle)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('id', $icArticle->getId())
            ->getResult();
    }

    public function getInvalidVariants(Article $icArticle)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('id', $icArticle->getId())
            ->getResult();
    }

    public function getHavingRelatedArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('relatedIds', $relatedIds)
            ->getResult();
    }

    public function getHavingSimilarArticles(array $relatedIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('similarIds', $relatedIds)->getResult();
    }

    public function getLinkedArticlesHavingOptions($optionIds)
    {
        return $this->getQuery(__FUNCTION__)
            ->setParameter('optionIds', $optionIds)->getResult();
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

    public function exportMappedProperties(string $articleConfigFile = null)
    {
        $articleConfigFile = $articleConfigFile ?? $this->articleConfigFile;
        $propertyMappings = $this->getMappedProperties();
        if (!empty($propertyMappings)) {
            /** @noinspection PhpUndefinedFieldInspection */
            Factory::toFile($articleConfigFile, $propertyMappings);

            $this->log->debug(sprintf("Exported %s article mappings to Config\\%s.",
                count($propertyMappings),
                basename($articleConfigFile)
            ));
        }
    }

    public function getMappedProperties()
    {
        return $this->getProperties($this->mappedProperties);
    }

    public function getProperties(array $properties)
    {
        $properties = array_map(function($property) {
            return 'a.' . $property;
        }, $properties);
        $properties = implode(', ', $properties);

        $dql = $this->dql[__FUNCTION__];
        $dql = str_replace(':properties', $properties, $dql);
        return $this->getEntityManager()
            ->createQuery($dql)
            ->getResult();
    }

    public function exportDosages()
    {
        $dosages = $this->getQuery('getDosages')->getResult();
        if (!empty($dosages)) {
            /** @noinspection PhpUndefinedFieldInspection */
            Factory::toFile(__DIR__ . '/../Config/test.php', $dosages);
        }
    }

    /**
     * An article validates true if either it's $accepted member is true
     * and at least one of the article's variants validates true
     *
     * @param Article $article
     * @return bool
     */
    public function validateArticle(Article $article): bool
    {
        if (!$article->isAccepted()) {
            return false;
        }
        $variants = $article->getVariants();
        foreach ($variants as $variant) {
            if ($variant->isValid()) {
                return true;
            }
        }
        return false;
    }
}
