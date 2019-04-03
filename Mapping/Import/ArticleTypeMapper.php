<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Model;

class ArticleTypeMapper extends BaseImportMapper implements ArticleMapperInterface
{
    // Article type constants
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
    const TYPE_BOTTLE               = 21;
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
    const TYPE_CLEAROMIZER_RDSA     = 38;
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

    /**
     * Derive the type of an article. This is done via the
     * 'name_type_mapping' configuration.
     *
     * @param Model $model
     * @param Article $article
     */
    public function map(Model $model, Article $article)
    {
        $name = $article->getName();
        $types = $this->config['name_type_mapping'];
        foreach ($types as $pattern => $type) {
            if (preg_match($pattern, $name) === 1) {
                $article->setType($this->config['types'][$type]);
                return;
            }
        }
        $article->setType('');
    }
}