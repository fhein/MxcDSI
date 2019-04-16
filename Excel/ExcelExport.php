<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Writer;
use Shopware\Components\Model\ModelManager;

class ExcelExport
{
    /** @var ModelManager */
    protected $modelManager;

    /** @var LoggerInterface */
    protected $log;

    protected $exporters;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(ModelManager $modelManager, array $exporters, LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->exporters = $exporters;
        $this->log = $log;
    }

    public function export() {
        $spreadSheet = new Spreadsheet();
        /** @var ExportPrices $exporter */
        foreach ($this->exporters as $title => $exporter) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $workSheet = $spreadSheet->createSheet();
            $workSheet->setTitle($title);
            $exporter->export($workSheet);
        }
        /** @noinspection PhpUnhandledExceptionInspection */
        $spreadSheet->removeSheetByIndex(0);
        $writer = new Writer($spreadSheet);
        /** @noinspection PhpUnhandledExceptionInspection */
        $writer->save($this->excelFile);
    }
}