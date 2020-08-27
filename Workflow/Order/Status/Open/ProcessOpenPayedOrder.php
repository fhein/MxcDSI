<?php

namespace MxcDropshipIntegrator\Workflow\Order\Status\Open;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;

class ProcessOpenPayedOrder implements AugmentedObject
{
    use DatabaseAwareTrait;
}
