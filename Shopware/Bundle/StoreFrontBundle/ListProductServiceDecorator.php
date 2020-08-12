<?php /** @noinspection PhpDeprecationInspection */

namespace MxcDropshipIntegrator\Shopware\Bundle\StoreFrontBundle;

use Exception;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;
use Shopware\Bundle\StoreFrontBundle\Struct\ListProduct;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContextInterface;

class ListProductServiceDecorator implements ListProductServiceInterface
{
    private $parent;

    public function __construct(ListProductServiceInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * To get detailed information about the selection conditions, structure and content of the returned object,
     * please refer to the linked classes.
     *
     * @param array $numbers
     * @param ProductContextInterface $context
     * @return ListProduct[] indexed by the product order number
     * @throws Exception
     * @see \Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface::get()
     */
    public function getList(array $numbers, ProductContextInterface $context)
    {
        $articles = $this->parent->getList($numbers, $context);

        /** @var Article $article */
        foreach ($articles as $article) {

            $details = ArticleTool::getArticleActiveDetailsArray($article->getId());

            $inStock = 0;
            foreach ($details as $detail) {
                $inStock += empty($detail['dc_ic_active']) ? $detail['instock'] : $detail['dc_ic_instock'];
                $releaseDate = $detail['releasedate'];
                if ($releaseDate !== null) {
                    $releaseDate = strtotime($releaseDate);
                    $releaseDate = date('d.m.Y', $releaseDate);
                }
            }
            /** @noinspection PhpUnhandledExceptionInspection */
            $article->addAttribute('mxc_in_stock', new Attribute(['in_stock' => $inStock]));
            $article->addAttribute('mxc_releasedate', new Attribute(['releasedate' => $releaseDate]));
        }

        return $articles;
    }

    /**
     * Returns a full \Shopware\Bundle\StoreFrontBundle\Struct\ListProduct object.
     * A list product contains all required data to display products in small views like listings, sliders or emotions.
     *
     * @param string $number
     *
     * @param ProductContextInterface $context
     * @return ListProduct|null
     */
    public function get($number, ProductContextInterface $context)
    {
        return $this->parent->get($number, $context);
    }
}