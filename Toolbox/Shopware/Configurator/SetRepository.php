<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Configurator;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;

class SetRepository
{
    /**
     * @var Set $set
     */
    private $set;

     /**
     * @var array $options
     */
    private $options;

    /**
     * @var array $groups
     */
    private $groups;

    /**
     * @var LoggerInterface $log
     */
    private $log;
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    protected function createSet(string $name) {
        $set = new Set();
        $this->modelManager->persist($set);
        $set->setName($name);
        $set->setPublic(false);
        $set->setType(0);
        return $set;
    }

    public function getSet(string $name) {
        $setRepo = $this->modelManager->getRepository(Set::class);
        /**
         * @var Set $set
         */
        $set = $setRepo->findOneBy(['name' => $name]);
        if ($set === null) {
            $this->log->debug(sprintf('%s: Creating new configurator set %s.',
                __FUNCTION__,
                $name));
            $set = $this->createSet($name);
        } else {
            $this->log->debug(sprintf('%s: Resetting existing configurator set %s.',
                __FUNCTION__,
                $name));
            // discard group and option and article links of existing set
            $set->getOptions()->clear();
            $set->getGroups()->clear();
            $set->getArticles()->clear();
        }
        $this->set = $set;
        $this->groups = [];
        $this->options = [];
        return $this->set;
    }

    public function addOption(Option $option) {
        $group = $option->getGroup();
        $groupName = $group->getName();
        $optionName = $option->getName();
        $setName = $this->set->getName();

        if (! isset($this->groups[$groupName])) {
            $this->log->info(sprintf('%s: Adding group %s to set %s.',
                __FUNCTION__,
                $groupName,
                $setName
            ));
            $this->groups[$groupName] = $group;
            $this->set->getGroups()->add($group);

            // important to avoid 'not configured for cascade persist
            $this->modelManager->persist($group);
        }

        if (! isset($this->options[$optionName])) {
            $this->log->info(sprintf('%s: Adding option %s to set %s.',
                __FUNCTION__,
                $optionName,
                $setName
            ));
            $this->options[$optionName] = $option;
            $this->set->getOptions()->add($option);

            // important to avoid 'not configured for cascade persist
            $this->modelManager->persist($option);
        }
    }
}