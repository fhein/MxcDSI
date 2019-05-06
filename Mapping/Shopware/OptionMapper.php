<?php

namespace MxcDropshipInnocigs\Mapping\Shopware;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipInnocigs\Toolbox\Shopware\Configurator\SetRepository;
use Shopware\Models\Article\Configurator\Set;

class OptionMapper implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var GroupRepository $groupRepository */
    protected $groupRepository;

    /** @var SetRepository $setRepository */
    protected $setRepository;

    /**
     * OptionMapper constructor.
     *
     * @param GroupRepository $groupRepository
     * @param SetRepository $setRepository
     */
    public function __construct(
        GroupRepository $groupRepository,
        SetRepository $setRepository
    ) {
        $this->groupRepository = $groupRepository;
        $this->setRepository = $setRepository;
    }

    /**
     * Create missing configurator groups and options for an InnoCigs article.
     *
     * @param array $variants
     */
    public function createShopwareGroupsAndOptions(array $variants) {
        $this->log->enter();
        $groupOptions = [];
        foreach ($variants as $variant) {
            // if (! $variant->isValid()) continue;
            /** @var Variant $variant */
            $icOptions = $variant->getOptions();
            $variant->setShopwareOptions([]);
            /** @var Option $icOption */
            foreach ($icOptions as $icOption) {
                $groupName = $icOption->getIcGroup()->getName();
                // if (! $icOption->isValid()) continue;
                $optionName = $icOption->getName();

                $this->log->debug(sprintf('ImportVariant %s (%s) has option %s from group %s.',
                    $variant->getNumber(),
                    $variant->getId(),
                    $optionName,
                    $groupName
                ));
                $groupOptions[$groupName][$optionName][] = $variant;
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
                // @todo: Product name should reflect the single option name if not "1er Packung"
                continue;
            }
            array_multisort(array_keys($options), SORT_NATURAL, $options);

            // for an unknown reason it is necessary to recreate the group and option lookup table
            // allthough it is not invalid. Otherwise doctrine will duplicate group and option entities.
            $this->groupRepository->createLookupTable();
            foreach ($options as $optionName => $variants) {
                $this->groupRepository->createGroup($groupName);
                $sortOptions[$groupName] = true;
                $swOption = $this->groupRepository->createOption($groupName, $optionName);
                foreach ($variants as $variant) {
                    $variant->addShopwareOption($swOption);
                    $this->log->notice(sprintf('Adding shopware option %s (id: %s) to variant %s (id: %s).',
                        $swOption->getName(),
                        $swOption->getId() ?? 'new',
                        $variant->getNumber(),
                        $variant->getId()
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
     * @param Product $product
     * @return null|Set
     */
    public function createConfiguratorSet(Product $product)
    {
        $validVariants = [];
        foreach ($product->getVariants() as $variant) {
            if (! $variant->isValid()) continue;
            $validVariants[] = $variant;
        }
        // $validVariants = $product->getValidVariants();
        if (count($validVariants) < 2) {
            $this->log->notice(sprintf('%s: No Shopware configurator set required. InnoCigs article %s does '
                . 'not provide more than one variant which is set not to get ignored.',
                __FUNCTION__,
                $product->getNumber()
            ));
            return null;
        }

        /** @var Variant $variant */
        $descriptions = [];
        foreach ($validVariants as $variant) {
            /** @var Option $option */
            $description = '';
            foreach ($variant->getOptions() as $option) {
                $description .= $option->getIcGroup()->getName() . ': ' . $option->getName() . ', ';
            }
            $descriptions[] = $description;
        }
        $this->log->debug('Valid variants for product: ' . $product->getName());
        $this->log->debug(var_export($descriptions, true));

        $this->createShopwareGroupsAndOptions($validVariants);

        $name = 'mxc-set-' . $product->getNumber();
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
        $set->getArticles()->add($product->getArticle());
        return $set;
    }
}