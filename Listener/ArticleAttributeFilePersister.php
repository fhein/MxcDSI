<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace MxcDropshipInnocigs\Listener;


use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\PropertyMapper;
use Zend\EventManager\EventInterface;

class ArticleAttributeFilePersister extends ActionListener
{
    /** @var PropertyMapper $propertyMapper */
    protected $propertyMapper;

    /**
     * ArticleAttributeFilePersister constructor.
     * @param PropertyMapper $propertyMapper
     * @param LoggerInterface $log
     */
    public function __construct(PropertyMapper $propertyMapper, LoggerInterface $log)
    {
        //parent::__construct([], $log);
        $this->log = $log;
        $this->propertyMapper = $propertyMapper;
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e)
    {
        $this->log->enter();
        $this->propertyMapper->savePropertyMappings();
        $this->log->leave();
    }
}