<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Mapping;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use MxcCommons\Plugin\Database\SchemaManager;
use MxcCommons\Defines\Constants;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Arrays\ArrayTool;
use MxcDropshipIntegrator\Models\Model;
use MxcDropshipIntegrator\Models\Variant;
use MxcCommons\Toolbox\Report\ArrayReport;
use RuntimeException;
use MxcDropshipInnocigs\Api\ApiClient;

class ImportClient implements EventSubscriber, AugmentedObject
{
    use ClassConfigAwareTrait;
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var SchemaManager $schemaManager */
    protected $schemaManager;

    /** @var ApiClient $apiClient */
    protected $apiClient;

    /** @var array $import */
    protected $import;

    /** @var array */
    protected $changeLog;

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

    protected $variants;

    /** @var ArrayReport */
    protected $reporter;

    public function __construct(
        SchemaManager $schemaManager,
        ApiClient $apiClient
    ) {
        $this->schemaManager = $schemaManager;
        $this->apiClient = $apiClient;
        $this->reporter = new ArrayReport();
        $model = new Model();
        $this->fields = $model->getPrivatePropertyNames();
    }

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    protected function setupImport()
    {
        $this->changeLog = [];
        $repository = $this->modelManager->getRepository(Variant::class);
        $this->variants = $repository->getAllIndexed();
        $this->optionNames = [];
    }

    public function getSubscribedEvents()
    {
        return ['preUpdate'];
    }

    public function importFromApi(bool $includeDescriptions, bool $sequential, bool $recreateSchema = false) {
        $this->recreateSchema($recreateSchema);
        $this->import = $this->apiClient->getProducts(false, $includeDescriptions, $sequential);
        return $this->doImport();
    }

    public function importFromXml(string $xml, bool $sequential, bool $recreateSchema = false)
    {
        $this->recreateSchema($recreateSchema);
        $this->import = $sequential
            ? $this->apiClient->getXmlReader()->readModelsFromUri($xml, false)
            : $this->apiClient->getHttpReader()->readModelsFromXml($xml, false);

        return $this->doImport();
    }

    public function importFromFile(string $xmlFile, bool $sequential, bool $recreateSchema = false)
    {
        $this->recreateSchema($recreateSchema);

        if (! file_exists($xmlFile)) {
            throw new RuntimeException('File does not exist: ' . $xmlFile);
        }

        $this->import = $sequential
            ? $this->apiClient->getXmlReader()->readModelsFromUri($xmlFile, false)
            : $this->apiClient->getHttpReader()->readModelsFromXml(file_get_contents($xmlFile), false);

        return $this->doImport();
    }

    protected function doImport()
    {
        $this->setupImport();
        $evm = $this->modelManager->getEventManager();
        $evm->addEventSubscriber($this);
        $this->flattenImport();
        $this->updateModels();

        $evm->removeEventSubscriber($this);

        $this->reportMissingProperties();

        $this->logImport();
        return $this->changeLog;
    }

    protected function flattenImport()
    {
        $i = 1;
        foreach ($this->import as &$master) {
            foreach ($master as &$item) {
                $item['options'] = $this->flattenOptions($item['options']);
                if (! empty($item['images'])) {
                    $this->missingItems['additional_images_available'][$item['model']] = $item['name'];
                }
                $item['images'] = $this->flattenImages($item['image'], $item['images']);
                unset ($item['image']);

                $i++;
            }
        }
    }

    protected function flattenOptions(array $attributes = null)
    {
        if ($attributes === null) return null;
        $options = [];
        foreach ($attributes as $group => $option) {
            $option = trim($option);
            $options[] = trim($group) . Constants::DELIMITER_L1 . $option;
            $this->optionNames[$option] = true;
        }
        sort($options);
        return implode(Constants::DELIMITER_L2, $options);
    }

    protected function flattenImages(?string $image, array $addlImages)
    {
        $images = $addlImages;
        sort($images);
        if (is_string($image)) {
            array_unshift($images, $image);
        }
        // array_filter removes false strings (empty strings in this case)
        // arraykeys(array_flip) does the same as array_unique but is faster
        return implode(Constants::DELIMITER_L1, array_keys(array_flip(array_filter($images))));
    }

    protected function updateModels()
    {
        $limit = $this->classConfig['limit'] ??  -1;
        $cursor = 0;
        $missingAttributes = [];
        $missingModels = [];
        $repository = $this->modelManager->getRepository(Model::class);
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $deletions = $repository->getAllIndexed();

        foreach ($this->import as $master => $records) {
            if ($cursor === $limit) {
                return;
            }
            $cursor++;
            $options = [];
            $models = [];
            foreach ($records as $number => $data) {
                $model = @$deletions[$number];

                if (null === $model) {
                    $model = new Model();
                    $this->modelManager->persist($model);
                } else {
                    /** @var Model $model */
                    $model = $deletions[$number];
                    unset($deletions[$number]);
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

        foreach ($deletions as $model) {
            $this->modelManager->remove($model);
        }

        $this->modelManager->flush();

        ($this->reporter)([
            'imMissingAttributes' => $missingAttributes,
            'imMissingModels'     => $missingModels,
        ]);
    }

    protected function reportMissingProperties()
    {
        $i = 1;
        $description = [];
        foreach ($this->import as $master) {
            foreach ($master as $item) {
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

    protected function checkMissingAttributes(array $records, array $models): array
    {
        $record = [];
        foreach ($records as $number => $data) {
            $record[$number] = [
                'name'       => $data['name'],
                'attributes' => $data['options'],
                'fixed'      => false,
            ];
            $fix = $this->classConfig['attribute_fixes'][$data['master']][$number]['attributes'];
            if ($fix !== null) {
                $this->log->debug('Attribute fix applied.');
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
            $options = explode(Constants::DELIMITER_L2, $options);
            foreach ($options as $fullOption) {
                [$group, $option] = explode(Constants::DELIMITER_L1, $fullOption);
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
                $set[implode(Constants::DELIMITER_L2, $value)] = true;
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

    public function preUpdate(PreUpdateEventArgs $args)
    {
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
            $this->changeLog[$number] = [
                'model'  => $model,
                'fields' => $fields,
            ];
        }
    }

    protected function logImport()
    {
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
    }

    protected function recreateSchema(bool $recreateSchema) {
        if ($recreateSchema) {
            $this->schemaManager->drop();
            $this->schemaManager->create();
        }
    }
}
