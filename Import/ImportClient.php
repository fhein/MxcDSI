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
        ];
        $report = new ArrayReport();
        $report($topics);

        $evm->removeEventSubscriber($this);
        // $this->logImport();
        $this->importMapper->import($this->importLog);
    }

    protected function logImport() {
        $this->log->debug('Additions:');
        $this->log->debug(var_export(array_keys($this->importLog['additions']), true));
        $this->log->debug('Deletions:');
        $this->log->debug(var_export(array_keys($this->importLog['deletions']), true));
        $this->log->debug('Changes:');
        $this->log->debug(var_export($this->importLog['changes'], true));
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
            $this->setModel($model, $data);
        }
    }

    /**
     * @param Model $model
     * @param array $data
     */
    protected function setModel(Model $model, array $data): void
    {
        $category = $this->getParamString($data['CATEGORY']);
        $model->setCategory($category);
        $model->setMaster($this->getParamString($data['MASTER']));
        $model->setModel($this->getParamString($data['MODEL']));
        $model->setEan($this->getParamString($data['EAN']));
        $model->setName($this->getParamString($data['NAME']));
        $model->setPurchasePrice($this->getParamString($data['PRODUCTS_PRICE']));
        $model->setRetailPrice($this->getParamString($data['PRODUCTS_PRICE_RECOMMENDED']));
        $model->setManufacturer($this->getParamString($data['MANUFACTURER']));
        $model->setAdditionalImages($data['PRODUCTS_IMAGE_ADDITIONAL']);
        $model->setOptions($data['PRODUCTS_ATTRIBUTES']);

        $this->categoryUsage[$category][] = $model->getName();
        $this->categories[$category] = true;
    }

    public function getSubscribedEvents()
    {
        return ['preUpdate'];
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var PreUpdateEventArgs $args */
        $model = $args->getEntity();
        if (! $model instanceof Model) return;

        $number = $model->getNumber();
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
        $raw = $this->apiClient->getItemList();
        $this->import = [];
        /** @noinspection PhpUndefinedFieldInspection */
        $this->import = array_column($raw['PRODUCTS']['PRODUCT'], null, 'MODEL');

        foreach ($this->import as $item) {
            // flatten options
            $options = [];
            foreach ($item['PRODUCTS_ATTRIBUTES'] as $group => $option) {
                $options[] = trim($group) . '#!#' . trim($option);
            }
            sort($options);
            $item['PRODUCTS_ATTRIBUTES'] = implode('##!##', $options);
            if (is_string($item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE'])) {
                $item['PRODUCTS_IMAGE_ADDITIONAL'] = trim($item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE']);
            } else {
                $images = array_map('trim', $item['PRODUCTS_IMAGE_ADDITIONAL']['IMAGE']);
                sort($images);
                $item['PRODUCTS_IMAGE_ADDITIONAL'] = implode('#!#', $images);
            }
            $image = trim($this->getParamString($item['PRODUCTS_IMAGE']));
            if ($image !== '') {
                $item['PRODUCTS_IMAGE_ADDITIONAL'] = $image . '#!#' . $item['PRODUCTS_IMAGE_ADDITIONAL'];
            }
            $this->import[trim($item['MODEL'])] = array_map('trim', $item);
        }
    }

    protected function getParamString($value)
    {
        if (! $value || is_string($value)) return $value;
        if (is_array($value) && empty($value)) return '';
        throw new InvalidArgumentException(
            sprintf('String or empty array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
