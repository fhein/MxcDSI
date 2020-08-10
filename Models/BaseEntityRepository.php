<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipIntegrator\Models;


use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Enlight_Hook;
use MxcDropshipIntegrator\MxcDropshipIntegrator;
use MxcCommons\Log\LoggerInterface;

class BaseEntityRepository extends EntityRepository implements Enlight_Hook
{
    /** @var LoggerInterface */
    protected $log;

    /** @var array */
    protected $dql = [];

    /** @var array */
    protected $sql = [];

    /** @var array */
    protected $queries = [];

    /** @var array */
    protected $statements = [];

    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->log = MxcDropshipIntegrator::getServices()->get('logger');
    }

    public function __call($method, $arguments)
    {
        switch (true) {
            case (null !== @$this->dql[$method]):
                $query = $this->getQuery($method);
                return $query->getResult();
            case (null !== @$this->sql[$method]):
                return $this->getStatement($method)->execute();
            default:
                return parent::__call($method, $arguments);
        }
    }

    protected function getQuery(string $name) : ?Query
    {
        if (! isset($this->queries[$name])) {
            $this->queries[$name] = $this->getEntityManager()->createQuery($this->dql[$name]);
        }
        return $this->queries[$name];
    }

    protected function getStatement(string $name) : ?Statement
    {
        if (! isset($this->statements[$name])) {
            $this->statements[$name] = $this->getEntityManager()->getConnection()->prepare($this->sql[$name]);
        }
        return $this->statements[$name];

    }
}