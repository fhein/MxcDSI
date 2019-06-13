<?php

namespace MxcDropshipInnocigs\Mapping\Import;

use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;

class DescriptionMapper implements ProductMapperInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    private $mappings;

    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function map(Model $model, Product $product)
    {
        $this->log->debug('Mapping description for ' . $product->getName());
        $description = $this->mappings[$product->getIcNumber()]['description'] ?? null;
        if (! $description) {
            $description = $model->getDescription();
        }
        $product->setDescription($description);
    }

    public function report()
    {
        // TODO: Implement report() method.
    }
}
