<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use Zend\Config\Config;

class ImportBase
{
    /**
     * @var ApiClient $apiClient
     */
    protected $apiClient;

    /**
     * @var LoggerInterface $log
     */
    protected $log;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var array $import
     */
    protected $import;

    /**
     * @var array $items
     */
    protected $items;

    /**
     * ImportBase constructor.
     *
     * @param ApiClient $apiClient
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ApiClient $apiClient,
        Config $config,
        LoggerInterface $log
    ) {
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->log = $log;
    }

    public function import()
    {
        $raw = $this->apiClient->getItemList();
        $this->import = [];
        /** @noinspection PhpUndefinedFieldInspection */
        $limit = $this->config->numberOfArticles ?? -1;

        foreach ($raw['PRODUCTS']['PRODUCT'] as $item) {
            $this->import[$item['MASTER']][$item['MODEL']] = $item;
            foreach ($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $this->items['groups'][$group][$option] = true;
            }
            $this->items['images'][$item['PRODUCTS_IMAGE']] = true;
            foreach ($item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'] as $image) {
                    $this->items['images'][$image] = true;
            }
            if ($limit !== -1 && count($this->import) === $limit) {
                break;
            }
        }
    }

    protected function getParamString($value)
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && empty($value)) {
            return '';
        }
        throw new InvalidArgumentException(
            sprintf('String or empty array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

}
