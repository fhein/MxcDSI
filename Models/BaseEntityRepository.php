<?php

namespace MxcDropshipInnocigs\Models;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mxc\Shopware\Plugin\Service\ServicesTrait;
use Zend\Log\LoggerInterface;

class BaseEntityRepository extends EntityRepository
{
    use ServicesTrait;

    /** @var LoggerInterface */
    protected $log;

    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->log = $this->getServices()->get('logger');
    }

    public function count(): int
    {
        $dql = sprintf('SELECT count(c.id) FROM %s c', $this->getClassName());
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getEntityManager()->createQuery($dql)->getSingleScalarResult();
    }
}