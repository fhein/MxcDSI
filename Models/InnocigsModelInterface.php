<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 06.12.2018
 * Time: 19:06
 */

namespace MxcDropshipInnocigs\Models;

use DateTime;

interface InnocigsModelInterface
{
    public function setIgnored(bool $ignored);

    public function getIgnored(): bool;

    public function setActive(bool $active);

    public function getActive(): bool;

    public function getCreated(): DateTime;

    public function getUpdated(): DateTime;

    public function setCode(string $code);

    public function getCode(): string;
}