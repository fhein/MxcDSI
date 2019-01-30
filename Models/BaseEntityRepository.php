<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 22.01.2019
 * Time: 17:43
 */

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
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('a')->select('count(a.id)')->getQuery()->getSingleScalarResult();
    }
}