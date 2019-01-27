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
        $this->import = array_column($raw['PRODUCTS']['PRODUCT'], null, 'MODEL');

        foreach ($this->import as $item) {
            // flatten options
            $options = [];
            foreach ($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $options[] = $group . '#!#' . $option;
            }
            sort($options);
            $item['PRODUCTS_ATTRIBUTES'] = implode('##!##', $options);

            if (is_string($item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'])) {
                $item['PRODUCTS_IMAGE_ADDITIONAL'] = $item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'];
            } else {
                $images = $item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'];
                sort($images);
                $item['PRODUCTS_IMAGE_ADDITIONAL'] = implode('#!#', $images);
            }
            $this->import[$item['MODEL']] = $item;
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
