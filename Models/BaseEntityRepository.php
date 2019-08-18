<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Models;


use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Zend\Log\LoggerInterface;

class BaseEntityRepository extends EntityRepository
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
        $this->log = MxcDropshipInnocigs::getServices()->get('logger');
    }

    public function __call($method, $arguments)
    {
        switch (true) {
            case (null !== @$this->dql[$method]):
                $query = $this->getQuery($method);
                $result = $query->getResult();
                return $result;
            case (null !== @$this->sql[$method]):
                return $this->getStatement($method)->execute();
            default:
                return parent::__call($method, $arguments);
        }
    }

    public function count(): int
    {
        $dql = sprintf('SELECT count(c.id) FROM %s c', $this->getClassName());
        return $this->getEntityManager()->createQuery($dql)->getSingleScalarResult();
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