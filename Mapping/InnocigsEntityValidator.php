<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Models\Current\Article;
use MxcDropshipInnocigs\Models\Current\Option;
use MxcDropshipInnocigs\Models\Current\Variant;

class InnocigsEntityValidator
{
    /**
     * An ImportArticle validates true if either it's accepted member is true
     * and at least one of it's variants validates true
     *
     * @param Article $article
     * @return bool
     */
    public function validateArticle(Article $article) : bool
    {
        if (! $article->isAccepted()) {
            return false;
        }
        $variants = $article->getVariants();
        foreach($variants as $variant) {
            if ($this->validateVariant($variant)) {
                return true;
            }
        }
        return false;
    }

    /**
     * An ImportVariant validates if either it's accepted member is true
     * and the accepted member of it's associated article is true,
     * and at least one of it's options has an accepted member which is true
     *
     * @param Variant $variant
     * @return bool
     */
    public function validateVariant(Variant $variant) : bool
    {
        if (! ($variant->isAccepted() && $variant->getArticle()->isAccepted())) {
            return false;
        }
        $options = $variant->getOptions();
        foreach ($options as $option) {
            if ($this->validateOption($option)) {
                return true;
            }
        }
        return false;
    }

    /**
     * An ImportOption is ignored if either it's ignored member is true
     * or the ignored member of the associated group is true.
     *
     * @param Option $option
     * @return bool
     */
    public function validateOption(Option $option) : bool {
        return ($option->isAccepted() && $option->getIcGroup()->isAccepted());
    }
}