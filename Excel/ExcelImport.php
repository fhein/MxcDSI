<?php

namespace MxcDropshipInnocigs\Excel;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Reader;
use Shopware\Components\Model\ModelManager;

class ExcelImport
{
    /** @var ModelManager */
    protected $modelManager;

    /** @var LoggerInterface */
    protected $log;

    protected $importers;

    protected $excelFile = __DIR__ . '/../Config/vapee.export.xlsx';

    public function __construct(ModelManager $modelManager, array $importers, LoggerInterface $log)
    {
        $this->modelManager = $modelManager;
        $this->importers = $importers;
        $this->log = $log;
    }

    public function import() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $spreadSheet = (new Reader())->load($this->excelFile);
        foreach ($this->importers as $title => $importer)
        {
            $sheet = $spreadSheet->getSheetByName($title);
            if (! $sheet) continue;
            $importer->import($sheet);
        }
    }
}