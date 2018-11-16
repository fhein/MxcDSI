<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 12.11.2018
 * Time: 14:36
 */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Exception\DatabaseException;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;
use Zend\Log\Logger;

class GroupRepository
{
    use ModelManagerTrait;

    private $data;
    private $log;

    public function __construct(Logger $log) {
        $this->log = $log;
        $this->createLookupTable();
    }

    //    [
    //        <group name> => [
    //            'group' => true|Group
    //            'options' => [
    //                <option name> => true|Option,
    //                ...
    //            ],
    //        ],
    //        ...
    //    ]
    private function createLookupTable()
    {
        $dql = sprintf('SELECT g.name gName, o.name oName FROM %s g JOIN %s o WHERE o.group = g.id',
            Group::class,
        Option::class
        );
        $array = $this->createQuery($dql)->getScalarResult();
        $this->data = [];
        foreach ($array as $entry) {
            $this->data[$entry['gName']]['group'] = true;
            $this->data[$entry['gName']]['options'][$entry['oName']] = true;
        }
        $this->log->info('Group repository lookup table: ' . PHP_EOL . var_export($this->data, true));
    }

    public function createGroup(string $name) {
        $group = $this->getGroup($name);
        if ($group instanceof Group) return $group;

        $this->log->info('Creating shopware group ' . $name);
        $group = new Group();
        $group->setName($name);

        $group->setPosition(count($this->data));
        $this->data[$name]['group'] = $group;
        $this->persist($group);
        $this->log->info('Created group: ' . $name);
        return $group;
    }

    public function getGroup(string $name) {
        $group = $this->data[$name]['group'] ?? null;
        if ($group  === true) {
            $group = $this->getRepository(Group::class)->findOneBy(['name' => $name]);
            $this->data[$name]['group'] = $group;
            $this->persist($group);
        }
        return $group;
    }

    public function getOption(string $groupName, string $optionName) {
        $option = $this->data[$groupName]['options'][$optionName] ?? null;
        $groupId = $this->getGroup($groupName)->getId();
        if ($option === true) {
            $this->log->info(__FUNCTION__ . ': Retrieving option ' . $optionName . ' for group ' . $groupName);
            $dql = sprintf("SELECT o FROM %s o JOIN %s g WHERE o.group = %s",
                Option::class,
                Group::class,
                $groupId);
            $option = $this->createQuery($dql)->getResult()[0];
            $this->data[$groupName]['options'][$optionName] = $option;
        }
        return $option;
    }

    public function createOption(string $groupName, string $optionName) {

        // we do not create an option if we do not know the group
        $this->log->info(__FUNCTION__ . ': Creating option ' . $optionName . ' for group ' . $groupName);
        $group = $this->getGroup($groupName);
        if (null === $group) return null;

        // if we know the option already return it
        $option = $this->getOption($groupName, $optionName);
        if ($option instanceof Option) return $option;

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
        $this->log->info(__FUNCTION__ . ': Option ' . $optionName . ' for group ' . $groupName . ' created.');
        return $option;
    }

    public function commit() {
        $this->flush();
    }
}