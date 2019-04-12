<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Configurator\Set;

class ShopwareOptionMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var GroupRepository $groupRepository */
    protected $groupRepository;

    /** @var SetRepository $setRepository */
    protected $setRepository;

    /** @var ModelManager $modelManager */
    protected $modelManager;

    /**
     * ShopwareOptionMapper constructor.
     *
     * @param GroupRepository $groupRepository
     * @param SetRepository $setRepository
     * @param ModelManager $modelManager
     * @param LoggerInterface $log
     */
    public function __construct(
        GroupRepository $groupRepository,
        SetRepository $setRepository,
        ModelManager $modelManager,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->groupRepository = $groupRepository;
        $this->setRepository = $setRepository;
        $this->modelManager = $modelManager;
    }

    /**
     * Create missing configurator groups and options for an InnoCigs article.
     *
     * @param array $icVariants
     */
    public function createShopwareGroupsAndOptions(array $icVariants) {
        $this->log->enter();
        $groupOptions = [];
        foreach ($icVariants as $icVariant) {
            /** @var Variant $icVariant */
            $icOptions = $icVariant->getOptions();
            $icVariant->setShopwareOptions([]);
            /** @var Option $icOption */
            foreach ($icOptions as $icOption) {
                $groupName = $icOption->getIcGroup()->getName();
                $optionName = $icOption->getName();

                $this->log->debug(sprintf('ImportVariant %s (%s) has option %s from group %s.',
                    $icVariant->getNumber(),
                    $icVariant->getId(),
                    $optionName,
                    $groupName
                ));
                $groupOptions[$groupName][$optionName][] = $icVariant;
            }
        }
        $sortOptions = [];
        foreach ($groupOptions as $groupName => $options) {
            // Because some variants may be set to be ignored (accepted = false) there is a chance that we have
            // groups with just a single option. We do not apply such groups, because selecting from a single
            // choice is not meaningful.
            if (count($options) <  2) {
                $this->log->notice(sprintf('Skipping creation/update of group %s because there are less than two options available.',
                    $groupName
                ));
                // @todo: Article name should reflect the single option name if not "1er Packung"
                continue;
            }
            array_multisort(array_keys($options), SORT_NATURAL, $options);

            // for an unknown reason it is necessary to recreate the group and option lookup table
            // allthough it is not invalid. Otherwise doctrine will duplicate group and option entities.
            $this->groupRepository->createLookupTable();
            foreach ($options as $optionName => $icVariants) {
                $this->groupRepository->createGroup($groupName);
                $sortOptions[$groupName] = true;
                $swOption = $this->groupRepository->createOption($groupName, $optionName);
                foreach ($icVariants as $icVariant) {
                    $icVariant->addShopwareOption($swOption);
                    $this->log->notice(sprintf('Adding shopware option %s (id: %s) to variant %s (id: %s).',
                        $swOption->getName(),
                        $swOption->getId() ?? 'new',
                        $icVariant->getNumber(),
                        $icVariant->getId()
                    ));
                }
            }
        }
        $sortOptions = array_keys($sortOptions);
        foreach ($sortOptions as $groupName) {
            $this->groupRepository->sortOptions($groupName);
        }
        $this->log->leave();
    }

    /**
     * Create and setup a configurator set for a Shopware article
     *
     * @param Article $icArticle
     * @return null|Set
     */
    public function createConfiguratorSet(Article $icArticle)
    {
        $validVariants = $icArticle->getValidVariants();
        if (count($validVariants) < 2) {
            $this->log->notice(sprintf('%s: No Shopware configurator set required. InnoCigs article %s does '
                . 'not provide more than one variant which is set not to get ignored.',
                __FUNCTION__,
                $icArticle->getNumber()
            ));
            return null;
        }

        $this->createShopwareGroupsAndOptions($validVariants);

        $name = 'mxc-set-' . $icArticle->getNumber();
        $set = $this->setRepository->getSet($name);

        // add the options belonging to this article and variants
        foreach ($validVariants as $variant) {
            /**
             * @var Variant $variant
             */
            $options = $variant->getShopwareOptions();
            foreach ($options as $option) {
                $this->setRepository->addOption($option);
            }
        }
        $set->getArticles()->add($icArticle->getArticle());
        return $set;
    }
}