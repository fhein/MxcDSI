<?php

namespace MxcDropshipInnocigs\Models;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Mxc\Shopware\Plugin\Service\ServicesTrait;
use Zend\Log\LoggerInterface;

class BaseEntityRepository extends EntityRepository
{
    use ServicesTrait;

    /** @var LoggerInterface */
    protected $log;

    /** @var EntityValidator $entityValidator */
    static protected $entityValidator;

    protected $dql;
    protected $queries;

    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $services = $this->getServices();
        $this->log = $services->get('logger');
    }

    public function count(): int
    {
        $dql = sprintf('SELECT count(c.id) FROM %s c', $this->getClassName());
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->getEntityManager()->createQuery($dql)->getSingleScalarResult();
    }

    protected function getQuery(string $name) : ?Query
    {
        if (! isset($this->queries[$name])) {
            $this->queries[$name] = $this->getEntityManager()->createQuery($this->dql[$name]);
        }
        return $this->queries[$name];
    }
}