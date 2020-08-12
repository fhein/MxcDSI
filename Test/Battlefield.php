<?php


namespace MxcDropshipIntegrator\Test;


use MxcDropshipIntegrator\MxcDropshipIntegrator;

class Battlefield
{
    public function __construct(string $text)
    {
        $log = MxcDropshipIntegrator::getServices()->get('logger');

    }
}