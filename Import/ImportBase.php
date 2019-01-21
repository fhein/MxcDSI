<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use Shopware\Components\Model\ModelManager;
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
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * @var array $import
     */
    protected $import;

    /**
     * ImportBase constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->log = $log;
    }

    public function import()
    {
        $raw = $this->apiClient->getItemList();
        $this->import = [];
        $limit = $this->config->numberOfArticles ?? -1;

        foreach ($raw['PRODUCTS']['PRODUCT'] as $item) {
            $this->import['articles'][$item['MASTER']][$item['MODEL']] = $item;
            foreach ($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $this->import['groups'][$group][$option] = 1;
            }
            if ($limit !== -1 && count($this->articles) === $limit) {
                break;
            }
        }
    }
}
