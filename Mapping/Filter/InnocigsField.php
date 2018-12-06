<?php

namespace MxcDropshipInnocigs\Mapping\Filter;

use MxcDropshipInnocigs\Zends\Filter\ClassFilter;

class InnocigsField extends InnocigsModel
{
    protected $fields;

    public function apply(&$object) {
        $object = parent::filter($object);
        foreach ($this->fields as $fieldName => $settings) {
            $getter = 'get' . ucFirst($fieldName);
            if (! method_exists($object, $getter)) {
                throw new InvalidArgumentException(
                    sprintf('%s: Object does not implement a getter (%s) for property %s.',
                        __METHOD__,
                        $getter,
                        $fieldName
                    )
                );
            }
            $value = $object->$getter();
            $filter = $settings['filter'];
            if ($filter($value)) {
                switch($settings['action']) {
                    case 'accept' :
                        $ignored = false;
                        break;
                    case 'ignore':
                        $ignored = true;
                        break;
                    default:
                        $ignored = false;
                        break;
                }
                $object->setIgnored($ignored);
                break;
            }

        }
        return true;
    }
}