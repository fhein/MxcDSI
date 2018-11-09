<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Convenience\DoctrineModelManagerTrait;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsAttribute;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;
use Zend\Log\Logger;

class ArticleAttributeMapper
{
    use DoctrineModelManagerTrait;

    private $log;
    private $swGroupOptionLookup;
    private $swGroupRepo;

    public function __construct(Logger $log)
    {
        $this->log = $log;
        $this->swGroupRepo = $this->getRepository(Group::class);
    }

    private function getGroupOptionLookupTable()
    {
        $group = Group::class;
        $option = Option::class;
        $dql = "SELECT gname gName, o.name oName FROM $group g JOIN $option o WHERE o.group = g.id";
        $array = $this->createQuery($dql)->getScalarResult();
        $lookup = [];
        foreach ($array as $entry) {
            $lookup[$entry['gName']][$entry['oName']] = true;
        }
        $this->log->info(var_export($lookup, true));
        return $lookup;
    }

    private function createShopwareGroupsAndOptions($icVariants)
    {
        $lookup = $this->getGroupOptionLookupTable();

        foreach ($icVariants as $icVariant) {
            /**
             * @var InnocigsVariant $icVariant
             */
            $icAttributes = $icVariant->getAttributes();
            foreach ($icAttributes as $icAttribute) {
                /**
                 * @var InnocigsAttribute $icAttribute
                 */
                $swGroup = null;
                $icGroupName = $icAttribute->getAttributeGroup()->getName();
                if (!$lookup[$icGroupName]) {
                    $lookup[$icGroupName] = 1;
                    $swGroup = new Group();
                    $swGroup->setName($icGroupName);
                    $swGroup->setPosition(count($lookup));
                    $this->persist($swGroup);
                }

                $swOption = null;
                $icAttributeName = $icAttribute->getName();
                if (!$lookup[$icGroupName][$icAttributeName]) {
                    $lookup[$icGroupName][$icAttributeName] = true;
                    $swOption = new Option();
                    $swOption->setName($icAttributeName);
                    $swOption->setGroup($swGroup);
                    $swOption->setPosition(count($lookup[$icGroupName]));
                    $this->persist($swOption);
                }
            }
        }
        $this->flush();
    }

    public function createConfiguratorSet(InnocigsArticle $article)
    {
        $icVariants = $article->getVariants();
        if (count($icVariants) === 0) {
            return null;
        }
        $this->createShopwareGroupsAndOptions($icVariants);
        $icGroupAttributeMap = $article->getGroupAttributeMap();

        // here all groups and options are available in shopware

        $swSet = new Set();
        foreach ($icVariants as $icVariant) {

        }
    }
}