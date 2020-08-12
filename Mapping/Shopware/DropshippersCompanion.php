<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcDropshipIntegrator\Mapping\Shopware;


use MxcCommons\Plugin\Service\LoggerAwareInterface;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareInterface;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Services\ApiClient;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Shopware\ArticleTool;
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

        ArticleTool::setDetailAttribute($detail, 'dc_ic_ordernumber', $variant->getIcNumber());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_articlename', $variant->getName());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', $variant->getPurchasePrice());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_retail_price', $variant->getRecommendedRetailPrice());
        ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', $this->getStockInfo()[$variant->getIcNumber()] ?? 0);
        ArticleTool::setDetailAttribute($detail, 'dc_ic_active', $active);
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
            $repository = $this->modelManager->getRepository(Plugin::class);
            $companion = $repository->findOneBy(['name' => 'wundeDcInnoCigs']);
            if (null === $companion)
            {
                $this->log->warn('Can not prepare articles for dropship orders. Dropshipper\'s Companion is not installed.');
                $this->valid = false;
            } else {
                $this->valid = true;
            }
        }
        return $this->valid;
    }

    protected function getStockInfo()
    {
        return $this->stockInfo ?? $this->stockInfo = $this->apiClient->getAllStockInfo();
    }
}