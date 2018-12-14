<?php
namespace MxcDropshipInnocigs\Mapping\Filter;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Shopware\Components\Model\ModelManager;

class InnocigsFilter
{
    /**
     * @var LoggerInterface $log
     */
    protected $log;
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * InnocigsFilter constructor.

     * @param ModelManager $modelManager
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, LoggerInterface $log) {
        $this->modelManager = $modelManager;
        $this->log = $log;
    }
}