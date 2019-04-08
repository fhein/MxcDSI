<?php

namespace MxcDropshipInnocigs\Models;

class ImageRepository extends BaseEntityRepository
{
    protected $dql = [
        'getAllIndexed'     => 'SELECT i FROM MxcDropshipInnocigs\Models\Image i INDEX BY i.url',
        'removeOrphaned'    => 'DELETE MxcDropshipInnocigs\Models\Image i WHERE i.variants is empty',
    ];

    public function removeOrphaned() {
        return $this->getQuery(__FUNCTION__)->execute();
    }

}
