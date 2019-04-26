<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelImport;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Gui\ProductUpdater;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Models\Article\Article;

class Shopware_Controllers_Backend_MxcDsiProduct extends BackendApplicationController
{
    protected $model = Product::class;
    protected $alias = 'product';

    public function indexAction() {
        $this->log->enter();
        try {
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function updateAction()
    {
        $this->log->enter();
        try {
            parent::updateAction();
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function importAction()
    {
        $this->log->enter();
        try {
            $client = $this->services->get(ImportClient::class);
            $client->import();
            $this->view->assign([ 'success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
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
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function exportConfigAction()
    {
        try {
            $modelManager = $this->getModelManager();
            $modelManager->getRepository(Product::class)->exportMappedProperties();
            $this->view->assign([ 'success' => true, 'message' => 'Product configuration was successfully exported.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function excelExportAction()
    {
        $this->log->enter();
        try {
            $excel = $this->services->get(ExcelExport::class);
            $excel->export();
            $this->view->assign([ 'success' => true, 'message' => 'Settings successfully exported to Config/vapee.export.xlsx.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function excelImportAction()
    {
        $this->log->enter();
        try {
            $excel = $this->services->get(ExcelImport::class);
            $excel->import();
            $this->view->assign([ 'success' => true, 'message' => 'Settings successfully imported from Config/vapee.export.xlsx.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function setStateSelectedAction()
    {
        $this->log->enter();
        try {
            $productUpdater = $this->services->get(ProductUpdater::class);
            $this->view->assign($productUpdater->setStateOnSelectedProducts($this->request));
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function checkRegularExpressionsAction()
    {
        $this->log->enter();
        try {
            $regularExpressions = $this->services->get(RegularExpressions::class);
            if (! $regularExpressions->check()) {
                $this->view->assign(['success' => false, 'message' => 'Errors found in regular expressions. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No errors found in regular expressions.']);
            }
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function checkNameMappingConsistencyAction()
    {
        $this->log->enter();
        try {
            $nameMappingConsistency = $this->services->get(NameMappingConsistency::class);
            $issueCount = $nameMappingConsistency->check();
            if ($issueCount > 0) {
                $issue = 'issue';
                if ($issueCount > 1) $issue .= 's';
                $this->view->assign(['success' => false, 'message' => 'Found ' . $issueCount . ' name mapping ' . $issue  . '. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No name mapping issues found.']);
            }

        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function remapAction()
    {
        $this->log->enter();
        try {
            /** @var ImportMapper $client */
            $propertyMapper = $this->services->get(PropertyMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $articles = $this->getModelManager()->getRepository(Product::class)->getAllIndexed();
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function remapSelectedAction() {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $articles = $this->getModelManager()->getRepository(Product::class)->getByIds($ids);
            $propertyMapper = $this->services->get(PropertyMapper::class);
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    protected function getAdditionalDetailData(array $data) {
        $data['variants'] = [];
        return $data;
    }

    public function save($data) {
        if (! empty($data['id'])) {
            // this is a request to update an existing product
            $product = $this->getRepository()->find($data['id']);
        } else {
            // this is a request to create a new product (not supported via our UI)
            $product = new $this->model();
            $this->getManager()->persist($product);
        }

        // Variant data is empty only if the request comes from the list view (not the detail view)
        // We prevent storing an article with empty variant list by unsetting empty variant data.
        if (isset($data['variants']) && empty($data['variants'])) {
            unset($data['variants']);
        }

        // hydrate (new or existing) article from UI data
        $data = $this->resolveExtJsData($data);
        unset($data['relatedProducts']);
        unset($data['similarProducts']);
        $product->fromArray($data);

        /** @var Product $product */
        $result = $this->services->get(ProductUpdater::class)->updateProductStates($product, $data);
        if ($result !== true) return $result;

        // Our customization ends here.
        // The rest below is default Shopware behaviour copied from parent implementation
        $violations = $this->getManager()->validate($product);
        $errors = [];
        /** @var Symfony\Component\Validator\ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'message' => $violation->getMessage(),
                'property' => $violation->getPropertyPath(),
            ];
        }

        if (!empty($errors)) {
            return ['success' => false, 'violations' => $errors];
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getManager()->flush();

        // The user may have changed the accepted state of variants to false in the detail view of an product.
        // So we need to check and remove invalid variants when the detail view gets saved.
        $this->services->get(DetailMapper::class)->deleteInvalidDetails([$product]);

        $detail = $this->getDetail($product->getId());

        return ['success' => true, 'data' => $detail['data']];
    }

    public function testImport1Action()
    {
        $this->log->enter();
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
            $this->services->get(ImportClient::class)->importFromFile($xmlFile, true);

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $articleMapper = $this->services->get(ProductMapper::class);
            $articleMapper->processStateChangesProductList($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport2Action()
    {
        $this->log->enter();
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateFeldwerte.xml';
            $this->services->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Values successfully updated.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport3Action()
    {
        $this->log->enter();
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateVarianten.xml';
            $this->services->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Variants successfully updated.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport4Action()
    {
        $this->log->enter();
        try {
            $xml = '<?xml version="1.0" encoding="utf-8"?><INNOCIGS_API_RESPONSE><PRODUCTS></PRODUCTS></INNOCIGS_API_RESPONSE>';
            $this->services->get(ImportClient::class)->importFromXml($xml);;
            $this->view->assign([ 'success' => true, 'message' => 'Empty list successfully imported.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }
    public function dev1Action()
    {
        $this->log->enter();
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $missingFlavors = $this->getRepository()->getProductsWithFlavorMissing();
            (new ArrayReport())(['pmMissingFlavors' => $missingFlavors]);

            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev2Action()
    {
        $this->log->enter();
        try {
            /** @var Product $product */
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getAllIndexed();
            foreach ($products as $product) {
                if ($product->getRetailPriceOthers() === '-') $product->setRetailPriceOthers(null);
                if ($product->getRetailPriceDampfplanet() === '-') $product->setRetailPriceDampfPlanet(null);
                $variants = $product->getVariants();
                foreach ($variants as $variant) {

                }
            }
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Retail prices cleaned up.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev3Action()
    {
        $this->log->enter();
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 3 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev4Action()
    {
        $this->log->enter();
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 4 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev5Action() {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 5 slot is currently free.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function dev6Action() {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 6 slot is currently free.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function dev7Action() {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 7 slot is currently free.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }
    public function dev8Action() {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 8 slot is currently free.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    protected function getRepository() : ProductRepository
    {
        return $this->getManager()->getRepository(Product::class);
    }

}
