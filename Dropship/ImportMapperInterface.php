<?php

namespace MxcDropshipIntegrator\Dropship;

use Doctrine\Common\Collections\ArrayCollection;

interface ImportMapperInterface
{
    public function mapOptions(?string $optionString): ArrayCollection;
    public function import(array $changes);

}