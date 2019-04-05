<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware;

use Shopware\Models\Article\Unit;

class UnitTool
{
    /**
     * Returns a Unit object for a given name. If the Unit Object is not available
     * it will be created.
     *
     * @param string $name
     * @return object|Unit|null
     */
    public static function getUnit(string $name)
    {
        $modelManager = Shopware()->Models();
        $unit = $modelManager->getRepository(Unit::class)->findOneBy(['name' => $name]);
        if (!$unit instanceof Unit) {
            $unit = new Unit();
            $modelManager->persist($unit);
            $unit->setName($name);
        }
        return $unit;
    }
}