<?php

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Configurator\Set;
use Zend\Log\Logger;

class ArticleAttributeMapper
{
    use ModelManagerTrait;

    private $log;
    private $groupRepository;

    public function __construct(GroupRepository $repository, Logger $log)
    {
        $this->log = $log;
        $this->groupRepository = $repository;
    }

    private function createShopwareGroupsAndOptions(InnocigsArticle $article) {
        $icVariants = $article->getVariants();
        foreach ($icVariants as $icVariant) {
            /**
             * @var InnocigsVariant $icVariant
             */
            $icAttributes = $icVariant->getAttributes();
            foreach ($icAttributes as $icAttribute) {
                /**
                 * @var InnocigsAttribute $icAttribute
                 */
                $icGroupName = $icAttribute->getAttributeGroup()->getName();
                $swGroup = $this->groupRepository->loadGroup($icGroupName) ?? $this->groupRepository->createGroup($icGroupName);

                $icAttributeName = $icAttribute->getName();
                if (! $this->groupRepository->hasOption($swGroup, $icAttributeName)) {
                    $this->groupRepository->createOption($swGroup, $icAttributeName);
                }
            }
        }
        //$this->groupRepository->flush();
        $this->flush();
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
            $attributes = $variant->getAttributes();
            foreach ($attributes as $attribute) {
                /**
                 * @var InnocigsAttribute $attribute
                 */
                $groupName = $attribute->getAttributeGroup()->getName();
                $optionName = $attribute->getName();
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