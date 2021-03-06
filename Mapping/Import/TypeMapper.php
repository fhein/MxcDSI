<?php

namespace MxcDropshipIntegrator\Mapping\Import;

use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Product;

class TypeMapper extends BaseImportMapper implements ProductMapperInterface
{
    // Product type constants
    const TYPE_UNKNOWN              = 0;
    const TYPE_E_CIGARETTE          = 1;
    const TYPE_BOX_MOD              = 2;
    const TYPE_E_PIPE               = 3;
    const TYPE_LIQUID               = 4;
    const TYPE_AROMA                = 5;
    const TYPE_SHAKE_VAPE           = 6;
    const TYPE_HEAD                 = 7;
    const TYPE_TANK                 = 8;
    const TYPE_SEAL                 = 9;
    const TYPE_DRIP_TIP             = 10;
    const TYPE_POD                  = 11;
    const TYPE_CARTRIDGE            = 12;
    const TYPE_CELL                 = 13;
    const TYPE_CELL_BOX             = 14;
    const TYPE_BASE                 = 15;
    const TYPE_CHARGER              = 16;
    const TYPE_BAG                  = 17;
    const TYPE_TOOL                 = 18;
    const TYPE_WADDING              = 19; // Watte
    const TYPE_WIRE                 = 20;
    const TYPE_CLEAROMIZER_RDA      = 21;
    const TYPE_SQUONKER_BOTTLE      = 22;
    const TYPE_VAPORIZER            = 23;
    const TYPE_SHOT                 = 24;
    const TYPE_CABLE                = 25;
    const TYPE_BOX_MOD_CELL         = 26;
    const TYPE_COIL                 = 27;
    const TYPE_RDA_BASE             = 28;
    const TYPE_MAGNET               = 29;
    const TYPE_MAGNET_ADAPTOR       = 30;
    const TYPE_ACCESSORY            = 31;
    const TYPE_BATTERY_CAP          = 32;
    const TYPE_EXTENSION_KIT        = 33;
    const TYPE_CONVERSION_KIT       = 34;
    const TYPE_CLEAROMIZER          = 35;
    const TYPE_CLEAROMIZER_RTA      = 36;
    const TYPE_CLEAROMIZER_RDTA     = 37;
    const TYPE_CLEAROMIZER_ADA      = 38;
    const TYPE_E_HOOKAH             = 39;
    const TYPE_SQUONKER_BOX         = 40;
    const TYPE_EMPTY_BOTTLE         = 41;
    const TYPE_EASY3_CAP            = 42;
    const TYPE_DECK                 = 43;
    const TYPE_TOOL_HEATING_PLATE   = 44;
    const TYPE_HEATING_PLATE        = 45;
    const TYPE_DRIP_TIP_CAP         = 46;
    const TYPE_TANK_PROTECTION      = 47;
    const TYPE_STORAGE              = 48;
    const TYPE_BATTERY_SLEEVE       = 49;
    const TYPE_CLEANING_SUPPLY      = 50;
    const TYPE_LIQUID_BOX           = 51;
    const TYPE_COVER                = 52;
    const TYPE_DISPLAY              = 53;
    const TYPE_SPARE_PARTS          = 54;
    const TYPE_POD_SYSTEM           = 55;
    const TYPE_NICSALT_LIQUID       = 56;
    const TYPE_PHOTO_SAMPLE         = 57;
    const TYPE_OTHER_EQUIPMENT      = 58;
    const TYPE_BOOK                 = 59;
    const TYPE_EASY4_CAP            = 60;

    protected $types;

    /**
     * Derive the type of an article. This is done via the
     * 'name_type_mapping' configuration.
     *
     * @param Model $model
     * @param Product $product
     * @param bool $remap
     */
    public function map(Model $model, Product $product, bool $remap = false)
    {
        $name = $product->getName();
        $types = $this->classConfig['name_type_mapping'] ?? [];
        $type = '';
        foreach ($types as $pattern => $type) {
            if (preg_match($pattern, $name) === 1) {
                $type = $this->classConfig['types'][$type];
                break;
            }
        }
        // check if e-cigarette is a pod-system
        // @todo: Quickhack for Argus GT with pod style clearomizer
        // @todo: Quickhack für Drag 3 & Drag X Plus with pod style clearomizer
        if ($type === 'E_CIGARETTE') {
            if (strpos($name, 'Argus GT') === false
                && strpos($name, 'Drag 3') === false
                && strpos($name, 'Drag X Plus') === false
                && strpos($name, 'iStick Power 2C') === false
                && strpos($name, 'Grus') === false
            ) {
                $description = $product->getIcDescription();
                if (strpos($description, 'Pod') !== false
                    || strpos($description, 'Cartridge') !== false) {
                    $type = 'POD_SYSTEM';
                }
            }
        }

        $product->setType($type);
    }

    public function getType(string $strType)
    {
        if (!$this->types) {
            $this->types = array_flip($this->classConfig['types']);
        }
        return $this->types[$strType] ?? null;
    }

    public function report()
    {
        // add reporting here
    }
}