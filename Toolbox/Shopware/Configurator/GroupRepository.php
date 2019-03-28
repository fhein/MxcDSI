<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Configurator;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;

class GroupRepository
{
    /**
     * @var array $data
     */
    protected $data;
    /**
     * @var LoggerInterface $log
     */
    protected $log;
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->createLookupTable();
    }

    protected function createLookupTable()
    {
        $dql = sprintf ('SELECT g FROM %s g', Group::class);
        $groups = $this->modelManager->createQuery($dql)->getResult();
        /** @var Group $group */
        foreach ($groups as $group) {
            $groupName = strtolower($group->getName());
            $this->data[$groupName]['group'] = $group;
            $options = $group->getOptions();
            foreach($options as $option) {
                $this->data[$groupName]['options'][strtolower($option->getName())] = $option;
            }
        }
    }

    public function createGroup(string $groupName) : Group {
        $group = $this->data[strtolower($groupName)]['group'];

        if ($group instanceof Group) {
            $this->log->notice(sprintf('%s: Using existing Shopware configurator group %s.',
                __FUNCTION__,
                $group->getName()
            ));
            return $group;
        }

        $this->log->notice(sprintf('%s: Creating Shopware configurator group %s',
            __FUNCTION__,
            $groupName
        ));
        $group = new Group();
        $this->modelManager->persist($group);

        $group->setName($groupName);
        $group->setPosition(count($this->data));
        $this->data[strtolower($groupName)]['group'] = $group;
        return $group;
    }

    public function createOption(string $groupName, string $optionName) : ?Option {
        // we do not create an option if we do not know the group
        /** @var Group $group */
        $group = $this->data[strtolower($groupName)]['group'];
        if (null === $group) return null;

        // if we know the option already return it
        $option = $this->data[strtolower($groupName)]['options'][strtolower($optionName)];
        if ($option instanceof Option) {
            $this->log->notice(sprintf('%s: Using existing Shopware configurator option %s of group %s.',
                __FUNCTION__,
                $optionName,
                $groupName
            ));
            return $option;
        }

        $this->log->notice(sprintf('%s: Creating Shopware configurator option %s for group %s.',
            __FUNCTION__,
            $optionName,
            $groupName
        ));

        // create new option
        $option = new Option();
        $option->setName($optionName);
        $option->setGroup($group);
        /**
         * @var ArrayCollection $options
         */
        $options = $group->getOptions();
        $options->add($option);
        $group->setOptions($options);

        $option->setPosition(count($this->data[$groupName]['options']));
        $this->data[strtolower($groupName)]['options'][strtolower($optionName)] = $option;
        return $option;
    }

    public function deleteGroup(string $groupName) {
        // cascade remove does not work because the shopware doctrine config is incomplete
        $group = $this->modelManager->getRepository(Group::class)->findOneBy(['name' => $groupName]);
        if ($group) {
            // delete the options
            $dql = sprintf( "DELETE %s option WHERE option.group = %s",
                Option::class,
                $group->getId()
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();

            // delete the group
            $dql = sprintf( "DELETE %s group WHERE group.name = '%s'",
                Group::class,
                $groupName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }

    public function deleteOption(string $groupName, string $optionName) {
        $group = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $groupName]);
        if ($group) {
            $dql = sprintf("DELETE %s option WHERE option.group = %s AND option.name = '%s'",
                Option::class,
                $group->getId(),
                $optionName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }

    protected function sortGroupOptions(array $groups, int $sortFlags)
    {
        foreach ($groups as $group) {
            $options = $group->getOptions();
            $array = [];
            /** @var Option $option */
            foreach ($options as $option) {
                $array[$option->getName()] = $option;
            }
            $keys = array_keys($array);
            sort($keys, $sortFlags);
            $pos = 1;
            foreach ($keys as $key) {
                $array[$key]->setPosition($pos++);
            }
        }
    }

    public function sortOptions(string $group, int $sortFlags = SORT_NATURAL)
    {
        $groups = $this->modelManager->getRepository(Group::class)->findBy(['name' => $group]);
        $this->sortGroupOptions($groups, $sortFlags);
    }

    public function sortAllOptions(int $sortFlags = SORT_NATURAL)
    {
        $groups = $this->modelManager->getRepository(Group::class)->findAll();
        $this->sortGroupOptions($groups, $sortFlags);
    }
}