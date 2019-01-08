<?php

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Database\BulkOperation;
use Zend\Config\Config;

class ImportModifier
{
    /**
     * @var BulkOperation $updater
     */
    protected $updater;

    /**
     * @var array $config
     */
    protected $config;

    public function __construct(BulkOperation $updater, Config $config) {
        $this->updater = $updater;
        $this->config = $config->toArray();
    }

    public function apply()
    {
        foreach($this->config as $filter) {
            $this->updater->update($filter);
        }
    }
}