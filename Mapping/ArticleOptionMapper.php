<?php

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use MxcDropshipInnocigs\Toolbox\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Configurator\SetRepository;

class ArticleOptionMapper
{
    /**
     * @var LoggerInterface $log
     */
    protected $log;
    /**
     * @var GroupRepository $groupRepository
     */
    protected $groupRepository;
    /**
     * @var SetRepository $setRepository
     */
    protected $setRepository;
    /**
     * @var PropertyMapper $mapper
     */
    protected $mapper;

    /**
     * ArticleOptionMapper constructor.
     *
     * @param GroupRepository $groupRepository
     * @param SetRepository $setRepository
     * @param PropertyMapper $mapper
     * @param LoggerInterface $log
     */
    public function __construct(GroupRepository $groupRepository, SetRepository $setRepository, PropertyMapper $mapper, LoggerInterface $log)
    {
        $this->log = $log;
        $this->groupRepository = $groupRepository;
        $this->setRepository = $setRepository;
        $this->mapper = $mapper;
    }

    /**
     * Create missing configurator groups and options for an InnoCigs article.
     *
     * @param InnocigsArticle $article
     */
    public function createShopwareGroupsAndOptions(InnocigsArticle $article) {
        $icVariants = $article->getVariants();
        $this->log->info(sprintf('%s: Creating configurator groups and options for InnoCigs Article %s',
            __FUNCTION__,
            $article->getCode()
        ));
        $groupOptions = [];
        foreach ($icVariants as $icVariant) {
            if ($icVariant->isIgnored()) continue;
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
                $groupOptions[$icGroupName][$icOptionName][] = $icVariant;
                $this->log->debug(sprintf('Variant %s (%s) has option %s from group %s.',
                    $icVariant->getCode(),
                    $icVariant->getId(),
                    $icOptionName,
                    $icGroupName
                ));
            }
        }
        foreach ($groupOptions as $icGroupName => $options) {
            // Because some variants may be set to be ignored there is a chance that we have
            // groups with just a single option. We do not apply such groups, because
            // selecting from a single choice is not meaningful.
            if (count($options) <  2) continue;
            foreach ($options as $icOptionName => $icVariants) {
                $this->groupRepository->createGroup($icGroupName);
                $swOption = $this->groupRepository->createOption($icGroupName, $icOptionName);
                foreach ($icVariants as $icVariant) {
                    $icVariant->addShopwareOption($swOption);
                    $this->log->debug(sprintf('Adding shopware option %s (%s) to variant %s (%s)',
                        $swOption->getName(),
                        $swOption->getId(),
                        $icVariant->getCode(),
                        $icVariant->getId()
                    ));
                }
            }
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->groupRepository->flush();
    }

    /**
     * Create and setup a configurator set for a Shopware article
     *
     * @param InnocigsArticle $icArticle
     * @return null|\Shopware\Models\Article\Configurator\Set
     */
    public function createConfiguratorSet(InnocigsArticle $icArticle)
    {
        $variants = $icArticle->getVariants();
        if (count($variants) < 2) {
            $this->log->info(sprintf('%s: No Shopware configurator set required. InnoCigs article %s does not provide variants.',
                __FUNCTION__,
                $icArticle->getCode()
            ));
            return null;
        }
        $this->createShopwareGroupsAndOptions($icArticle);
        $setName = 'mxc-set-' . $this->mapper->mapArticleCode($icArticle->getCode());
        $this->setRepository->initSet($setName);

        $this->log->info(sprintf('%s: Setup of configurator %s set for InnoCigs Article %s',
            __FUNCTION__,
            $setName,
            $icArticle->getCode()
        ));

        // add the options belonging to this article and variants
        foreach ($variants as $variant) {
            if ($variant->isIgnored()) {
                continue;
            }
            /**
             * @var InnocigsVariant $variant
             */
            $options = $variant->getShopwareOptions();
            foreach ($options as $option) {
                $this->setRepository->addOption($option);
            }
        }
        return $this->setRepository->prepareSet($icArticle->getArticle());
    }

    public function getShopwareOptions(InnocigsVariant $variant) {
        return $variant->getShopwareOptions();
    }
}