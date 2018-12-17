<?php

namespace MxcDropshipInnocigs\Toolbox\Configurator;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
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
        $set->setName($name);
        $set->setPublic(false);
        $set->setType(0);
        return $set;
    }

    public function initSet(string $name) {
        $setRepo = $this->modelManager->getRepository(Set::class);
        /**
         * @var Set $set
         */
        $set = $setRepo->findOneBy(['name' => $name]);
        if ($set === null) {
            $this->log->info(sprintf('%s: Creating new configurator set %s.',
                __FUNCTION__,
                $name));
            $set = $this->createSet($name);
        } else {
            $this->log->info(sprintf('%s: Using existing configurator set %s.',
                __FUNCTION__,
                $name));
            // prepare lookup tables groups and options of existing set
            $options = $set->getOptions()->toArray();
            foreach($options as $option) {
                /**
                 * @var Option $option
                 */
                $this->options[$option->getName()] = $option;
            }
            $groups = $set->getGroups()->toArray();
            foreach ($groups as $group) {
                /**
                 * @var Group $group
                 */
                $this->groups[$group->getName()] = $group;
            }
        }
        $this->modelManager->persist($set);
        $this->set = $set;
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
        }

        if (! isset($this->options[$optionName])) {
            $this->log->info(sprintf('%s: Adding option %s to set %s.',
                __FUNCTION__,
                $optionName,
                $setName
            ));
            $this->options[$optionName] = $option;
        }
    }

    public function prepareSet(Article $article) {

        $this->log->enter();
        $this->set->getArticles()->add($article);

        // objects are returned by reference
        $setGroups = $this->set->getGroups();
        $setGroups->clear();
        // Note: $this->set->setGroups(new ArrayCollection($this->groups)) does not work. Why??
        foreach ($this->groups as $group) {
            $setGroups->add($group);
        }

        // objects are returned by reference
        $setOptions = $this->set->getOptions();
        $setOptions->clear();
        // Note: $this->set->setOptions(new ArrayCollection($this->options)) does not work. Why??
        foreach ($this->options as $option) {
            $setOptions->add($option);
        }
        $this->log->leave();
        return $this->set;
    }
}