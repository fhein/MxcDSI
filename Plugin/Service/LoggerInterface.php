<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 23.11.2018
 * Time: 13:12
 */

namespace MxcDropshipInnocigs\Plugin\Service;

use Throwable;

interface LoggerInterface extends \Zend\Log\LoggerInterface
{
    public function except(Throwable $e, bool $logTrace = true, bool $rethrow = true);

    public function enter($function = null);

    public function leave($function = null);
}