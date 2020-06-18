<?php


namespace MxcDropshipInnocigs\Mapping\MetaData;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Models\Product;


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

    private $cellTypes = [ '18350', '18650', '20700', '21700'];

    public function extractMetaData()
    {
        $products = $this->modelManager->getRepository(Product::class)->findAll();
        /** @var Product $product */
        foreach ($products as $product) {
            $type = $product->getType();
            switch ($type) {
                case 'POD_SYSTEM':
                    $this->extractMetaDataPodSystem($product);
                    break;
            }
        }
        $this->modelManager->flush();
    }

    protected function extractMetaDataPodSystem(Product $product)
    {
            $description = $product->getDescription();
            $name = $product->getName();

            // Suche eine mAh Angabe im Namen und in der Beschreibung
            $cellCapacity = $this->extractCellCapacity($name, $description);
            $product->setCellCapacity($cellCapacity);

            // Wenn keine Kapazitätsangabe vorhanden ist, ist der Akku wechselbar
            $cellChangeable = $cellCapacity === null;
            $product->setCellChangeable($cellChangeable);

            // Pod-Systeme haben heutzutage höchstens einen Akku
            $numberOfCells = $cellChangeable ? 1 : 0;
            $product->setNumberOfCells($numberOfCells);

            // Eine ml Angabe ist bei einem Pod-System das Tankvolumen
            $tankCapacity = $this->extractTankCapacity($name, $description);
            $product->setCapacity($tankCapacity);

            // Wenn im Lieferumfang das Wort Head auftaucht, sind die Köpfe wechselbar
            $headChangeable = $this->extractHeadChangeable($description);
            $product->setHeadChangeable($headChangeable);

            // Sucht nach Vorkommen der unter $this->cellTypes konfigurierten Typen
            $cellTypes = $this->extractCellTypes($description);
            $product->setCellTypes($cellTypes);
    }

    protected function extractCellCapacity(string $name, string $description)
    {
        $cellCapacity = null;
        $matches = [];

        // Suche Akkukapazität im Produktnamen und dann in der Beschreibung
        if (preg_match('~(\d?\.?\d+) mAh~', $name, $matches) === 1) {
            $cellCapacity = $matches[1];
        } elseif (preg_match('~(\d?\.?\d+) mAh~', $description, $matches) === 1) {
            $cellCapacity = $matches[1];
        }

        // Entferne Dezimalpunkt
        if ($cellCapacity !== null) {
            $cellCapacity = str_replace('.', '', $cellCapacity);
        }
        return $cellCapacity;
    }

    protected function extractTankCapacity(string $name, string $description)
    {
        $matches = [];
        $tankCapacity = 0;

        // Suche ml Angabe im Produktnamen und dann in der Besschreibung
        if (preg_match('~(\d?,?\d+) ml~', $name, $matches) === 1) {
            $tankCapacity = $matches[1];
        } elseif (preg_match('~(\d?,?\d+) ml~', $description, $matches) === 1) {
            $tankCapacity = $matches[1];
        }

        // Bei ganzen Zahlen füge ,0 hinzu
        if ($tankCapacity !== null) {
            if (strpos($tankCapacity, ',') === false) {
                $tankCapacity .= ',0';
            }
        }
        return $tankCapacity;
    }

    protected function extractHeadChangeable(string $description)
    {
        // Suche im Lieferumfang nach dem Wort Head
        return preg_match('~\d.*x.*Head~', $description) === 1;
    }

    protected function extractCellTypes(string $description): ?array
    {
        $cellTypes = [];
        foreach ($cellTypes as $cellType) {
            if (strpos($cellType, $description) !== false) {
                $cellTypes[] = $cellType;
            }
        }
        return isempty($cellTypes) ? null : $cellTypes;
    }
}