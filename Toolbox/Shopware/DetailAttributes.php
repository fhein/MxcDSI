<?php


namespace MxcDropshipIntegrator\Toolbox\Shopware;

use Shopware\Models\Article\Article;

class Attributes
{
    const ARTICLE = 0;
    const ARTICLEDETAIL = 1;
    const ORDER = 2;
    const ORDERDETAIL = 3;

    protected $tables = [
        Article::class => 's_articles_attributes',
        Detail:: => 's_articles_attributes',
        self::ORDER => 's_order_attributes',
        self::ORDERDETAIL => 's_order_detail_attributes'
    ];

    public static function get($$attr = null)
    {
        switch (getType($attr)) {
            case 'NULL':
                $selector = '*';
                break;
            case 'string':
                $selector = $attr;
                break;
            case 'array':
                $selector = implode(', ', $attr);
                break;
            default:
                return false;
        }
        $sql = 'SELECT'
    }


}