<?php

namespace MxcDropshipInnocigs\Models;

use DateTime;

interface InnocigsModelInterface
{
    public function setAccepted(bool $accepted);

    public function isAccepted(): bool;

    public function setActive(bool $active);

    public function isActive(): bool;

    public function getCreated(): DateTime;

    public function getUpdated(): DateTime;
}