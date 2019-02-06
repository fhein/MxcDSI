<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class ImportClient implements EventSubscriber
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var LoggerInterface $log */
    protected $log;

    /** @var Config $config */
    protected $config;

    /** @var ImportMapper $importMapper */
    protected $importMapper;

    /** @var array $import */
    protected $import;

    /** @var array */
    protected $additions;

    /** @var array */
    protected $changes;

    /** @var array */
    protected $deletions;

    protected $importLog;

    /** @var array */
    protected $categoryUsage;

    /** @var array */
    protected $categories;

    /** @var array */
    protected $fields;

    /**
     * ImportClient constructor.
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     * @param ImportMapper $importMapper
     * @param Config $config
     * @param LoggerInterface $log
     */
    public function __construct(
        ModelManager $modelManager,
        ApiClient $apiClient,
        ImportMapper $importMapper,
        Config $config,
        LoggerInterface $log
    ) {
        $this->modelManager = $modelManager;
        $this->importMapper = $importMapper;
        $this->apiClient = $apiClient;
        $this->log = $log;
        $this->config = $config;
    }

    public function getSubscribedEvents()
    {
        return ['preUpdate'];
    }

    public function import()
    {
        $this->importLog['deletions'] = $this->modelManager->getRepository(Model::class)->getAllIndexed();
        $this->importLog['additions'] = [];
        $this->importLog['changes'] = [];

        $model = new Model();
        $this->fields = $model->getPrivatePropertyNames();

        $evm = $this->modelManager->getEventManager();
        $evm->addEventSubscriber($this);

        $this->apiImport();
        $this->createModels();
        $this->deleteModels();
        $this->modelManager->flush();

        $this->categories = array_keys($this->categories);
        sort($this->categories);
        ksort($this->categoryUsage);
        $topics = [
            'innocigsCategories' => $this->categories,
            'innocigsCategoryUsage' => $this->categoryUsage,
            'importLog' => $this->importLog,
        ];
        $report = new ArrayReport();
        $report($topics);

        $evm->removeEventSubscriber($this);
        $this->importMapper->import($this->importLog);
    }

    protected function deleteModels() {
        /**
         * @var string $number
         * @var Model $model
         */
        foreach ($this->importLog['deletions'] as $number => $model) {
            $model->setDeleted(true);
        }
    }

    protected function createModels() {
        $limit = $this->config->get('limit', -1);
        $cursor = 0;
        foreach ($this->import as $number => $data) {

            if ($cursor === $limit) return;
            $cursor++;

            $model = $this->importLog['deletions'][$number];
            if (null !== $model) {
                unset($this->importLog['deletions'][$number]);
            } else {
                $model = new Model();
                $this->importLog['additions'][$number] = $model;
                $this->modelManager->persist($model);
            }
            $model->fromImport($data);
            $category = $data['category'];
            $this->categoryUsage[$category][] = $model->getName();
            $this->categories[$category] = true;
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var PreUpdateEventArgs $args */
        $model = $args->getEntity();
        if (! $model instanceof Model) return;

        $number = $model->getModel();
        $this->importLog['changes'][$number]['model'] = $model;
        foreach ($this->fields as $field) {
            if ($args->hasChangedField($field)) {
                $this->importLog['changes'][$number]['fields'][$field] = [
                    'oldValue' => $args->getOldValue($field),
                    'newValue' => $args->getNewValue($field)
                ];
            }
        }
    }

    protected function apiImport()
    {
        $report = new ArrayReport();
        $raw = $this->apiClient->getItemList();
        $topics['ImportDataRaw'] = $raw;

        $this->import = [];
        foreach ($raw['PRODUCTS']['PRODUCT'] as $data) {
            // flatten options
            $item['category'] = $this->getParamString($data['CATEGORY']);
            $item['model'] = $this->getParamString($data['MODEL']);
            $item['master'] = $this->getParamString($data['MASTER']);
            $item['ean'] = $this->getParamString($data['EAN']);
            $item['name'] = $this->getParamString($data['NAME']);
            $item['retailPrice'] = $this->getParamString($data['PRODUCTS_PRICE']);
            $item['purchasePrice'] = $this->getParamString($data['PRODUCTS_PRICE_RECOMMENDED']);
            $item['manufacturer'] = $this->getParamString($data['MANUFACTURER']);
            $item['manual'] = $this->getParamString($data['PRODUCTS_MANUAL']);
            $item['options'] = $this->condenseOptions($data['PRODUCTS_ATTRIBUTES']);
            $item['images'] = $this->condenseImages(
                $this->getParamString($data['PRODUCTS_IMAGE']),
                $this->getParamArray($data['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'])
            );
            $this->import[$item['model']] = $item;
        }
        $topics['importData'] = $this->import;
        $report($topics);
    }

    protected function condenseImages(?string $image, array $addlImages) {
        $images = [];
        if (is_string($image) && $image !== '') {
            $images[] = $image;
        }
        if (! empty($addlImages)) {
            sort($addlImages);
            $images[] = implode('#!#', $addlImages);
        }
        return implode('#!#', $images);
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function condenseOptions(array $attributes)
    {
        $options = [];
        foreach ($attributes as $group => $option) {
            $options[] = trim($group) . '#!#' . trim($option);
        }
        sort($options);
        return implode('##!##', $options);
    }

    protected function getParamArray($value) {
        if (null === $value) return [];
        if (is_string($value)) return [ $value ];
        if (is_array($value)) return $value;
        throw new InvalidArgumentException(
            sprintf('String or array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    protected function getParamString($value)
    {
        if (is_string($value)) return trim($value);
        if ($value === null || is_array($value)) return '';
        throw new InvalidArgumentException(
            sprintf('String or empty array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
