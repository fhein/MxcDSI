<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Client\ApiClient;
use MxcDropshipInnocigs\Models\Import\Model;
use Shopware\Components\Model\ModelManager;
use Zend\Config\Config;

class ImportClient extends ImportBase implements EventSubscriber
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    /** @var ImportMapper $importMapper */
    protected $importMapper;

    /** @var array */
    protected $additions;

    /** @var array */
    protected $changes;

    /** @var array */
    protected $deletions;

    protected $importLog;

    /** @var array */
    protected $fields = [
        'master',
        'model',
        'ean',
        'name',
        'purchasePrice',
        'retailPrice',
        'imageUrl',
        'additionalImages',
        'manufacturer'
    ];

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
        parent::__construct($apiClient, $config, $log);
        $this->modelManager = $modelManager;
        $this->importMapper = $importMapper;
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

        $evm = $this->modelManager->getEventManager();
        $evm->addEventSubscriber($this);

        parent::import();
        $this->createModels();
        $this->modelManager->flush();
        $this->logImport();

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

    protected function createModels() {
        foreach ($this->import as $number => $data) {
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
        $model->setCategory($this->getParamString($data['CATEGORY']));
        $model->setMaster($data['MASTER']);
        $model->setModel($this->getParamString($data['MODEL']));
        $model->setEan($this->getParamString($data['EAN']));
        $model->setName($this->getParamString($data['NAME']));
        $model->setPurchasePrice($this->getParamString($data['PRODUCTS_PRICE']));
        $model->setRetailPrice($this->getParamString($data['PRODUCTS_PRICE_RECOMMENDED']));
        $model->setManufacturer($this->getParamString($data['MANUFACTURER']));
        $model->setImageUrl($this->getParamString($data['PRODUCTS_IMAGE']));
        $model->setAdditionalImages($data['PRODUCTS_IMAGE_ADDITIONAL']);
        $model->setOptions($data['PRODUCTS_ATTRIBUTES']);
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
}
