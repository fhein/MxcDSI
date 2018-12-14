<?php

namespace MxcDropshipInnocigs\Toolbox\Configurator;

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
        $dql = sprintf('SELECT g.name gName, o.name oName FROM %s g JOIN %s o WHERE o.group = g.id',
            Group::class,
        Option::class
        );
        $array = $this->modelManager->createQuery($dql)->getScalarResult();
        $this->data = [];
        foreach ($array as $entry) {
            $this->data[$entry['gName']]['group'] = true;
            $this->data[$entry['gName']]['options'][$entry['oName']] = true;
        }
    }

    public function createGroup(string $name) : Group {
        $group = $this->getGroup($name);
        if ($group instanceof Group) {
            $this->log->notice(sprintf('%s: Returning existing Shopware configurator group %s.',
                __FUNCTION__,
                $name
            ));
            return $group;
        }

        $this->log->notice(sprintf('%s: Creating shopware group %s',
            __FUNCTION__,
            $name
        ));
        $group = new Group();
        $this->modelManager->persist($group);

        $group->setName($name);
        $group->setPosition(count($this->data));
        $this->data[$name]['group'] = $group;
        return $group;
    }

    public function getGroup(string $name) : ?Group {
        $group = $this->data[$name]['group'] ?? null;
        if ($group  === true) {
            $group = $this->modelManager->getRepository(Group::class)->findOneBy(['name' => $name]);
            $this->data[$name]['group'] = $group;
            $this->modelManager->persist($group);
        }
        return $group;
    }

    public function getOption(string $groupName, string $optionName) : ?Option {
        $option = $this->data[$groupName]['options'][$optionName] ?? null;
        if ($option === true) {
            $dql = sprintf("SELECT o FROM %s o JOIN %s g WHERE o.group = %s AND o.name = '%s'",
                Option::class,
                Group::class,
                 $this->getGroup($groupName)->getId(),
                 $optionName);
            $option = $this->modelManager->createQuery($dql)->getResult()[0];
            $this->data[$groupName]['options'][$optionName] = $option;
        }
        return $option;
    }

    public function createOption(string $groupName, string $optionName) : ?Option {
        // we do not create an option if we do not know the group
        $group = $this->getGroup($groupName);
        if (null === $group) return null;

        // if we know the option already return it
        $option = $this->getOption($groupName, $optionName);
        if ($option instanceof Option) {
            $this->log->notice(sprintf('%s: Returning existing Shopware configurator option %s of group %s.',
                __FUNCTION__,
                $optionName,
                $groupName
            ));
            return $option;
        }

        $this->log->notice(sprintf('%s: Creating option %s for group %s.',
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
        $this->data[$groupName]['options'][$optionName] = $option;
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

    public function flush() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

}