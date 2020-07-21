<?php


namespace MxcDropshipInnocigs\Mapping\MetaData;

use Mxc\Shopware\Plugin\Service\ClassConfigAwareInterface;
use Mxc\Shopware\Plugin\Service\ClassConfigAwareTrait;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Html\HtmlDocument;


/**
 * Class MetaDataExtractor
 * @package MxcDropshipInnocigs\Mapping\MetaData
 *
 * Extraktion von Metadaten aus Produktbeschreibungen
 *
 */
class MetaDataExtractor implements ModelManagerAwareInterface, LoggerAwareInterface, ClassConfigAwareInterface
{
    use LoggerAwareTrait;
    use ModelManagerAwareTrait;
    use ClassConfigAwareTrait;

    private $document;
    private $cellTypes = [ '18350', '18650', '20700', '21700'];

    public function __construct(HtmlDocument $document)
    {
        $this->document = $document;
    }

    public function extractMetaData(Product $product, ?array $what = null)
    {
        $type = $product->getType();
        if (! in_array($type, $this->classConfig['types'])) return;

        if (null === $what) {
            $what = @$this->classConfig['defaults'][$type];
        }
        if ($what === null) return;

        $topics = $this->setupSearchTopics($product);

        foreach ($what as $topic) {
            switch ($topic) {
                case 'BATTERIES':
                    $cellCapacity = $this->extractCellCapacity($topics);
                    $product->setCellCapacity($cellCapacity);

                    $cellCount = $this->extractCellCount($topics);
                    if ($cellCount === null && $cellCapacity === null) {
                        $cellCount = 1;
                    }
                    $product->setCellCount($cellCount);

                    $cellTypes = $this->extractCellTypes($topics);
                    if (! empty($cellTypes)) {
                        $cellTypes = implode(MxcDropshipInnocigs::MXC_DELIMITER_L1, $cellTypes);
                        $product->setCellTypes($cellTypes);
                    }

                    break;

                case 'TANK_CAPACITY':
                    $tankCapacity = $this->extractTankCapacity($topics);
                    $product->setCapacity($tankCapacity);
                    break;

                case 'POWER':
                    $power = $this->extractPower($topics);
                    $product->setPower($power);
                    break;

                case 'HEAD_CHANGABLE':
                    $headChangeable = $this->extractHeadChangeable($topics);
                    $product->setHeadChangeable($headChangeable);
                    break;

                case 'INHALATION_STYLE':
                    $this->extractStyle($topics);
                    // @todo: INHALATION_STYLE property in product
                    break;
            }
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
    protected function extractCellCount(array $topics)
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

        foreach ($sources as $source) {
            if ($source === null) continue;
            $matches = [];
            $count = preg_match_all('~(\d+,\d|\d+) Watt~', $source, $matches, PREG_SET_ORDER);
            if ($count != false && $count > 0) {
                $power = end($matches)[1];
                $power = str_replace(',', '.', $power);
                break;
            }
        }

        return $power;
    }

    protected function extractStyle(array $topics)
    {
        $source = $topics['tables'][1] ?? $topics['tables'][0];
        if ($source = null) return null;

        $mtlStyles = [ '~MTL~', '~[Mm]outh [Tt]o [Ll]ung~', '~Mund.*Lunge~'];
        $dlStyles = ['~DL~', '~Direct Lung~', '~direkte Lungeninhalation~', '~direkte Lungenzüge~' ];

        if (preg_match('~(\d+,\d|\d+) Watt~', $source, $matches) === 1) {
            $power = $matches[1];
            if (strpos($power, ',') !== false)
                $this->log->debug('Decimal power: '. $power);
            str_replace(',', '.', $power);
        }


    }
}