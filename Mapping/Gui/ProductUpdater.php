<?php

namespace MxcDropshipInnocigs\Mapping\Gui;

use Enlight_Controller_Request_Request as Request;
use Mxc\Shopware\Plugin\Service\LoggerAwareInterface;
use Mxc\Shopware\Plugin\Service\LoggerAwareTrait;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareInterface;
use Mxc\Shopware\Plugin\Service\ModelManagerAwareTrait;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;

class ProductUpdater implements LoggerAwareInterface, ModelManagerAwareInterface
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var array */
    protected $supportedStateProperties = ['active', 'accepted', 'linked'];

    /** @var ProductMapper */
    protected $productMapper;

    /** @var ProductRepository */
    protected $repository;

    public function __construct(ProductMapper $productMapper)
    {
        $this->productMapper = $productMapper;
    }

    public function setStateOnSelectedProducts(Request $request)
    {
        $params = $request->getParams();

        $property = $params['field'];
        if (! in_array($property, $this->supportedStateProperties)) {
            return [ 'success' => false, 'message' => 'State property not supported: ' . $property];
        }
        $value = $params['value'] === 'true';
        $ids = json_decode($params['ids'], true);

        $repository = $this->getRepository();
        $repository->setStateByIds($property, $value, $ids);
        $products = $repository->getByIds($ids);

        switch ($property) {
            case 'accepted':
                $this->productMapper->setArticleAcceptedState($products, $value);
                break;
            case 'linked':
            case 'active':
                $this->productMapper->processStateChangesProductList($products, true);
                break;
        }

        $msg = sprintf('Product %s states were successfully updated.', $property);
        return ['success' => true, 'message' => $msg];
    }

    public function updateProductStates(Product $product, array $data)
    {
        $updates = $this->getStateUpdates($product, $data);
        return $this->processStateUpdates($product, $updates);
    }

    protected function getStateUpdates(Product $product, array $data) {
        $updates = [];

        foreach ($this->supportedStateProperties as $property) {
            $getState = 'is' . ucfirst($property);
            if ($product->$getState() === $data[$property]) continue;
            $updates[$property] = $data[$property];
        }
        return $updates;
    }

    public function processStateUpdates(Product $product, array $updates)
    {
        foreach ($updates as $property => $value) {
            switch ($property) {
                case 'accepted':
                    $this->productMapper->setArticleAcceptedState([$product], $product->isAccepted());
                    break;
                default:
                    $this->productMapper->processStateChangesArticle($product, true);
                    break;
            }
            $getState = 'is' . ucFirst($property);
            if ($product->$getState() === $value) {
                continue;
            }
            $message = sprintf("Failed to set article's %s state to %s.", $property, var_export($value, true));
            return ['success' => false, 'message' => $message];
        }
        return true;
    }

    protected function getRepository()
    {
        return $this->repository ?? $this->repository = $this->modelManager->getRepository(Product::class);
    }
}