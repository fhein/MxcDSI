<?php

namespace MxcDropshipInnocigs\Convenience;

use Throwable;
use Zend\Log\Logger;

class ExceptionLogger
{
    protected $log;

    public function __construct(Logger $log) {
        $this->log = $log;
    }

    public function log(Throwable $e, bool $logTrace = true, bool $rethrow = true) {
        $this->log->crit(get_class($e) . ': "'. $e->getMessage(). '", code: ' . $e->getCode());
        if ($logTrace) $this->log->crit($e->getTraceAsString());
        if ($rethrow) throw($e);
    }
}