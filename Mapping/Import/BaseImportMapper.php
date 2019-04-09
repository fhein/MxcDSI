<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;

class BaseImportMapper
{
    /** @var array $config */
    protected $config = [];

    /** @var LoggerInterface $log */
    protected $log;

    /** @var array */
    protected $report;

    public function __construct(array $config, LoggerInterface $log)
    {
        $this->log = $log;
        $this->config = $config;
    }
}