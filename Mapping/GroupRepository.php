<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 12.11.2018
 * Time: 14:36
 */

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Convenience\ModelManagerTrait;
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
        $group = Group::class;
        $option = Option::class;
        $dql = "SELECT g.name gName, o.name oName FROM $group g JOIN $option o WHERE o.group = g.id";
        $array = $this->createQuery($dql)->getScalarResult();
        $this->data = [];
        foreach ($array as $entry) {
            $this->data[$entry['gName']]['group'] = true;
            $this->data[$entry['gName']]['options'][$entry['oName']] = true;
        }
    }

    public function createGroup(string $name, int $pos = null) {
        $group = new Group();
        $group->setName($name);
        $group->setPosition($pos ?? count($this->data));
        $this->data[$name]['group'] = $group;
        $this->persist($group);
        return $group;
    }

    public function createOption(Group $group, string $name, int $pos = null) {
        $option = new Option();
        $option->setName($name);
        $option->setGroup($group);
        $option->setPosition($pos ?? count($this->data));
        $this->data[$group->getName()]['options'][$name] = $option;
        $this->persist($option);
        return $option;
    }

    public function loadGroup(string $name)
    {
        if (!$this->hasGroup($name)) return null;
        $repository = $this->getRepository(Group::class);
        $group = $repository->findOneBy(['name' => $name]);
        if (isset($group)) {
            $this->data[$name]['group'] = $group;
            $options = $group->getOptions();
            foreach ($options as $option) {
                /**
                 * @var Option $option
                 */
                $this->data[$name]['options'][$option->getName()] = $option;
            }
    }
        return $group;
    }

    public function getGroup(string $name) {
        if (! $this->data[$name]) return null;
        $group = $this->data[$name]['group'];
        if (! $group instanceof Group) {
            $group = $this->getRepository(Group::class)->findOneBy(['name' => $name]);
            $this->data[$name]['group'] = $group;
        }
        return $group;
    }

    public function getOption(Group $group, string $name) {
        $groupName = $group->getName();
        // short cut retrieval (works if everything is fine)
        $option = $this->data[$groupName]['options'][$name];
        if ($option instanceof Option) {
            return $option;
        }

        // We do not have the requested option, try to recover
        // If the next line returns the group is not managed by us
        if (! isset($this->data[$groupName])) return null;
        // Check if we just know about the group or if it is loaded already
        if ($this->data[$groupName]['group'] === true) {
            // group exists but is not loaded
            $this->loadGroup($groupName);
        } else {
            // check if the group we store and group given are the same object
            if ($this->data[$groupName]['group'] !== $group ) {
                // we have an unexpected inconsistency
                return null;
            }
        }
        // Here the group and all it's options are cached
        // Check if the requested option is available
        if (! isset($this->data[$groupName]['options'][$name])) return null;

        // here we know the option exists and the cache holds it
        $option = $this->data[$name]['options'][$name];
        return $option;
    }

    public function hasGroup(string $name) {
        return isset($this->data[$name]);
    }

    public function hasOption(Group $group, string $name) {
        return isset($this->data[$group->getName()]['options'][$name]);
    }


}