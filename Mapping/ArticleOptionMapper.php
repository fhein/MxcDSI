<?php

namespace MxcDropshipInnocigs\Mapping;

use Mxc\Shopware\Plugin\Convenience\ModelManagerTrait;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;
use Shopware\Models\Article\Article;

class ArticleOptionMapper
{
    use ModelManagerTrait;

    private $log;
    private $groupRepository;
    private $mapper;

    public function __construct(GroupRepository $repository, PropertyMapper $mapper, LoggerInterface $log)
    {
        $this->log = $log;
        $this->groupRepository = $repository;
        $this->mapper = $mapper;
    }

    public function createShopwareGroupsAndOptions(InnocigsArticle $article) {
        $icVariants = $article->getVariants();
        $this->log->info(sprintf('%s: Creating configurator groups and options for InnoCigs Article %s',
            __FUNCTION__,
            $article->getCode()
        ));
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
                $swOption = $this->groupRepository->createOption($icGroupName, $icOptionName);
                $icVariant->addShopwareOption($swOption);
            }
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->groupRepository->flush();
    }

    public function createConfiguratorSet(InnocigsArticle $icArticle, Article $swArticle)
    {
        $variants = $icArticle->getVariants();
        if (count($variants) < 2) {
            $this->log->info(sprintf('%s: No Shopware configurator set required. InnoCigs article %s does not provide variants.',
                __FUNCTION__,
                $icArticle->getCode()
            ));
            return null;
        }
        $setRepository = new SetRepository();
        $setName = 'mxc-set-' . $this->mapper->mapArticleCode($icArticle->getCode());
        $setRepository->initSet($setName);

        $this->log->info(sprintf('%s: Setup of configurator %s set for InnoCigs Article %s',
            __FUNCTION__,
            $setName,
            $icArticle->getCode()
        ));

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