<?php

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\Group;
use MxcDropshipInnocigs\Models\Current\Variant;
use MxcDropshipInnocigs\Toolbox\Configurator\GroupRepository;
use MxcDropshipInnocigs\Toolbox\Configurator\SetRepository;

class ArticleOptionMapper
{
    /** @var LoggerInterface $log */
    protected $log;

    /** @var GroupRepository $groupRepository */
    protected $groupRepository;

    /** @var SetRepository $setRepository */
    protected $setRepository;

    /** @var InnocigsEntityValidator $validator */
    protected $validator;

    /**
     * ArticleOptionMapper constructor.
     *
     * @param GroupRepository $groupRepository
     * @param SetRepository $setRepository
     * @param InnocigsEntityValidator $validator
     * @param LoggerInterface $log
     */
    public function __construct(
        GroupRepository $groupRepository,
        SetRepository $setRepository,
        InnocigsEntityValidator $validator,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->groupRepository = $groupRepository;
        $this->setRepository = $setRepository;
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
             * @var Variant $icVariant
             */
            $icOptions = $icVariant->getOptions();
            foreach ($icOptions as $icOption) {
                /**
                 * @var Group $icGroup
                 */
                $icGroup = $icOption->getIcGroup();
                $groupName = $icGroup->getName();
                $optionName = $icOption->getName();

                $this->log->debug(sprintf('ImportVariant %s (%s) has option %s from group %s.',
                    $icVariant->getCode(),
                    $icVariant->getId(),
                    $optionName,
                    $groupName
                ));

                // A valid variant may hold options which are ignored. Skip variants with ignored options.
                if (! $this->validator->validateOption($icOption)) {
                    $this->log->debug('Named option does not validate. ImportVariant ignored.');
                    continue 2;
                }
                $groupOptions[$groupName][$optionName][] = $icVariant;
            }
        }
        foreach ($groupOptions as $groupName => $options) {
            // Because some variants may be set to be ignored (accepted = false) there is a chance that we have
            // groups with just a single option. We do not apply such groups, because selecting from a single
            // choice is not meaningful.
            if (count($options) <  2) {
                $this->log->notice(sprintf('Skipping creation/update of group %s because there are less than two options available.',
                    $groupName
                ));
                continue;
            }
            foreach ($options as $optionName => $icVariants) {
                $this->groupRepository->createGroup($groupName);
                $swOption = $this->groupRepository->createOption($groupName, $optionName);
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

    protected function getValidVariants(Article $article) : array {
        $validVariants = [];
        $variants = $article->getVariants();
        foreach ($variants as $variant) {
            if ($this->validator->validateVariant($variant)) {
                $validVariants[] = $variant;
            }
        }
        return $validVariants;
    }

    /**
     * Create and setup a configurator set for a Shopware article
     *
     * @param Article $icArticle
     * @return null|\Shopware\Models\Article\Configurator\Set
     */
    public function createConfiguratorSet(Article $icArticle)
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

        $this->log->info(sprintf('%s: Creating configurator groups and options for InnoCigs ImportArticle %s.',
            __FUNCTION__,
            $icArticle->getCode()
        ));

        $this->createShopwareGroupsAndOptions($variants);

        $name = 'mxc-set-' . $icArticle->getCode();
        $this->log->info(sprintf('%s: Creating configurator set %s for InnoCigs ImportArticle %s.',
            __FUNCTION__,
            $name,
            $icArticle->getCode()
        ));
        $set = $this->setRepository->initSet($name);

        // add the options belonging to this article and variants
        foreach ($variants as $variant) {
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

    public function getShopwareOptions(Variant $variant) {
        return $variant->getShopwareOptions();
    }
}