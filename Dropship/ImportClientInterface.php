<?php

namespace MxcDropshipIntegrator\Dropship;

interface ImportClientInterface
{
    public function importFromXml (string $xml, bool $sequential, bool $recreateSchema = false);
    public function importFromFile (string $xmlFile, bool $sequential, bool $recreateSchema = false);
    public function importFromApi (bool $includeDescriptions, bool $sequential, bool $recreateSchema = false);
}