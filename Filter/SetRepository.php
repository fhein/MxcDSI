<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 04.12.2018
 * Time: 17:12
 */

namespace MxcDropshipInnocigs\Filter;


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