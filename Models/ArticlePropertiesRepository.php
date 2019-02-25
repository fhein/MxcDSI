<?php

namespace MxcDropshipInnocigs\Models;

class ArticlePropertiesRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'  => 'SELECT a FROM MxcDropshipInnocigs\Models\ArticleProperties a INDEX BY a.icNumber',
        'removeOrphaned' => 'SELECT a FROM MxcDropshipInnocigs\Models\ArticleProperties a WHERE a.article is empty',
    ];

    public function getAllIndexed()
    {
        return $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
    }

    public function removeOrphaned()
    {
        $orphans = $this->getEntityManager()->createQuery($this->dql[__FUNCTION__])->getResult();
        /** @var Article $orphan */
        foreach ($orphans as $orphan) {
            $this->log->debug('Removing orphaned article properties\'' . $orphan->getName() . '\'');
            $this->getEntityManager()->remove($orphan);
        }
    }
}
