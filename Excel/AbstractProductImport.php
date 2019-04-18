<?php


namespace MxcDropshipInnocigs\Excel;


use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

abstract class AbstractProductImport extends AbstractSheetImport
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var LoggerInterface $log */
    protected $log;

    public function __construct(ModelManager $modelManager, LoggerInterface $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

}