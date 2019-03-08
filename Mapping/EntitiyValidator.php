<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Models\Article;
use MxcDropshipInnocigs\Models\Option;
use MxcDropshipInnocigs\Models\Variant;

class EntitiyValidator
{
    /**
     * An article validates true if either it's $accepted member is true
     * and at least one of the article's variants validates true
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
     * A variant validates true if the $accepted member of the variant is true and
     * the $accepted member of the associated Article is true and all of the variant's
     * options validate true
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
            if (! $this->validateOption($option)) {
                return false;
            }
        }
        return true;
    }

    /**
     * An option validates true if either it's $accepted member is true
     * or the $accepted member of the associated group is true.
     *
     * @param Option $option
     * @return bool
     */
    public function validateOption(Option $option) : bool {
        return ($option->isAccepted() && $option->getIcGroup()->isAccepted());
    }
}