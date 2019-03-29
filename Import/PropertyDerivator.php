<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 25.02.2019
 * Time: 14:24
 */

namespace MxcDropshipInnocigs\Import;


use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class PropertyDerivator
{
    protected $log;
    protected $modelManager;
    protected $config;
    protected $articleGroups;

    public function __construct(ModelManager $modelManager, Config $config, LoggerInterface $log)
    {
        $this->log = $log;
        $this->config = $config->toArray();
        $this->modelManager = $modelManager;
    }

    public function derive(array $articles)
    {
        $this->articleGroups = [];
        //$articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        foreach ($articles as $number => $article) {
            $this->deriveProperties($article);
        }
        $this->deriveRelatedArticles();
        $this->deriveSimilarArticles();

        $this->dumpProductNames();
        $this->dumpRelatedArticles();
        $this->dumpSimilarArticles();
    }

    protected function dumpProductNames() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        $products = [];
        foreach ($articles as $number => $article) {
            /** @var Article $article */
            $name = $article->getCommonName();
            $products[$name] = true;
        }
        ksort($products);
        (new ArrayReport())(['peProducts' => array_keys($products)]);
    }

    public function deriveProperties(Article $article)
    {
        $type = $article->getType();

        $commonName = $this->deriveCommonName($article);
        $article->setCommonName($commonName);

        switch ($commonName) {
            case 'K2 & K3':
                {
                    // special case where one article name indicates spare part for two articles
                    $this->articleGroups[$type]['K2'][] = $article;
                    $this->articleGroups[$type]['K3'][] = $article;
                }
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'Aromamizer Supreme RDTA V2':
                $this->articleGroups[$type][$commonName . '.1'][] = $article;
                // intentional fall through
            default:
                $this->articleGroups[$type][$commonName][] = $article;
        }

        $article->setPiecesPerPack($this->derivePiecesPerPack($article));
        $article->setDosage($this->deriveDosage($article));
    }

    protected function deriveCommonName(Article $article)
    {
        $name = $article->getName();
        $raw = explode(' - ', $name);
        $index = $this->config['common_name_index'][$raw[0]][$raw[1]] ?? 1;
        $name = trim($raw[$index] ?? $raw[0]);
        $replacements = [ '~ \(\d+ Stück pro Packung\)~', '~Head$~'];
        $name = preg_replace($replacements, '', $name);
        return trim($name);
    }

    protected function derivePiecesPerPack(Article $article)
    {
        $name = $article->getName();
        $matches = [];
        $ppp = 1;
        if (preg_match('~\((\d+) Stück~', $name, $matches) === 1) {
            $ppp = $matches[1];
        };
        return $ppp;
    }

    protected function deriveDosage(Article $article)
    {
        if ($article->getType() !== 'AROMA') return null;
        $supplier = $article->getSupplier();
        $dosage = $this->config['recommended_dosage'][$supplier];
        return $dosage;
    }

    protected function getAsscociatedArticles(Article $article, array $config) : ArrayCollection
    {
        $associatedArticles = new ArrayCollection();
        foreach ($config['groups'] as $groupName) {
            foreach ($this->articleGroups[$groupName] as $cName => $group) {
                /** @var Article $relatedArticle */
                foreach ($group as $relatedArticle) {
                    if ($config['match_common_name'] && $article->getCommonName() !== $cName) continue;
                    if ($associatedArticles->contains($relatedArticle)) continue;

                    $associatedArticles->add($relatedArticle);
                }
            }
        }
        return $associatedArticles;
    }

    protected function deriveRelatedArticles()
    {
        foreach ($this->config['related_article_groups'] as $group => $setting) {
            foreach ($this->articleGroups[$group] as $articles) {
                /** @var Article $article */
                foreach ($articles as $article) {
                    $relatedArticles = $this->getAsscociatedArticles($article, $setting);
                    $article->setRelatedArticles($relatedArticles);
                }
            }
        }
    }

    protected function isSimilarFlavoredArticle(Article $article1, Article $article2)
    {
        $flavor1 = array_map('trim', explode(',', $article1->getFlavor()));
        $flavor2 = array_map('trim', explode(',', $article2->getFlavor()));
        foreach ($this->config['similar_flavors'] as $flavor) {
            if (in_array($flavor, $flavor1) && in_array($flavor, $flavor2)) {
                return true;
            }
        }
        $common = array_intersect($flavor1, $flavor2);
        $min = min(count($flavor1), count($flavor2));
        $count = count($common);
        $requiredMatches = $min === 1 ? 1 : $min - 1;
        return ($count === $requiredMatches);
    }

    protected function deriveSimilarFlavoredArticles($group)
    {
        $articles = [];
        foreach ($this->articleGroups[$group] as $groupArticles) {
            foreach($groupArticles as $article) {
                $articles[] = $article;
            };
        }
        foreach ($articles as $article1) {
            /** @var ArrayCollection $similarArticles */
            $similarArticles = $article1->getSimilarArticles();
            foreach ($articles as $article2) {
                if ($article1 === $article2 || ! $this->isSimilarFlavoredArticle($article1, $article2)) continue;
                if (! $similarArticles->contains($article2)) {
                    $article1->addSimilarArticle($article2);
                }
            }
        }
    }

    protected function deriveSimilarArticles()
    {
        foreach ($this->config['similar_article_groups'] as $group => $setting) {
            foreach ($this->articleGroups[$group] as $articles) {
                /** @var Article $article */
                foreach ($articles as $article) {
                    $similarArticles = $this->getAsscociatedArticles($article, $setting);
                    $article->setSimilarArticles($similarArticles);
                }
            }
        }

        foreach (['SHAKE_VAPE', 'LIQUID', 'AROMA'] as $group) {
            $this->deriveSimilarFlavoredArticles($group);
        }
    }

    public function export() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        $export = [];
        /** @var  Article $article */
        foreach($articles as $number => $article) {
            $base = $article->getBase();
            if ($base !== null) {
                $export[$number]['base'] = $base;
            }
            $dosage = $article->getDosage();
            if ($dosage !== null) {
                $export[$number]['dosage'] = $dosage;
            }
        }
        (new ArrayReport())(['peProperties' => $export]);
    }

    public function dumpRelatedArticles() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        /** @var Article $article */
        $relatedArticleList = [];
        foreach ($articles as $number => $article) {
            $relatedArticles = $article->getRelatedArticles();
            if ($relatedArticles->isEmpty()) continue;
            $list = [];
            foreach ($relatedArticles as $relatedArticle) {
                $list[] = $relatedArticle->getName();
            }
            $relatedArticleList[$number] = [
                'name' => $article->getName(),
                'related_articles' => $list,
            ];
        }
        (new ArrayReport())(['peRelatedArticles' => $relatedArticleList]);
    }

    public function dumpSimilarArticles() {
        $articles = $this->modelManager->getRepository(Article::class)->getAllIndexed();
        /** @var Article $article */
        $similarArticleList = [];
        foreach ($articles as $number => $article) {
            $similarArticles = $article->getSimilarArticles();
            if ($similarArticles->isEmpty()) continue;
            $list = [];
            foreach ($similarArticles as $similarArticle) {
                $list[] = [
                    'name' => $similarArticle->getName(),
                    'flavor' => $similarArticle->getFlavor(),
                ];
            }
            $similarArticleList[$number] = [
                'name' => $article->getName(),
                'flavor' => $article->getFlavor(),
                'similar_articles' => $list,
            ];
        }
        (new ArrayReport())(['peSimilarArticles' => $similarArticleList]);
    }

}