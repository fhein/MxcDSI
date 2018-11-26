<?php

namespace MxcDropshipInnocigs\Plugin\Service;

use Throwable;

interface LoggerInterface extends \Zend\Log\LoggerInterface
{
    public function except(Throwable $e, bool $logTrace = true, bool $rethrow = true);

    public function enter();

    public function leave();
}