<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace MxcDropshipInnocigs\Listener;


use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Models\Product;
use Shopware\Components\Model\ModelManager;
use Zend\EventManager\EventInterface;

class MappingFilePersister extends ActionListener
{
    /** @var ModelManager $modelManager */
    protected $modelManager;

    protected $log;

    /**
     * MappingFilePersister constructor.
     *
     * @param ModelManager $modelManager
     * @param LoggerInterface $log
     */
    public function __construct(ModelManager $modelManager, LoggerInterface $log)
    {
        $this->log = $log;
        $this->modelManager = $modelManager;
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->log->enter();
        $repository = $this->modelManager->getRepository(Product::class);
        $repository->exportMappedProperties();
        $this->log->leave();
    }
}