<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 22.01.2019
 * Time: 17:43
 */

namespace MxcDropshipInnocigs\Models;


use Doctrine\ORM\EntityRepository;

class BaseEntityRepository extends EntityRepository
{
    public function count(): int
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return $this->createQueryBuilder('a')->select('count(a.id)')->getQuery()->getSingleScalarResult();
    }
}