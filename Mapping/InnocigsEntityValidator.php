<?php

namespace MxcDropshipInnocigs\Mapping;

use MxcDropshipInnocigs\Exception\InvalidArgumentException;
use MxcDropshipInnocigs\Models\InnocigsArticle;
use MxcDropshipInnocigs\Models\InnocigsGroup;
use MxcDropshipInnocigs\Models\InnocigsModelInterface;
use MxcDropshipInnocigs\Models\InnocigsOption;
use MxcDropshipInnocigs\Models\InnocigsVariant;

class InnocigsEntityValidator
{
    protected $validators = [
        InnocigsArticle::class => 'validateArticle',
        InnocigsVariant::class => 'validateVariant',
        InnocigsGroup::class => 'validateGroup',
        InnocigsOption::class => 'validateOption'
    ];

    /**
     * An InnocigsArticle validates true if either it's accepted member is true
     * and at least one of it's variants validates true
     *
     * @param InnocigsArticle $article
     * @return bool
     */
    public function validateArticle(InnocigsArticle $article) : bool
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
     * An InnocigsVariant validates if either it's accepted member is true
     * and the accepted member of it's associated article is true,
     * and at least one of it's options has an accepted member which is true
     *
     * @param InnocigsVariant $variant
     * @return bool
     */
    public function validateVariant(InnocigsVariant $variant) : bool
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
     * An InnocigsGroup validates if it's accepted member is true
     * and the number of associated options with accepted member = true
     * is greater than 1.
     *
     * @param InnocigsGroup $group
     * @return bool
     */
    public function validateGroup(InnocigsGroup $group) : bool
    {
        if (! $group->isAccepted()) {
            return false;
        }
        $options = $group->getOptions();
        $count = 0;
        foreach ($options as $option) {
            /**
             * @var InnocigsOption $option
             */
            if ($option->isAccepted()) {
                $count += 1;
                if ($count > 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * An InnocigsOption is ignored if either it's ignored member is true
     * or the ignored member of the associated group is true.
     *
     * @param InnocigsOption $option
     * @return bool
     */
    public function validateOption(InnocigsOption $option) : bool {
        return ($option->isAccepted() && $option->getGroup()->isAccepted());
    }

    public function validate(InnocigsModelInterface $entity) : bool {
        $method = $this->validators[get_class($entity)] ?? null;
        if (null === $method) {
            throw new InvalidArgumentException('No validator available for ' . get_class($entity));
        }
        return $this->$method($entity);
    }
}