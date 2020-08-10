<?php

namespace MxcDropshipIntegrator\Mapping\Shopware;

use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\Option;
use MxcDropshipIntegrator\Models\Product;
use MxcDropshipIntegrator\Models\Variant;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\GroupRepository;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\OptionSorter;
use MxcDropshipIntegrator\Toolbox\Shopware\Configurator\SetRepository;
use Shopware\Models\Article\Article;
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
            // We do not create a Packungsgröße configurator if only one valid option is available (usually 1er Packung.)
            // We do create single option configurators for other options, because InnoCigs delivers degraded
            // products, which once were multi-option, but now have a single option left. This is necessary
            // because the remaining option identifies a product property, which would not get displayed to
            // the shop user if we skipped the configurator.
            if (count($options) <  2 && $groupName === 'Packungsgröße') {
                $optionName = array_keys($options)[0];
                if ($optionName === '1er Packung') continue;
            }
            //@todo: only variables can be passed by reference, param 1 is wrong
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

    public function needsUpdate(Product $product)
    {
        $variants = $product->getVariants();
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            $valid = $variant->isValid();
            if ($valid && ($variant->getDetail() === null)) return true;
            if (! $valid && ($variant->getDetail() !== null)) return true;
        }
        return false;
    }

    protected function getValidVariants(Product $product)
    {
        $variants = $product->getVariants();
        $validVariants = [];
        /** @var Variant $variant */
        foreach ($variants as $variant) {
            if ($variant->isValid()) $validVariants[] = $variant;
        }
        return $validVariants;
    }

    /**
     * @param Product $product
     * @param array $validVariants
     * @return Set
     */
    protected function createConfiguratorSet(Product $product, array $validVariants): Set
    {
        $name = 'mxc-set-' . $product->getNumber();
        $set = $this->setRepository->getSet($name);

        // add the options belonging to this article and variants
        foreach ($validVariants as $variant) {
            /** @var Variant $variant */
            $options = $variant->getShopwareOptions();
            foreach ($options as $option) {
                $this->setRepository->addOption($option);
            }
        }
        $set->getArticles()->add($product->getArticle());
        return $set;
    }

    /**
     * Create and setup a configurator set for a Shopware article
     *
     * @param Product $product
     * @return array|null
     */
    public function updateConfiguratorSet(Product $product)
    {
        $needsUpdate = $this->needsUpdate($product);
        if (! $needsUpdate) {
            $this->log->debug('Configurator set does not require an update.');
            /** @var Article $article */
            $article = $product->getArticle();
            return [ $needsUpdate, $article->getConfiguratorSet()];
        }

        $validVariants = $this->getValidVariants($product);
        $count = count($validVariants);

        if ($count < 1) return [$needsUpdate, false];

        $this->createShopwareGroupsAndOptions($validVariants);
        return [$needsUpdate, $this->createConfiguratorSet($product, $validVariants)];
    }
}