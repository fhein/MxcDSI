<?php

namespace MxcDropshipInnocigs\Mapping;


use MxcDropshipInnocigs\Application\Application;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Shopware\Models\Article\Configurator\Set;
use Zend\Log\Logger;

class SetRepository
{
    use ModelManagerTrait;

    /**
     * @var Set $set
     */
    private $set;

    /**
     * @var bool $isNew
     */
    private $isNew = false;

    /**
     * @var array $options
     */
    private $options;

    /**
     * @var array $groups
     */
    private $groups;

    /**
     * @var Logger $log
     */
    private $log;

    public function __construct() {
        $this->log = Application::getServices()->get('logger');
    }

    protected function createSet(string $name) {
        $set = new Set();
        $set->setName($name);
        $this->isNew = true;
        return $set;
    }

    public function initSet(string $name) {
        $setRepo = $this->getRepository(Set::class);
        /**
         * @var Set $set
         */
        $set = $setRepo->findOneBy(['name' => $name]);
        if ($set === null) {
            $set = $this->createSet($name);
        } else {
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
        $this->persist($set);
        $this->set = $set;
    }

    public function addOption(Option $option) {
        $group = $option->getGroup();
        $this->groups[$group->getName()] = $group;
        $this->options[$option->getName()] = $option;
    }

    public function prepareSet(Article $article) {
        if ($this->isNew) {
            $this->set->setPublic(false);
            $this->set->setType(0);
        }

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
        return $this->set;
    }
}