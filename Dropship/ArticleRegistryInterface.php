<?php

namespace MxcDropshipIntegrator\Dropship;

use Shopware\Models\Article\Detail;

interface ArticleRegistryInterface
{
    public function register(int $detailId, string $productNumber, bool $active, int $delivery);
    public function unregister(int $detailId);
    public function getSettings(int $detailId);
}