<?php /** @noinspection PhpUnhandledExceptionInspection */

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelProductImport;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Models\Article\Article;

class Shopware_Controllers_Backend_MxcDsiProduct extends BackendApplicationController
{
    protected $model = Product::class;
    protected $alias = 'product';

    public function importAction()
    {
        try {
            $client = $this->getServices()->get(ImportClient::class);
            if (! $client->import()) {
                $this->view->assign(['success' => false, 'message' => 'Failed to import/update items.']);
                return;
            }
            $this->view->assign(['success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function refreshAction() {
        try {
            $modelManager = $this->getModelManager();
            /** @noinspection PhpUndefinedMethodInspection */
            if ($modelManager->getRepository(Product::class)->refreshLinks()) {
                $this->view->assign([ 'success' => true, 'message' => 'Product links were successfully updated.']);
            } else {
                $this->view->assign([ 'success' => false, 'message' => 'Failed to update product links.']);
            };
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateAssociatedProductsAction() {
        try {
            $modelManager = $this->getManager();
            /** @noinspection PhpUndefinedMethodInspection */
            $associatedProducts = $modelManager->getRepository(Product::class)->getLinkedProducts();

            $productMapper = $this->getServices()->get(ProductMapper::class);
            $productMapper->updateAssociatedProducts($associatedProducts);
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Associated products were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function exportConfigAction()
    {
        try {
            $modelManager = $this->getModelManager();
            $modelManager->getRepository(Product::class)->exportMappedProperties();
            $this->view->assign([ 'success' => true, 'message' => 'Product configuration was successfully exported.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function excelExportAction()
    {
        try {
            $excel = $this->getServices()->get(ExcelExport::class);
            $excel->export();
            $this->view->assign([ 'success' => true, 'message' => 'Settings successfully exported to Config/vapee.export.xlsx.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function excelImportAction()
    {
        try {
            $excel = $this->getServices()->get(ExcelProductImport::class);
            $excel->import();
            $this->view->assign([ 'success' => true, 'message' => 'Settings successfully imported from Config/vapee.export.xlsx.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    protected function setStatePropertyOnSelected()
    {
        $params = $this->request->getParams();
        $property = $params['field'];
        $value = $params['value'] === 'true';
        $ids = json_decode($params['ids'], true);
        $this->getRepository()->setStateByIds($property, $value, $ids);
        $products = $this->getRepository()->getByIds($ids);
        return [$value, $products];
    }

    public function linkSelectedProductsAction()
    {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $productMapper = $this->getServices()->get(ProductMapper::class);
            switch ($value) {
                case true:
                    $productMapper->controllerUpdateArticles($products, true);
                    $message = 'Products were successfully created.';
                    break;
                case false:
                    $productMapper->deleteArticles($products);
                    $message = 'Products were successfully deleted.';
                    break;
                default:
                    $message = 'Nothing done.';
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getRepository()->refreshLinks();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function acceptSelectedProductsAction() {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $productMapper = $this->getServices()->get(ProductMapper::class);
            switch ($value) {
                case true:
                    $productMapper->controllerUpdateArticles($products, false);
                    $message = 'Product states were successfully set to accepted.';
                    break;
                case false:
                    $productMapper->deleteArticles($products);
                    $message = 'Product states were successfully set to ignored '
                        . 'and associated Shopware products were deleted.';
                    break;
                default:
                    $message = 'Nothing done.';
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getRepository()->refreshLinks();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function activateSelectedProductsAction()
    {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $productMapper = $this->getServices()->get(ProductMapper::class);
            switch ($value) {
                case true:
                    $productMapper->controllerActivateArticles($products, true, true);
                    $message = 'Shopware products were successfully created and activated';
                    break;
                case false:
                    $productMapper->controllerActivateArticles($products, false, false);
                    $message = 'Shopware products were successfully deactivated.';
                    break;
                default:
                    $message = 'Nothing done.';
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getRepository()->refreshLinks();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function checkRegularExpressionsAction()
    {
        try {
            $regularExpressions = $this->getServices()->get(RegularExpressions::class);
            if (! $regularExpressions->check()) {
                $this->view->assign(['success' => false, 'message' => 'Errors found in regular expressions. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No errors found in regular expressions.']);
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function checkNameMappingConsistencyAction()
    {
        try {
            $nameMappingConsistency = $this->getServices()->get(NameMappingConsistency::class);
            $issueCount = $nameMappingConsistency->check();
            if ($issueCount > 0) {
                $issue = 'issue';
                if ($issueCount > 1) $issue .= 's';
                $this->view->assign(['success' => false, 'message' => 'Found ' . $issueCount . ' name mapping ' . $issue  . '. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No name mapping issues found.']);
            }

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapAction()
    {
        try {
            /** @var ImportMapper $client */
            $services = $this->getServices();
            $propertyMapper = $services->get(PropertyMapper::class);
            $categoryMapper = $services->get(CategoryMapper::class);
            $productMapper = $services->get(ProductMapper::class);
            $repository = $this->getModelManager()->getRepository(Product::class);

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $repository->getAllIndexed();
            $propertyMapper->mapProperties($products);
            $categoryMapper->buildCategoryTree();
            $this->getModelManager()->flush();

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $repository->getLinkedProducts();
            $productMapper->updateArticles($products, false);
            $this->getModelManager()->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapSelectedAction() {
        try {
            $services = $this->getServices();
            $propertyMapper = $services->get(PropertyMapper::class);
            $productMapper = $services->get(ProductMapper::class);
            $modelManager = $this->getManager();
            $repository = $modelManager->getRepository(Product::class);

            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);

            $products = $repository->getByIds($ids);
            $propertyMapper->mapProperties($products);
            $modelManager->flush();

            $products = $repository->getLinkedProductsFromProductIds($ids);
            $productMapper->updateArticles($products, false);
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport1Action()
    {
        try {
            $testDir = __DIR__ . '/../../Test/';
            $modelManager = $this->getManager();
            $modelManager->createQuery('DELETE MxcDropshipInnocigs\Models\Model ir')->execute();
            $articles = $modelManager->getRepository(Article::class)->findAll();
            $articleResource = new \Shopware\Components\Api\Resource\Article();
            $articleResource->setManager($modelManager);
            /** @var Article $article */
            foreach ($articles as $article) {
                $articleResource->delete($article->getId());
            }

            $xmlFile = $testDir . 'TESTErstimport.xml';
            $this->getServices()->get(ImportClient::class)->importFromFile($xmlFile, true);

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $productMapper = $this->services->get(ProductMapper::class);
            $productMapper->updateArticles($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport2Action()
    {
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateFeldwerte.xml';
            $this->getServices()->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Values successfully updated.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport3Action()
    {
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateVarianten.xml';
            $this->getServices()->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Variants successfully updated.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport4Action()
    {
        try {
            $xml = '<?xml version="1.0" encoding="utf-8"?><INNOCIGS_API_RESPONSE><PRODUCTS></PRODUCTS></INNOCIGS_API_RESPONSE>';
            $this->getServices()->get(ImportClient::class)->importFromXml($xml);;
            $this->view->assign([ 'success' => true, 'message' => 'Empty list successfully imported.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev1Action()
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $missingFlavors = $this->getRepository()->getProductsWithFlavorMissing();
            (new ArrayReport())(['pmMissingFlavors' => $missingFlavors]);

            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev2Action()
    {
        try {
            /** @var Product $product */
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getAllIndexed();
            foreach ($products as $product) {
                if ($product->getRetailPriceOthers() === '-') $product->setRetailPriceOthers(null);
                if ($product->getRetailPriceDampfplanet() === '-') $product->setRetailPriceDampfPlanet(null);
            }
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Retail prices cleaned up.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev3Action()
    {
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 3 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
    public function dev4Action()
    {
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 4 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev5Action() {
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 5 slot is currently free.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev6Action() {
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 6 slot is currently free.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev7Action() {
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 7 slot is currently free.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev8Action() {
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 8 slot is currently free.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    protected function getStateUpdates(array $data)
    {
        $product = isset($data['id']) ? $this->getRepository()->find($data['id']) : null;
        if (! $product) return [null, null];

        $changes = [];
        foreach (['accepted', 'active', 'linked'] as $property) {
            $getState = 'is' . ucfirst($property);
            if ($product->$getState() === $data[$property]) continue;
            $changes[$property] = $data[$property];
        }
        return [$product, $changes];
    }

    protected function updateProductStates(Product $product, array $changes)
    {
        $productMapper = $this->getServices()->get(ProductMapper::class);

        $change = $changes['linked'] ?? null;
        if ($change === false) {
            $productMapper->deleteArticles([$product]);
            return;
        } elseif ($change === true) {
            $productMapper->controllerUpdateArticles([$product], true);
        }

        $change = $changes['accepted'] ?? null;
        if ($change !== null) {
            $productMapper->acceptArticle($product, $change);
            if ($change === false) return;
        }

        $change = $changes['active'] ?? null;
        if ($change === true) {
            $productMapper->controllerActivateArticles([$product], true, true);
        } elseif ($change === false) {
            $productMapper->controllerActivateArticles([$product], false, false);
        }
    }

    public function save($data) {
        list($product, $changes) = $this->getStateUpdates($data);
        if (! $product) {
            return [ 'success' => false, 'message' => 'Creation of new products via GUI is not supported.'];
        }

        // Variant data is empty only if the request comes from the list view (not the detail view)
        // We prevent storing an article with empty variant list by unsetting empty variant data.
        $fromListView = isset($data['variants']) && empty($data['variants']);
        if ($fromListView) unset($data['variants']);

        // hydrate (new or existing) article from UI data
        $data = $this->resolveExtJsData($data);
        unset($data['relatedProducts']);
        unset($data['similarProducts']);
        $product->fromArray($data);
        $this->getManager()->flush();

        /** @var Product $product */
        $this->updateProductStates($product, $changes);

        // The user may have changed the accepted state of variants to false in the detail view of an product.
        // So we need to check and remove invalid variants when the detail view gets saved.
        if (! $fromListView) {
            $this->getServices()->get(ProductMapper::class)->updateArticle($product, false);
            $this->getManager()->flush();
        }

        $detail = $this->getDetail($product->getId());
        return ['success' => true, 'data' => $detail['data']];
    }

    protected function getRepository() : ProductRepository
    {
        return $this->getManager()->getRepository(Product::class);
    }

    protected function getAdditionalDetailData(array $data) {
        $data['variants'] = [];
        return $data;
    }
}
