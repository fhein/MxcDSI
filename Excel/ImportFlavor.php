<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Shopware\Components\Model\ModelManager;

class ImportFlavor
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    private $products;

    public function __construct(
        ModelManager $modelManager,
        LoggerInterface $log
    ) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    public function import(Worksheet $sheet)
    {
        $records = $this->entitiesToArray($sheet->toArray());
        if (! is_array($records) || empty($records)) return;

        foreach ($records as $record) {
            $this->updateFlavor($record);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->modelManager->flush();
    }

    protected function updateFlavor(array $record)
    {
        /** @var Product $product */
        $product = $this->getProducts()[$record['icNumber']];
        if (! $product) return;
        $values = explode(',', $record['flavor']);
        $values = array_map('trim', $values);
        $flavor = implode(', ', $values);
        $product->setFlavor($flavor);
    }

    protected function entitiesToArray(array $entities)
    {
        $headers = null;
        foreach ($entities as &$entity) {
            if (! $headers) {
                $headers = $entity;
                continue;
            }
            $entity = array_combine($headers, $entity);
        }
        // remove header entity
        array_shift($entities);
        return $entities;

    }

    protected function getProducts()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->products ?? $this->products = $this->modelManager->getRepository(Product::class)->getFlavoredProducts();
    }
}