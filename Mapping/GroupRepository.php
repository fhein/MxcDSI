<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 12.11.2018
 * Time: 14:36
 */

namespace MxcDropshipInnocigs\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use MxcDropshipInnocigs\Plugin\Convenience\ModelManagerTrait;
use MxcDropshipInnocigs\Plugin\Service\LoggerInterface;
use Shopware\Models\Article\Configurator\Group;
use Shopware\Models\Article\Configurator\Option;

class GroupRepository
{
    use ModelManagerTrait;

    protected $data;
    protected $log;

    public function __construct(LoggerInterface $log) {
        $this->log = $log;
        $this->createLookupTable();
    }

    protected function createLookupTable()
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
    }

    public function createGroup(string $name) {
        $group = $this->getGroup($name);
        if ($group instanceof Group) {
            $this->log->info(sprintf('%s: Returning existing Shopware configurator group %s.',
                __FUNCTION__,
                $name
            ));
            return $group;
        }

        $this->log->info(sprintf('%s: Creating shopware group %s',
            __FUNCTION__,
            $name
        ));
        $group = new Group();
        $this->persist($group);

        $group->setName($name);
        $group->setPosition(count($this->data));
        $this->data[$name]['group'] = $group;
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
            $dql = sprintf("SELECT o FROM %s o JOIN %s g WHERE o.group = %s AND o.name = '%s'",
                Option::class,
                Group::class,
                 $groupId,
                 $optionName);
            $option = $this->createQuery($dql)->getResult()[0];
            $this->data[$groupName]['options'][$optionName] = $option;
        }
        return $option;
    }

    public function createOption(string $groupName, string $optionName) {

        // we do not create an option if we do not know the group
        $group = $this->getGroup($groupName);
        if (null === $group) return null;

        // if we know the option already return it
        $option = $this->getOption($groupName, $optionName);
        if ($option instanceof Option) {
            $this->log->info(sprintf('%s: Returning existing Shopware configurator option %s of group %s.',
                __FUNCTION__,
                $optionName,
                $groupName
            ));
            return $option;
        }

        $this->log->info(sprintf('%s: Creating option %s for group %s.',
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
}