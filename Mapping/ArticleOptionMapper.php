<?php

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\InnocigsArticle;
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
     * @var InnocigsEntityValidator $validator
     */
    protected $validator;

    /**
     * ArticleOptionMapper constructor.
     *
     * @param GroupRepository $groupRepository
     * @param SetRepository $setRepository
     * @param PropertyMapper $mapper
     * @param InnocigsEntityValidator $validator
     * @param LoggerInterface $log
     */
    public function __construct(
        GroupRepository $groupRepository,
        SetRepository $setRepository,
        PropertyMapper $mapper,
        InnocigsEntityValidator $validator,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->groupRepository = $groupRepository;
        $this->setRepository = $setRepository;
        $this->mapper = $mapper;
        $this->validator = $validator;
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
            /**
             * @var InnocigsVariant $icVariant
             */
            $icOptions = $icVariant->getOptions();
            foreach ($icOptions as $icOption) {
                $icGroupName = $icOption->getGroup()->getName();
                $icOptionName = $icOption->getName();

                $this->log->debug(sprintf('Variant %s (%s) has option %s from group %s.',
                    $icVariant->getCode(),
                    $icVariant->getId(),
                    $icOptionName,
                    $icGroupName
                ));

                // A valid variant may hold options which are invalid. Skip invalid options.
                if (! $this->validator->validate($icOption)) {
                    $this->log->debug('Named option does not validate and is ignored.');
                    continue;
                }
                $swGroupName =  $this->mapper->mapGroupName($icGroupName);
                $swOptionName = $this->mapper->mapOptionName($icOptionName);
                $groupOptions[$swGroupName][$swOptionName][] = $icVariant;
            }
        }
        foreach ($groupOptions as $swGroupName => $options) {
            // Because some variants may be set to be ignored (accepted = false) there is a chance that we have
            // groups with just a single option. We do not apply such groups, because selecting from a single
            // choice is not meaningful.
            if (count($options) <  2) {
                $this->log->notice(sprintf('Skipping creation/update of group %s because there are less than two options available.',
                    $swGroupName
                ));
                continue;
            }
            foreach ($options as $swOptionName => $icVariants) {
                $this->groupRepository->createGroup($swGroupName);
                $swOption = $this->groupRepository->createOption($swGroupName, $swOptionName);
                foreach ($icVariants as $icVariant) {
                    $icVariant->addShopwareOption($swOption);
                    $this->log->notice(sprintf('Adding shopware option %s (id: %s) to variant %s (id: %s).',
                        $swOption->getName(),
                        $swOption->getId(),
                        $icVariant->getCode(),
                        $icVariant->getId()
                    ));
                }
            }
        }
        $this->log->leave();
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->groupRepository->flush();
    }

    protected function getValidVariants(InnocigsArticle $article) : array {
        $validVariants = [];
        $variants = $article->getVariants();
        foreach ($variants as $variant) {
            if ($this->validator->validate($variant)) {
                $validVariants[] = $variant;
            }
        }
        return $validVariants;
    }

    /**
     * Create and setup a configurator set for a Shopware article
     *
     * @param InnocigsArticle $icArticle
     * @return null|\Shopware\Models\Article\Configurator\Set
     */
    public function createConfiguratorSet(InnocigsArticle $icArticle)
    {
        $variants = $this->getValidVariants($icArticle);
        if (count($variants) < 2) {
            $this->log->notice(sprintf('%s: No Shopware configurator set required. InnoCigs article %s does '
                . 'not provide more than one variant which is set not to get ignored.',
                __FUNCTION__,
                $icArticle->getCode()
            ));
            return null;
        }

        $this->log->info(sprintf('%s: Creating configurator groups and options for InnoCigs Article %s.',
            __FUNCTION__,
            $icArticle->getCode()
        ));

        $this->createShopwareGroupsAndOptions($variants);

        $name = 'mxc-set-' . $this->mapper->mapArticleCode($icArticle->getCode());
        $this->log->info(sprintf('%s: Creating configurator set %s for InnoCigs Article %s.',
            __FUNCTION__,
            $name,
            $icArticle->getCode()
        ));
        $set = $this->setRepository->initSet($name);

        // add the options belonging to this article and variants
        foreach ($variants as $variant) {
            /**
             * @var InnocigsVariant $variant
             */
            $options = $variant->getShopwareOptions();
            foreach ($options as $option) {
                $this->setRepository->addOption($option);
            }
        }
        $set->getArticles()->add($icArticle->getArticle());
        return $set;
    }

    public function getShopwareOptions(InnocigsVariant $variant) {
        return $variant->getShopwareOptions();
    }
}