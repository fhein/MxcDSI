<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Report\ArrayReport;
use MxcDropshipInnocigs\Toolbox\Arrays\ArrayTool;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;
use const MxcDropshipInnocigs\MXC_DELIMITER_L1;
use const MxcDropshipInnocigs\MXC_DELIMITER_L2;

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

    /** @var array $options */
    protected $optionNames;

    /** @var array */
    protected $categoryUsage = [];

    /** @var array */
    protected $categories = [];

    /** @var array */
    protected $missingItems = [];

    /** @var array */
    protected $fields;

    /** @var ArrayReport */
    protected $reporter;

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
        $this->reporter = new ArrayReport();
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
        $this->optionNames = [];

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
        ksort($this->optionNames);
        foreach ($this->missingItems as &$item) {
            asort($item);
        }
        $topics = [
            'imCategoryInnocigs'      => $this->categories,
            'imCategoryUsageInnocigs' => $this->categoryUsage,
            'imOptionNamesInnocigs'   => array_keys($this->optionNames),
            'imMissingItems'          => $this->missingItems,
        ];

        ($this->reporter)($topics);

        $evm->removeEventSubscriber($this);
        $this->importMapper->import($this->importLog);
    }


    protected function apiImport()
    {
        $this->import = $this->apiClient->getItemList();
        $i = 1;
        $description = [];
        foreach ($this->import as &$master) {
            foreach ($master as &$item) {
                $item['options'] = $this->condenseOptions($item['options']);
                if (! empty($item['images'])) {
                    $this->missingItems['additional_images_available'][$item['model']] = $item['name'];
                }
                $item['images'] = $this->condenseImages($item['image'], $item['images']);
                unset ($item['image']);
                if ($item['description'] === '') {
                    $this->missingItems['missing_descriptions'][$item['model']] = $item['name'];
                } else {
                    $description[$item['description']]['models'][$item['model']] = $item['name'];
                }
                $this->import[$item['master']][$item['model']] = $item;
                if ($item['images'] === '') {
                    $this->missingItems['missing_images'][$item['model']] = $item['name'];
                }
                if ($item['category'] === '') {
                    $this->missingItems['missing_categories'][$item['model']] = $item['name'];
                }
                $i++;
            }
        }
        foreach ($description as $desc => &$entry) {
            $entry['description'] = preg_replace('~\n~', '', $desc);
        }
        $description = array_values($description);
        ($this->reporter)([ 'imDescriptions' => $description]);

        ($this->reporter)(['imData' => $this->import]);
    }

    protected function getParamString($value)
    {
        if (is_string($value)) {
            return trim($value);
        }
        if ($value === null || is_array($value)) {
            return '';
        }
        throw new InvalidArgumentException(
            sprintf('String or empty array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function condenseOptions(array $attributes)
    {
        $options = [];
        foreach ($attributes as $group => $option) {
            $option = trim($option);
            $options[] = trim($group) . MXC_DELIMITER_L1 . $option;
            $this->optionNames[$option] = true;
        }
        sort($options);
        return implode(MXC_DELIMITER_L2, $options);
    }

    protected function condenseImages(?string $image, array $addlImages)
    {
        $images = [];
        if (is_string($image) && $image !== '') {
            $images[] = $image;
        }
        if (!empty($addlImages)) {
            sort($addlImages);
            $images[] = implode(MXC_DELIMITER_L1, $addlImages);
        }
        return implode(MXC_DELIMITER_L1, $images);
    }

    protected function getParamArray($value)
    {
        if (null === $value) {
            return [];
        }
        if (is_string($value)) {
            return [$value];
        }
        if (is_array($value)) {
            return $value;
        }
        throw new InvalidArgumentException(
            sprintf('String or array expected, got %s.',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    protected function createModels()
    {
        $limit = $this->config->get('limit', -1);
        $cursor = 0;
        $missingAttributes = [];
        $missingModels = [];
        foreach ($this->import as $master => $records) {
            if ($cursor === $limit) {
                return;
            }
            $cursor++;
            $options = [];
            $models = [];
            foreach ($records as $number => $data) {
                $model = $this->importLog['deletions'][$number];
                if (null !== $model) {
                    /** @var Model $model */
                    $model = $this->importLog['deletions'][$number];
                    unset($this->importLog['deletions'][$number]);
                } else {
                    $model = new Model();
                    $this->importLog['additions'][$number] = $model;
                    $this->modelManager->persist($model);
                }
                $model->fromImport($data);
                $models[$model->getModel()] = $model;
                $options[$model->getOptions()] = true;
                $category = $data['category'];
                $this->categoryUsage[$category][] = $model->getName();
                $this->categories[$category] = true;
            }
            // The option strings of all models with the same master id
            // must be different, otherwise attributes are missing
            if (count($records) !== count($options)) {
                $record = $this->checkMissingAttributes($records, $models);
                $missingAttributes[$master] = $record;
            }
            $issue = $this->checkMissingModels($records, $models);
            if (! empty($issue)) {
                $missingModels[$master] = $issue;
            }
        }
        ($this->reporter)([
            'imMissingAttributes' => $missingAttributes,
            'imMissingModels'     => $missingModels,
        ]);
    }

    /**
     * @param array $records
     * @param array $models
     * @return array
     */
    protected function checkMissingAttributes(array $records, array $models): array
    {
        $record = [];
        foreach ($records as $number => $data) {
            $record[$number] = [
                'name'       => $data['name'],
                'attributes' => $data['options'],
                'fixed'      => false,
            ];
            $fix = $this->config['attribute_fixes'][$data['master']][$number]['attributes'];
            if ($fix !== null) {
                $models[$number]->setOptions($fix);
                $record[$number]['fixed'] = $fix;
            }
        }
        return $record;
    }

    protected function checkMissingModels(array $records, array $models)
    {
        $groups = [];
        foreach ($records as $number => $data) {
            $model = $models[$number];
            $options = $model->getOptions();
            $options = explode(MXC_DELIMITER_L2, $options);
            foreach ($options as $fullOption) {
                list ($group, $option) = explode(MXC_DELIMITER_L1, $fullOption);
                $groups[$group][$option] = $fullOption;
            }
        }

        ksort($groups);
        foreach ($groups as $name => $options) {
            sort($options);
            $groups[$name] = array_values($options);
        }

        $product = ArrayTool::cartesianProduct($groups);
        $nrModelsExpected = count($product);
        $nrModelsDelivered = count($records);

        $record = [];
        if ($nrModelsDelivered != $nrModelsExpected) {
            $record = [
                'models_delivered' => $nrModelsDelivered,
                'models_expected'  => $nrModelsExpected,
            ];

            $set = [];
            foreach ($product as $value) {
                $set[implode(MXC_DELIMITER_L2, $value)] = true;
            }

            foreach ($records as $number => $data) {
                $options = $models[$number]->getOptions();
                $record['models'][$number] = [
                    'name'    => $data['name'],
                    'options' => $options,
                ];
                unset($set[$options]);
            }
            $record['models_missing'] = array_keys($set);
        }
        return $record;
    }

    protected function deleteModels()
    {
        /**
         * @var string $number
         * @var Model $model
         */
        foreach ($this->importLog['deletions'] as $number => $model) {
            $model->setDeleted(true);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var PreUpdateEventArgs $args */
        $model = $args->getEntity();
        if (!$model instanceof Model) {
            return;
        }

        $number = $model->getModel();
        $fields = [];
        foreach ($this->fields as $field) {
            if ($args->hasChangedField($field)) {
                $fields[$field] = [
                    'oldValue' => $args->getOldValue($field),
                    'newValue' => $args->getNewValue($field)
                ];
            }
        }
        if (!empty($fields)) {
            $this->importLog['changes'][$number] = [
                'model'  => $model,
                'fields' => $fields,
            ];
        }
    }
}
