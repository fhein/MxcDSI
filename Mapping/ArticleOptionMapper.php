<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;
use Zend\Log\Logger;

class ArticleOptionMapper
{
    use ModelManagerTrait;

    private $log;
    private $groupRepository;
    private $mapper;

    public function __construct(GroupRepository $repository, PropertyMapper $mapper, Logger $log)
    {
        $this->log = $log;
        $this->groupRepository = $repository;
        $this->mapper = $mapper;
    }

    public function createShopwareGroupsAndOptions(InnocigsArticle $article) {
        $icVariants = $article->getVariants();
        foreach ($icVariants as $icVariant) {
            /**
             * @var InnocigsVariant $icVariant
             */
            $icOptions = $icVariant->getOptions();
            foreach ($icOptions as $icOption) {
                /**
                 * @var InnocigsOption $icOption
                 */
                $icGroupName =  $this->mapper->mapGroupName($icOption->getGroup()->getName());
                $icOptionName = $this->mapper->mapOptionName($icOption->getName());

                $this->groupRepository->createGroup($icGroupName);
                $this->groupRepository->createOption($icGroupName, $icOptionName);
            }
        }
        $this->groupRepository->commit();
    }

    public function createConfiguratorSet(InnocigsArticle $icArticle, Article $swArticle)
    {
        $variants = $icArticle->getVariants();
        if (count($variants) < 2) {
            return null;
        }
        $this->log->info('Article: '. $icArticle->getName() . ': Creating set for ' . count($variants) . ' variants.');
        $setRepository = new SetRepository();
        $setRepository->initSet('mxc-set-' . $this->mapper->mapArticleCode($icArticle->getCode()));

        // add the options belonging to this article and variants
        foreach ($variants as $variant) {
            /**
             * @var InnocigsVariant $variant
             */
            $options = $variant->getOptions();
            foreach ($options as $icOption) {
                /**
                 * @var InnocigsOption $icOption
                 */
                $groupName = $this->mapper->mapGroupName($icOption->getGroup()->getName());
                $optionName = $this->mapper->mapOptionName($icOption->getName());
                $option = $this->groupRepository->getOption($groupName, $optionName);
                $setRepository->addOption($option);
            }
        }
        return $setRepository->prepareSet($swArticle);
    }
}