<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

class InnocigsUpdater
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;
    /**
     * @var LoggerInterface $log
     */
    protected $log;

    /**
     * InnocigsUpdater constructor.
     * @param ModelManager $modelManager
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->modelManager = $modelManager;
        $this->log = $log;
    }
}