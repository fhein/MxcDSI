<?php

namespace MxcDropshipInnocigs\Models;

interface ValidationModelInterface
{
    public function setAccepted(bool $accepted);
    public function isAccepted(): bool;
}