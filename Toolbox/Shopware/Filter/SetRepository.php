<?php

namespace MxcDropshipInnocigs\Toolbox\Shopware\Filter;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

class SetRepository
{
    protected $modelManager;
    protected $log;

    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

}