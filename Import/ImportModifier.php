<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Database\BulkOperation;
use Zend\Config\Config;

class ImportModifier
{
    /**
     * @var BulkOperation $bulkOperation
     */
    protected $bulkOperation;

    /**
     * @var array $config
     */
    protected $config;

    public function __construct(BulkOperation $ibulkOperation, Config $config) {
        $this->bulkOperation = $ibulkOperation;
        $this->config = $config->toArray();
    }

    public function apply()
    {
        foreach($this->config as $filter) {
            $this->bulkOperation->update($filter);
        }
    }
}