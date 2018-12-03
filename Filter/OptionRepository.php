<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 12.11.2018
 * Time: 14:36
 */

namespace MxcDropshipInnocigs\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;

class OptionRepository
{
    protected $data;
    protected $log;

    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->log = $log;
        $this->modelManager = $modelManager;
        $this->createLookupTable();
    }

    protected function createLookupTable()
    {
        $dql = sprintf('SELECT o.name oName, v.value vName FROM %s o JOIN %s v WHERE v.option = o.id',
            Option::class,
        Value::class
        );
        $array = $this->modelManager->createQuery($dql)->getScalarResult();
        $this->data = [];
        foreach ($array as $entry) {
            $this->data[$entry['oName']]['option'] = true;
            $this->data[$entry['oName']]['values'][$entry['vName']] = true;
        }
    }

    public function createOption(string $name) {
        $option = $this->getOption($name);
        if ($option instanceof Option) {
            $this->log->info(sprintf('%s: Returning existing Shopware filter option %s.',
                __FUNCTION__,
                $name
            ));
            return $option;
        }

        $this->log->info(sprintf('%s: Creating shopware filter option %s',
            __FUNCTION__,
            $name
        ));
        $option = new Option();
        $this->modelManager->persist($option);

        $option->setName($name);
        $option->setFilterable(true);
        $this->data[$name]['option'] = $option;
        $this->data[$name]['values'] = [];
        return $option;
    }

    public function getOption(string $name) {
        $option = $this->data[$name]['option'] ?? null;
        if ($option  === true) {
            $option = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $name]);
            $this->data[$name]['option'] = $option;
            $this->modelManager->persist($option);
        }
        return $option;
    }

    public function getValue(string $optionName, string $valueName) {
        $value = $this->data[$optionName]['values'][$valueName] ?? null;
        $optionId = $this->getOption($optionName)->getId();
        if ($value === true) {
            $dql = sprintf("SELECT o FROM %s v JOIN %s o WHERE v.option = %s AND v.value = '%s'",
                Value::class,
                Option::class,
                 $optionId,
                 $valueName);
            $value = $this->modelManager->createQuery($dql)->getResult()[0];
            $this->data[$optionName]['values'][$valueName] = $value;
        }
        return $value;
    }

    public function createValue(string $optionName, string $valueName) {

        // we do not create a value if we do not know the option
        $option = $this->getOption($optionName);
        if (null === $option) return null;

        // if we know the option already return it
        $value = $this->getValue($optionName, $valueName);
        if ($value instanceof Value) {
            $this->log->info(sprintf('%s: Returning existing option %s of Shopware property %s.',
                __FUNCTION__,
                $valueName,
                $optionName
            ));
            return $option;
        }

        $this->log->info(sprintf('%s: Creating option %s for Shopware property %s.',
            __FUNCTION__,
            $valueName,
            $optionName
        ));

        // create new value
        $value = new Value($option, $valueName);
        $this->modelManager->persist($value);
        /**
         * @var ArrayCollection $options
         */
        $options = $option->getValues();
        $options->add($value);

        $value->setPosition(count($this->data[$optionName]['values']));
        $this->data[$optionName]['values'][$valueName] = $value;
        return $value;
    }

    public function deleteOption(string $optionName) {
        // cascade remove does not work because the shopware doctrine config is incomplete
        $option = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $optionName]);
        if ($option) {
            // delete the values
            $dql = sprintf( "DELETE %s value WHERE value.option = %s",
                Value::class,
                $option->getId()
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();

            // delete the option
            $dql = sprintf( "DELETE %s option WHERE option.name = '%s'",
                Option::class,
                $optionName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }

    public function deleteValue(string $optionName, string $valueName) {
        $option = $this->modelManager->getRepository(Option::class)->findOneBy(['name' => $optionName]);
        if ($option) {
            $dql = sprintf("DELETE %s value WHERE value.option = %s AND value.value = '%s'",
                Value::class,
                $option->getId(),
                $valueName
            );
            $query = $this->modelManager->createQuery($dql);
            $query->execute();
        }
    }
    public function flush() {
        $this->modelManager->flush();
    }

}