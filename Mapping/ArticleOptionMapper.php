<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Configurator\Set;
use Zend\Log\Logger;

class ArticleOptionMapper
{
    use ModelManagerTrait;

    private $log;
    private $groupRepository;

    public function __construct(GroupRepository $repository, Logger $log)
    {
        $this->log = $log;
        $this->groupRepository = $repository;
        $this->log->info(__CLASS__ . ' created.');
    }

    public function createShopwareGroupsAndOptions(InnocigsArticle $article) {
        $icVariants = $article->getVariants();
        $this->log->info(__FUNCTION__ . ': ' . count($icVariants) . ' variants retrieved.');
        foreach ($icVariants as $icVariant) {
            /**
             * @var InnocigsVariant $icVariant
             */
            $this->log->info(__FUNCTION__ . ': Trying to retrieve options from variant: ' . $icVariant->getCode());
            $this->log->info(var_export($icVariant->__debugInfo(), true));
            $icOptions = $icVariant->getOptions();
            $this->log->info(__FUNCTION__ . ': ' . count($icOptions) . ' options retrieved.');
            foreach ($icOptions as $icOption) {
                /**
                 * @var InnocigsOption $icOption
                 */
                $icGroupName = $icOption->getGroup()->getName();
                $swGroup = $this->groupRepository->loadGroup($icGroupName) ?? $this->groupRepository->createGroup($icGroupName);
                $this->log->info(__FUNCTION__ . ': Got swGroup');


                $icOptionName = $icOption->getName();
                if (! $this->groupRepository->hasOption($swGroup, $icOptionName)) {
                    $this->groupRepository->createOption($swGroup, $icOptionName);
                }
            }
        }
        $this->groupRepository->flush();
    }

    private function createArticleSet(InnocigsArticle $article) {
        $options = [];
        $groups = [];
        $variants = $article->getVariants();

        // compute the groups and options belonging to this set
        foreach ($variants as $variant) {
            /**
             * @var InnocigsVariant $variant
             */
            $options = $variant->getOptions();
            foreach ($options as $option) {
                /**
                 * @var InnocigsOption $option
                 */
                $groupName = $option->getGroup()->getName();
                $optionName = $option->getName();
                if (! isset($groups[$groupName])) {
                    $group = $this->groupRepository->getGroup($groupName);
                    $groups[$groupName] = $group;
                } else {
                    $group = $groups[$groupName];
                }
                $options[] = $this->groupRepository->getOption($group, $optionName);
            }
        }
        // discard array keys
        $groups = array_values($groups);

        // create the shopware configurator set
        $set = new Set();
        $set->setName('mxc-set-' .  $article->getCode());
        // standard set
        $set->setType(0);
        //$set->setArticles(new ArrayCollection([$article]));
        // Todo: set Article when created
        $set->setPublic(false);
        $set->setGroups(new ArrayCollection($groups));
        $set->setOptions(new ArrayCollection($options));
        return $set;
    }

    public function createConfiguratorSet(InnocigsArticle $article)
    {
        if (count($article->getVariants()) < 2) {
            return null;
        }
        $this->createShopwareGroupsAndOptions($article);
        $set = $this->createArticleSet($article);
        $this->persist($set);
        $this->flush();

        return $set;
    }
}