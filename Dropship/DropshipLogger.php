<?php

namespace MxcDropshipIntegrator\Dropship;

use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipIntegrator\Models\DropshipLogEntry;

class DropshipLogger implements ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;

    const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

    protected $logLevels = [
        self::EMERG => 'EMERGENCY',
        self::ALERT => 'ALERT',
        self::CRIT => 'CRITICAL',
        self::ERR  => 'ERROR',
        self::WARN => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
    ];

    public function log(int $level, string $module, string $message, string $orderNumber = null, int $position = null)
    {
        $entry = new DropshipLogEntry();
        $entry->set($level, $module, $message, $orderNumber, $position);
        $this->modelManager->persist($entry);
    }

    public function done()
    {
        $this->modelManager->flush();
    }

    // enables methods emerg, alert, crit, err, warn, notice, info, debug
    public function __call($method, $params)
    {
        $m = strtolower($method);
        if ($m !== $method) return null;
        $constant = 'self::' . strtoupper($method);
        $level = constant($constant);
        if ($level === null) return null;
        array_unshift($params, $level);
        return call_user_func_array([$this, 'log'], $params);
    }
}