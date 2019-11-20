<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class
DescriptionMapper implements ProductMapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    private $mappings;

    protected $descriptionAromaDefault =
        '<p><strong>Aroma von ##supplier##</strong> zur Herstellung von E-Liquid. <strong>Geschmack: ##flavor##</strong>.</p>'
        . '<p>Geben Sie das Aroma entsprechend der Dosierempfehlung '
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
        '<p><strong>Longfill-Aroma von ##supplier##</strong> zur Herstellung von E-Liquid. <strong>Geschmack: ##flavor##</strong>.</p>'
        .'<p>##content## ml Aroma sind in eine ##capacity## ml Flasche abgefüllt.'
        . ' Füllen Sie die Flasche einfach mit einer nikotinfreien oder nikotinhaltigen Base bis oben auf. Verschließen Sie dann die Flasche und schütteln Sie '
        . ' durch, damit sich die Flüssigkeiten gut vermischen. Das fertige E-Liquid sollte nun vor der Verwendung noch einige Tage reifen, damit sich '
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

    protected $descriptionAroma = [
        'Elli\'s Aromen' =>
            '<p><strong>Lebensmittelaroma von ##supplier##. Geschmack: ##flavor##.</strong></p>'
            . '<p>Zur Herstellung von E-Liquid fügen Sie das Aroma entsprechend der Dosierempfehlung '
            . 'des Herstellers einer nikotinfreien oder nikotinhaltigen Basis zu und mischen Sie das Ergebnis gut durch. Das fertige '
            . 'Liquid sollte nun vor der Verwendung noch einige Tage reifen, damit sich die Aromastoffe entfalten können.</p>'
            . '<p>Detaillierte Angaben zu Dosierung und Reifezeit von Elli\'s Aromen finden Sie unter diesem Link: <a title="Ellifiziert!" href="http://www.ellifiziert.de/?page=labor" target="_blank">Ellifiziert!</a></p>'
            . '<p><strong>Aromen sind hochkonzentriert und dürfen keinesfalls pur gedampft werden.</strong></p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>##content## ml Lebensmittelaroma</td>'
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
            . '</table>',
    ];

    protected $descriptionShakeVapeDefault =
        '<p><strong>Shake & Vape E-Liquid von ##supplier##. Geschmack: ##flavor##.</strong></p>'
        . '<p>Die ##capacity## ml Flasche enthält ##content## ml überaromatisiertes, nikotinfreies E-Liquid. '
        . 'Füllen Sie die Flasche einfach mit ##fillup## ml Nikotin-Shot oder ##fillup## ml Basis auf. Anschließend gut schütteln, und Ihr E-Liquid ist fertig zum Gebrauch.</p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
        . '<tbody>'
        . '<tr>'
        . '<td>Produkt</td>'
        . '<td>##content## ml überaromatisiertes E-Liquid in ##capacity## ml Flasche </td>'
        . '</tr>'
        . '<tr>'
        . '<td>Geschmack </td>'
        . '<td>##flavor##</td>'
        . '</tr>'
        . '<tr>'
        . '<td>Inhaltsstoffe<sup>1</sup></td>'
        . '<td>PG, VG, Aromastoffe</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>';

    protected $descriptionShakeVapeTwoSizes =
        '<p><strong>Shake & Vape E-Liquid von ##supplier##. Geschmack: ##flavor##.</strong></p>'
        . '<p>Wählen Sie zwischen einer ##capacity1## ml Flasche mit ##content1## ml und einer '
        . '##capacity2## ml Flasche mit ##content2## ml überaromatisiertem, nikotinfreien E-Liquid aus. '
        . 'Füllen Sie die Flasche einfach mit ##fillup1## ml bzw. ##fillup2## ml Nikotin-Shot oder Basis auf. Anschließend gut schütteln, und Ihr E-Liquid ist fertig zum Gebrauch.</p>'
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
        . '<td>PG, VG, Aromastoffe</td>'
        . '</tr>'
        .'</tbody>'
        . '</table>'
        . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin. </p>';

    protected $descriptionShakeVape = [
        'Chicken Shop' =>
            '<p><strong>Shake & Vape E-Liquid von Chicken Shop. Geschmack: ##flavor##.</strong></p>'
            . '<p>Wählen Sie zwischen 25 ml und 200 ml überaromatisiertem, nikotinfreien E-Liquid aus.</p>'
            . '<p>Die Variante mit 25 ml E-Liquid erhalten in einer 30 ml Flasche. '
            . 'Füllen Sie diese Flasche einfach mit 5 ml Nikotin-Shot oder Basis auf. Anschließend gut schütteln, und Ihr E-Liquid ist fertig zum Gebrauch.</p>'
            . '<p>In der 200 ml Variante erhalten Sie neben der vollständig mit überaromatisiertem E-Liquid gefüllten 200 ml Flasche zwei 60 ml Leerflaschen zum '
            . 'Mischen Ihres E-Liquids. Füllen Sie dazu eine 60 ml Flasche mit 50 ml E-Liquid und 10 ml Nikotin-Shot oder Basis. '
            . 'Gut schütteln und Ihr E-Liquid ist fertig.</p>'
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
            . '<td>PG, VG, Aromastoffe</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>',

        'Pink Spot' =>
            '<p><strong>Shake & Vape E-Liquid von Pink Spot. Geschmack: ##flavor##.</strong></p>'
            . '<p>Die 60 ml Flasche enthält 50 ml überaromatisiertes, nikotinfreies E-Liquid. '
            . 'Füllen Sie die Flasche einfach mit einem 10 ml Nikotin-Shot oder 10 ml Basis auf. Anschließend gut schütteln, und Ihr E-Liquid ist fertig zum Gebrauch.</p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>50 ml überaromatisiertes E-Liquid in 60 ml Flasche </td>'
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
            . '<td>PG, VG, Aromastoffe</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>',
        'Koncept XIX' =>
            '<p><strong>Shake & Vape E-Liquid von Vampire Vape. Geschmack: ##flavor##.</strong></p>'
            . '<p>Die 60 ml Flasche enthält 50 ml überaromatisiertes, nikotinfreies E-Liquid. '
            . 'Füllen Sie die Flasche einfach mit einem 10 ml Nikotin-Shot oder 10 ml Basis auf. Anschließend gut schütteln, und Ihr E-Liquid ist fertig zum Gebrauch.</p>'
            . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
            . '<tr>'
            . '<td>Produkt</td>'
            . '<td>50 ml überaromatisiertes E-Liquid in 60 ml Flasche </td>'
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
            . '<td>PG, VG, Aromastoffe</td>'
            . '</tr>'
            .'</tbody>'
            . '</table>'
            . '<p><sup>1</sup> PG: Propylenglykol, VG: pflanzliches Glycerin</p>'
    ];

    protected $descriptionLiquidDefault =
        '<p><strong>Gebrauchsfertiges E-Liquid von ##supplier## für die E-Zigarette. Geschmack: ##flavor##.</strong></p><p>Einfach in den Verdampfer der E-Zigarette einfüllen und losdampfen!</p>'
        . '<table border="5" frame="hsides" rules="rows" cellspacing="0" cellpadding="2">'
            . '<tbody>'
                . '<tr>'
                    . '<td>Produkt</td>'
                    . '<td>Gebrauchsfertiges E-Liquid, 10 ml Flasche </td>'
                . '</tr>'
                . '<tr>'
                    . '<td>Geschmack </td>'
                    . '<td>##flavor##</td>'
                . '</tr>'
                . '<tr>'
                    . '<td>Inhaltsstoffe<sup>1</sup></td>'
                    . '<td>PG, VG, Aromastoffe, Nikotin nach Wahl</td>'
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
        $supplier = $product->getBrand();
        $flavor = ucfirst($product->getFlavor());
        $description = null;

        $description = $this->descriptionAroma[$supplier] ?? null;
        if ($description !== null) {
            $description = str_replace(
                ['##flavor##', '##dosage##', '##content##', '##capacity##', '##supplier##'],
                [$flavor, $product->getDosage(), $product->getContent(), $product->getCapacity(), $supplier],
                $description
            );
            return $description;
        }

        if ($content === $capacity) {
            $description = $this->descriptionAromaDefault;
            $description = str_replace(
                ['##flavor##', '##dosage##', '##content##', '##supplier##'],
                [$flavor, $product->getDosage(), $product->getContent(), $supplier],
                $description
            );
        } else {
            $description = $this->descriptionAromaLongfill;
            $description = str_replace(
                ['##flavor##', '##content##', '##capacity##', '##supplier##'],
                [$flavor, $product->getContent(), $product->getCapacity(), $supplier],
                $description
            );
        }

        return $description;
    }

    protected function getShakeVapeDescription(Product $product)
    {
        $name = $product->getName();
        if (is_int(strpos($name, 'Koncept XIX'))) {
            return $this->descriptionShakeVape['Koncept XIX'] ?? null;
        }
        // Note: We assume that all variants have the same content
        // so that we can use the content value of the first
        $variant = $product->getVariants()[0];
        $content = $variant->getContent();
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
            $description = str_replace(
                ['##fillup##', '##content##', '##capacity##'],
                [$fillup, $content[0], $capacity[0]],
                $description
            );

            return $description;
        }

        if (count($content) === 2 && count($capacity) === 2) {
            $description = $this->descriptionShakeVapeTwoSizes;
            $fillup1 = $capacity[0] - $content[0];
            $fillup2 = $capacity[1] - $content[1];
            $description = str_replace(
                ['##fillup1##', '##fillup2##', '##content1##', '##capacity1##', '##content2##', '##capacity2##'],
                [$fillup1, $fillup2, $content[0], $capacity[0], $content[1], $capacity[1]],
                $description
            );

            return $description;
        }

        return @$this->descriptionShakeVape[$product->getBrand()];
    }

    public function map(Model $model, Product $product, bool $remap = false)
    {
        $description = @$this->mappings[$product->getIcNumber()]['description'];
        if ($remap || ! $description) {
            $this->remap($product);
            return;
        }
        $product->setDescription($description);
    }

    public function remap(Product $product)
    {
        $type = $product->getType();
        $flavor = ucfirst($product->getFlavor());
        $supplier = $product->getBrand();

        switch ($type) {
            case 'LIQUID':
                $description = $this->descriptionLiquidDefault;
                $description = str_replace(['##flavor##', '##supplier##'], [$flavor, $supplier], $description);
                break;
            case 'SHAKE_VAPE':
                $description = $this->getShakeVapeDescription($product);
                $description = str_replace(['##flavor##', '##supplier##'], [$flavor, $supplier], $description);
                break;
            case 'AROMA':
                $description = $this->getAromaDescription($product);
                break;
            default:
                $description = $this->mappings[$product->getIcNumber()]['description'] ?? $product->getIcDescription();
        }
        $product->setDescription($description);
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}
