<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class DescriptionMapper implements ProductMapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    private $mappings;

    protected $baseLiquidDefault = [
        'tag' => 'Basis (PG/VG)',
        'base' => '50:50',
    ];

    protected $baseLiquid = [
        'SC' => [
            'tag' => 'Basis (PG/VG/Wasser)',
            'base' => '50:38:12'
        ],
    ];

    protected $descriptionShakeVape = [
        'Pink Spot' =>
            '<p><br>Shake & Vape Liquid. Geschmack: ##flavor##. Die 60 ml Flasche enthält 50 ml überaromatisiertes, nikotinfreies E-Liquid. '
            . 'Füllen Sie die Flasche einfach mit einem 10 ml Nikotin-Shot oder 10 ml Basis auf. Anschließend gut schütteln, und Ihr Liquid ist fertig zum Gebrauch.</p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>50 ml überaromatisiertes E-Liquid in 60ml Flasche </td>'
            . '</tr>'
            . '<tr>'
            . '<td>Geschmack </td>'
            . '<td>##flavor##</td>'
            . '</tr>'
            . '<tr>'
                . '<td>Basis (PG/VG)<sup>1</sup></td>'
                . '<td>40:60</td>'
            . '</tr>'
            . '<tr>'
            . '<td>Inhaltsstoffe<sup>1</sup></td>'
            . '<td>PG, VG, Aromen</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>',
        'Koncept XIX' =>
            '<p><br>Shake & Vape Liquid. Geschmack: ##flavor##. Die 60 ml Flasche enthält 50 ml überaromatisiertes, nikotinfreies E-Liquid. '
            . 'Füllen Sie die Flasche einfach mit einem 10 ml Nikotin-Shot oder 10 ml Basis auf. Anschließend gut schütteln, und Ihr Liquid ist fertig zum Gebrauch.</p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>50 ml überaromatisiertes E-Liquid in 60ml Flasche </td>'
            . '</tr>'
            . '<tr>'
            . '<td>Geschmack </td>'
            . '<td>##flavor##</td>'
            . '</tr>'
            . '<tr>'
                . '<td>Basis (PG/VG)<sup>1</sup></td>'
                . '<td>20:80</td>'
            . '</tr>'
            . '<tr>'
            . '<td>Inhaltsstoffe<sup>1</sup></td>'
            . '<td>PG, VG, Aromen</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>'
    ];

    protected $descriptionLiquidDefault =
        '<p><br>Gebrauchsfertiges Liquid für die E-Zigarette. Geschmack: ##flavor##. Einfach in den Verdampfer der E-Zigarette einfüllen und losdampfen!</p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
                . '<tr>'
                    . '<td>Produkt</td>'
                    . '<td>Gebrauchsfertiges E-Liquid, 10ml Flasche </td>'
                . '</tr>'
                . '<tr>'
                    . '<td>Geschmack </td>'
                    . '<td>##flavor##</td>'
                . '</tr>'
//                . '<tr>'
//                    . '<td>##baseTag##<sup>1</sup>&nbsp;&nbsp;</td>'
//                    . '<td>##base##</td>'
//                . '</tr>'
                . '<tr>'
                    . '<td>Inhaltsstoffe<sup>1</sup></td>'
                    . '<td>PG, VG, Aromen, Nikotin nach Wahl</td>'
                . '</tr>'
            .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>';

    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function map(Model $model, Product $product)
    {
        $this->log->debug('Mapping description for ' . $product->getName());
        $description = $this->mappings[$product->getIcNumber()]['description'] ?? null;
        $type = $product->getType();
        $brand = $product->getBrand();
        $flavor = $product->getFlavor();
        $name = $product->getName();
        if ($type === 'LIQUID') {
//            $base = $this->baseLiquid[$product->getBrand()] ?? $this->baseLiquidDefault;
            $description = $this->descriptionLiquidDefault;
            $description = str_replace('##flavor##', $flavor, $description);
//            $description = str_replace('##baseTag##', $base['tag'], $description);
//            $description = str_replace('##base##', $base['base'], $description);
        } elseif ($type === 'SHAKE_VAPE') {
            if (is_int(strpos($name, 'Koncept XIX'))) {
                $description = $this->descriptionShakeVape['Koncept XIX'] ?? null;
            } else {
                $description = $this->descriptionShakeVape[$brand] ?? null;
            }
            $description = str_replace('##flavor##', $flavor, $description);
        }
        if (! $description) {
            $description = $model->getDescription();
        }
        $product->setDescription($description);
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}
