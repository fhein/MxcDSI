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

    protected $descriptionAromaDefault =
        '<p><br>Aroma zur Herstellung von E-Liquid. Geschmack: ##flavor##. Geben Sie das Aroma entsprechend der Dosierempfehlung '
        . 'des Herstellers einer nikotinfreien oder nikotinhaltigen Basis zu und mischen Sie das Ergebnis gut durch. Das fertige '
        . 'Liquid sollte nun vor der Verwendung noch einige Tage reifen, damit sich die Aromastoffe entfalten können. In der Regel werden 1 - 5 Tage Reifezeit empfohlen.</p>'
        . '<p><strong>Aromen sind hochkonzentriert und dürfen keinesfalls pur gedampft werden.</strong></p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
        . '<tbody>'
        . '<tr>'
        . '<td>Produkt</td>'
        . '<td>##content## ml Aroma zur Herstellung von E-Liquids </td>'
        . '</tr>'
        . '<tr>'
        . '<td>Geschmack </td>'
        . '<td>##flavor##</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Dosierung</td>'
        . '<td>##dosage## %</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Inhaltsstoffe</td>'
        . '<td>Propylenglykol, Aromastoffe</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>';

    protected $descriptionAromaLongfill =
        '<p><br>Longfill-Aroma zur Herstellung von E-Liquid. Geschmack: ##flavor##. Die ##content## ml Aroma sind in eine ##capacity## ml Flasche abgefüllt.'
        . ' Füllen Sie die Flasche einfach mit einer nikotinfreien oder nikotinhaltigen Base bis oben auf. Verschließen Sie dann die Flasche und schütteln Sie '
        . ' durch, damit sich die Flüssigkeiten gut vermischen. Das fertige Liquid sollte nun vor der Verwendung noch einige Tage reifen, damit sich '
        . 'die Aromastoffe entfalten können. In der Regel werden 1 - 5 Tage Reifezeit empfohlen.</p>'
        . '<p><strong>Aromen sind hochkonzentriert und dürfen keinesfalls pur gedampft werden.</strong></p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
        . '<tbody>'
        . '<tr>'
        . '<td>Produkt</td>'
        . '<td>##content## ml Aroma in einer ##capacity## ml Flasche </td>'
        . '</tr>'
        . '<tr>'
        . '<td>Geschmack </td>'
        . '<td>##flavor##</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Dosierung</td>'
        . '<td>Flasche bis oben mit Basis auffüllen</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Inhaltsstoffe</td>'
        . '<td>Propylenglykol, Aromastoffe</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>';


    protected $descriptionShakeVapeDefault =
        '<p><br>Shake & Vape Liquid. Geschmack: ##flavor##. Die ##capacity## ml Flasche enthält ##content## ml überaromatisiertes, nikotinfreies E-Liquid. '
        . 'Füllen Sie die Flasche einfach mit ##fillup## ml Nikotin-Shot oder ##fillup## ml Basis auf. Anschließend gut schütteln, und Ihr Liquid ist fertig zum Gebrauch.</p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
        . '<tbody>'
        . '<tr>'
        . '<td>Produkt</td>'
        . '<td>##content## ml überaromatisiertes E-Liquid in ##capacity## Flasche </td>'
        . '</tr>'
        . '<tr>'
        . '<td>Geschmack </td>'
        . '<td>##flavor##</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Inhaltsstoffe<sup>1</sup></td>'
        . '<td>PG, VG, Aromen</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>';

    protected $descriptionShakeVapeTwoSizes =
        '<p><br>Shake & Vape Liquid. Geschmack: ##flavor##. Wählen Sie zwischen einer ##capacity1## ml Flasche mit ##content1## ml und einer '
        . '##capacity2## ml Flasche mit ##content2## ml überaromatisiertem, nikotinfreien Liquid aus. '
        . 'Füllen Sie die Flasche einfach mit ##fillup1## ml bzw. ##fillup2## ml Nikotin-Shot oder Basis auf. Anschließend gut schütteln, und Ihr Liquid ist fertig zum Gebrauch.</p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
        . '<tbody>'
        . '<tr>'
        . '<td>Produkt</td>'
        . '<td>Überaromatisiertes E-Liquid in Flasche zum Auffüllen</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Geschmack </td>'
        . '<td>##flavor##</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Inhaltsstoffe<sup>1</sup></td>'
        . '<td>PG, VG, Aromen</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>';

    protected $descriptionShakeVape = [
        'Chicken Shop' =>
            '<p><br>Shake & Vape Liquid. Geschmack: ##flavor##. Wählen Sie zwischen 25 ml und 200 ml überaromatisiertem, nikotinfreien Liquid aus.</p>'
            . '<p>Die Variante mit 25 ml Liquid erhalten in einer 30 ml Flasche. '
            . 'Füllen Sie diese Flasche einfach mit 5 ml Nikotin-Shot oder Basis auf. Anschließend gut schütteln, und Ihr Liquid ist fertig zum Gebrauch.</p>'
            .'<p>In der 200 ml Variante erhalten Sie neben der vollständig mit überaromatisiertem Liquid gefüllten 200 ml Flasche zwei 60 ml Leerflaschen zum '
            . 'Mischen Ihres Liquids. Füllen Sie dazu eine 60 ml Flasche mit 50 ml Liquid und 10 ml Nikotin-Shot oder Basis. '
            . 'Gut schütteln und Ihr Liquid ist fertig.</p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>Überaromatisiertes E-Liquid</td>'
            . '</tr>'
            . '<tr>'
            . '<td>Geschmack </td>'
            . '<td>##flavor##</td>'
            . '</tr>'
            . '<tr>'
            . '<td>Inhaltsstoffe<sup>1</sup></td>'
            . '<td>PG, VG, Aromen</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>',

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
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>',
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
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>'
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
                . '<tr>'
                    . '<td>Inhaltsstoffe<sup>1</sup></td>'
                    . '<td>PG, VG, Aromen, Nikotin nach Wahl</td>'
                . '</tr>'
            .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>';

    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    protected function getAromaDescription(Product $product)
    {
        $content = strval($product->getContent());
        $capacity = strval($product->getCapacity());
        $description = null;

        if ($content === $capacity) {
            $description = $this->descriptionAromaDefault;
            $description = str_replace('##flavor##', $product->getFlavor(), $description);
            $description = str_replace('##dosage##', $product->getDosage(), $description);
            $description = str_replace('##content##', $product->getContent(), $description);
        } else {
            $description = $this->descriptionAromaLongfill;
            $description = str_replace('##flavor##', $product->getFlavor(), $description);
            $description = str_replace('##content##', $product->getContent(), $description);
            $description = str_replace('##capacity##', $product->getCapacity(), $description);
        }

        return $description;
    }

    protected function getShakeVapeDescription(Product $product)
    {
        $name = $product->getName();
        if (is_int(strpos($name, 'Koncept XIX'))) {
            return $this->descriptionShakeVape['Koncept XIX'] ?? null;
        }
        $content = $product->getContent();
        $capacity = $product->getCapacity();

        if (! $content && ! $capacity) {
            return $this->descriptionShakeVape[$product->getBrand()] ?? null;
        }

        $content = explode(',', $content);
        $content = array_map('trim', $content);
        $capacity = explode(',', $capacity);
        $capacity = array_map('trim', $capacity);

        if (count($content) === 1 && count($capacity) === 1) {
            $description = $this->descriptionShakeVapeDefault;
            $fillup = $capacity[0] - $content[0];
            $description = str_replace('##fillup##', $fillup, $description);
            $description = str_replace('##content##', $content[0], $description);
            $description = str_replace('##capacity##', $capacity[0], $description);

            return $description;
        }

        if (count($content) === 2 && count($capacity) === 2) {
            $description = $this->descriptionShakeVapeTwoSizes;
            $fillup1 = $capacity[0] - $content[0];
            $fillup2 = $capacity[1] - $content[1];
            $description = str_replace('##fillup1##', $fillup1, $description);
            $description = str_replace('##fillup2##', $fillup2, $description);
            $description = str_replace('##content1##', $content[0], $description);
            $description = str_replace('##capacity1##', $capacity[0], $description);
            $description = str_replace('##content2##', $content[1], $description);
            $description = str_replace('##capacity2##', $capacity[1], $description);

            return $description;
        }

        return @$this->descriptionShakeVape[$product->getBrand()];
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $description = @$this->mappings[$product->getIcNumber()]['description'];
        if ($remap || ! $description) {
            $description = $this->remap($product);
        }
        $product->setDescription($description);
    }

    public function remap(Product $product)
    {
        $type = $product->getType();
        $flavor = $product->getFlavor();

        switch ($type) {
            case 'LIQUID':
                $description = $this->descriptionLiquidDefault;
                $description = str_replace('##flavor##', $flavor, $description);
                break;
            case 'SHAKE_VAPE':
                $description = $this->getShakeVapeDescription($product);
                $description = str_replace('##flavor##', $flavor, $description);
                break;
            case 'AROMA':
                $description = $this->getAromaDescription($product);
                break;
            default:
                $description = $this->mappings[$product->getIcNumber()]['description'] ?? $product->getIcDescription();
        }

        return $description;
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}
