<?php


namespace MxcDropshipInnocigs\Mapping\Shopware;


use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Import\ApiClient;
use MxcDropshipInnocigs\Models\Variant;
use Shopware\Models\Plugin\Plugin;

class DropshippersCompanion implements ModelManagerAwareInterface, LoggerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var bool */
    protected $valid;

    /** @var ApiClient */
    private $apiClient;

    /** @var array */
    private $stockInfo;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function configureDropship(Variant $variant, bool $active = true)
    {
        $detail = $variant->getDetail();
        if (! $detail) return;

        if (! $this->validate()) return;

        $attribute = $detail->getAttribute();
        // @todo: $attribute null happens but it should not
        if (! $attribute) return;

        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcActive($active);
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcOrderNumber($variant->getIcNumber());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcArticleName($variant->getProduct()->getName());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcPurchasingPrice($variant->getPurchasePrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcRetailPrice($variant->getRecommendedRetailPrice());
        /** @noinspection PhpUndefinedMethodInspection */
        $attribute->setDcIcInstock($this->getStockInfo()[$variant->getIcNumber()] ?? 0);
    }

    /**
     * Check if the Dropshipper's Companion for InnoCigs Shopware plugin is installed or not.
     * If installed, check if the required APIs provided by the companion plugin are present.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (! is_bool($this->valid)) {
            $className = 'Shopware\Models\Attribute\Article';
            if (null === $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs'])
                || !(method_exists($className, 'setDcIcOrderNumber')
                    && method_exists($className, 'setDcIcArticleName')
                    && method_exists($className, 'setDcIcPurchasingPrice')
                    && method_exists($className, 'setDcIcRetailPrice')
                    && method_exists($className, 'setDcIcActive')
                    && method_exists($className, 'setDcIcInstock'))
            ) {
                $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
                $this->valid = false;
            } else {
                $this->valid = true;
            }
        };
        return $this->valid;
    }

    protected function getStockInfo()
    {
        return $this->stockInfo ?? $this->stockInfo = $this->apiClient->getAllStockInfo();
    }
}