<?php


namespace MxcDropshipInnocigs\Mapping\MetaData;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Toolbox\Html\HtmlDocument;


/**
 * Class MetaDataExtractor
 * @package MxcDropshipInnocigs\Mapping\MetaData
 *
 * Extraktion von Metadaten aus Produktbeschreibungen
 *
 */
class MetaDataExtractor implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;

    private $document;
    private $cellTypes = [ '18350', '18650', '20700', '21700'];

    public function __construct(HtmlDocument $document)
    {
        $this->document = $document;
    }

    public function extractMetaData(Product $product)
    {
        $type = $product->getType();
        switch ($type) {
            case 'POD_SYSTEM':
                $this->extractMetaDataPodSystem($product);
                break;
            case 'E_CIGARETTE':
                $this->extractMetaDataEcigarette($product);
                break;
        }
        $this->modelManager->flush();
    }

    protected function setupSearchTopics(Product $product)
    {
        $description = $product->getDescription();
        $topics['name'] = $product->getName();
        $topics['description'] = $description;

        $tables = $this->document->getHtmlByTagName('table', $description);
        $topics['tables'] = array_values($tables);
        $tables = $this->document->getTablesAsArray($description);
        $topics['arrays'] = $tables;
        $topics['scopeOfDelivery'] = $this->document->getScopeOfDelivery($description);
        return $topics;
    }

    protected function extractMetaDataEcigarette(Product $product)
    {
        $topics = $this->setupSearchTopics($product);

        $cellCapacity = $this->extractCellCapacity($topics);
        $product->setCellCapacity($cellCapacity);

        $cellChangeable = $cellCapacity === null;
        $product->setCellChangeable($cellChangeable);

        $numberOfCells = $this->extractNumberOfCells($topics);
        if ($numberOfCells === null && $cellChangeable) {
            $numberOfCells = 1;
        }
        $product->setNumberOfCells($numberOfCells);

        $tankCapacity = $this->extractTankCapacity($topics);
        $product->setCapacity($tankCapacity);

        $power = $this->extractPower($topics);
        $product->setPower($power);

        $headChangeable = $this->extractHeadChangeable($topics);
        $product->setHeadChangeable($headChangeable);
    }

    protected function extractMetaDataPodSystem(Product $product)
    {
            $topics = $this->setupSearchTopics($product);

            // Suche eine mAh Angabe im Namen und in der Beschreibung
            $cellCapacity = $this->extractCellCapacity($topics);
            $product->setCellCapacity($cellCapacity);

            // Wenn keine Kapazitätsangabe vorhanden ist, ist der Akku wechselbar
            $cellChangeable = $cellCapacity === null;
            $product->setCellChangeable($cellChangeable);

            // Pod-Systeme haben heutzutage höchstens einen Akku
            $numberOfCells = $cellChangeable ? 1 : 0;
            $product->setNumberOfCells($numberOfCells);

            // Eine ml Angabe ist bei einem Pod-System das Tankvolumen
            $tankCapacity = $this->extractTankCapacity($topics);
            $product->setCapacity($tankCapacity);

            // Wenn im Lieferumfang das Wort Head auftaucht, sind die Köpfe wechselbar
            $headChangeable = $this->extractHeadChangeable($topics);
            $product->setHeadChangeable($headChangeable);

            // Sucht nach Vorkommen der unter $this->cellTypes konfigurierten Typen
            $cellTypes = $this->extractCellTypes($topics);
            $product->setCellTypes($cellTypes);
    }

    /**
     * Suche Akkukapazität im Produktnamen, in der ersten Tabelle oder in der gesamten Beschreibung
     */
    protected function extractCellCapacity(array $topics)
    {
        $cellCapacity = null;

        $sources = [ $topics['name'], $topics['tables'][0], $topics['description']];

        $matches = [];
        foreach ($sources as $source) {
            if ($source === null) continue;
            if (preg_match('~(\d?\.?\d+) mAh~', $source, $matches) === 1) {
                $cellCapacity = $matches[1];
                break;
            }
        }

        // Entferne Dezimalpunkt
        if ($cellCapacity !== null) {
            $cellCapacity = str_replace('.', '', $cellCapacity);
        }
        return $cellCapacity;
    }

    /**
     * Suche Tankkapazität im Namen, in der letzten Tabelle und in der gesamten Beschreibung
     *
     * @param array $topics
     * @return mixed|string|null
     */

    protected function extractTankCapacity(array $topics)
    {
        $matches = [];
        $tankCapacity = null;

        // Bei Produkten mit zwei Tabellen findet sich die Info in der zweiten Tabelle, sonst in der ersten
        $tableIdx = count($topics['tables']) === 2 ? 1 : 0;

        $sources = [ $topics['name'], $topics['tables'][$tableIdx], $topics['description']];

        foreach ($sources as $source) {
            if ($source === null) continue;
            if (preg_match('~(\d+,\d+|\d+) ml~', $source, $matches) === 1) {
                $tankCapacity = $matches[1];
                break;
            }
        }

        // Bei ganzen Zahlen füge ,0 hinzu
        if ($tankCapacity !== null) {
            if (strpos($tankCapacity, ',') === false) {
                $tankCapacity .= ',0';
            }
        }
        return $tankCapacity;
    }

    /**
     * Wir nehmen an, das der Kopf wechselbar ist, wenn das Wort Head im Lieferumfang vorkommt
     *
     * @param string $description
     * @return bool
     */
    protected function extractHeadChangeable(array $topics)
    {
        // Suche im Lieferumfang nach dem Wort Head
        $source = $topics['scopeOfDelivery'];
        if ($source === null) return false;

        return preg_match('~<li>.*(Head|Verdampferkopf).*</li>~', $source) === 1;
    }

    /**
     * Wir suchen in der ersten Tabelle oder in der gesamten Beschreibung
     * nach Vorkommen der Akkutypen, die unter $this->cellTypes konfiguriert sind
     *
     * @param array $topics
     * @return array|null
     */
    protected function extractCellTypes(array $topics): ?array
    {

        $sources = [ $topics['tables'][0], $topics['description']];

        $cellTypes = [];
        foreach ($sources as $source) {
            if ($source === null) continue;
            foreach ($cellTypes as $cellType) {
                if (strpos($cellType, $source) !== false) {
                    $cellTypes[] = $cellType;
                }
            }
            if (! empty($cellTypes)) break;
        }
        return $cellTypes;
    }

    /**
     * Finde in der ersten Tabelle einen Text mit Anzahl der Akkuzellen (z.B. 2x 18650er Akkuzelle)
     * Wenn keine erste Tabelle existiert, Abbruch.
     *
     * @param array $topics
     * @return int|null
     */
    protected function extractNumberOfCells(array $topics)
    {
        $source = $topics['tables'][0];
        if ($source === null) return null;

        $numberOfCells = null;
        $matches = [];
        if (preg_match('~(\d+) ?x.*Akkuzelle~', $source, $matches) === 1) {
            $numberOfCells = $matches[1];
        }
        return $numberOfCells;
    }

    /**
     * Finde die Wattzahl entweder im Namen oder in der ersten Tabelle der Produktbeschreibung
     *
     * @param array $topics
     * @return mixed|null
     */
    protected function extractPower(array $topics)
    {
        $sources = [ $topics['name'], $topics['tables'][0]];
        $power = null;

        $matches = [];
        foreach ($sources as $source) {
            if ($source === null) continue;

            if (preg_match('~(\d+,\d|\d+) Watt~', $source, $matches) === 1) {
                $power = $matches[1];
                if (strpos($power, ',') !== false)
                    $this->log->debug('Decimal power: '. $power);
                str_replace(',', '.', $power);
                break;
            }
        }

        return $power;
    }
}