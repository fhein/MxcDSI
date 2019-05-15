<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelProductImport;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Gui\ProductStateUpdater;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Shopware\DetailMapper;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Report\ArrayReport;
use Shopware\Models\Article\Article;
use Shopware\Components\CSRFWhitelistAware;
use Symfony\Component\HttpFoundation\FileBag;
use Shopware\Components\SwagImportExport\UploadPathProvider;

class Shopware_Controllers_Backend_MxcDsiProduct extends BackendApplicationController implements CSRFWhitelistAware
{
    protected $model = Product::class;
    protected $alias = 'product';

    public function getWhitelistedCSRFActions()
    {
        return [
            'excelExport',
        ];
    }

    public function postDispatch()
    {
       /* if ($this->Request()->getActionName() !== 'excelExport') {
            parent::postDispatch();
        }*/
    }

    public function indexAction() {
        $log = $this->getLog();
        $log->enter();
        try {
            parent::indexAction();
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $log->leave();
    }

    public function updateAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            parent::updateAction();
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function importAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $client = $this->getServices()->get(ImportClient::class);
            $client->import();
            $this->view->assign([ 'success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $log->leave();
    }

    public function refreshAction() {
        $log = $this->getLog();
        try {
            $modelManager = $this->getModelManager();
            /** @noinspection PhpUndefinedMethodInspection */
            if ($modelManager->getRepository(Product::class)->refreshLinks()) {
                $this->view->assign([ 'success' => true, 'message' => 'Product links were successfully updated.']);
            } else {
                $this->view->assign([ 'success' => false, 'message' => 'Failed to update product links.']);
            };
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $log->leave();
    }

    public function exportConfigAction()
    {
        $log = $this->getLog();
        try {
            $modelManager = $this->getModelManager();
            $modelManager->getRepository(Product::class)->exportMappedProperties();
            $this->view->assign([ 'success' => true, 'message' => 'Product configuration was successfully exported.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $log->leave();
    }

    public function excelExportAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            //necessary for correct download file
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
            $this->Front()->Plugins()->Json()->setRenderer(false);

            $excel = $this->getServices()->get(ExcelExport::class);
            $excel->export();

            $filepath = $excel->getExcelFile();
            $batchfile = file_get_contents($filepath);
            $size = filesize($filepath);

            $response = $this->Response();
            $response->setHeader('Cache-Control', 'must-revalidate');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . 'vapee.export.xlsx');
            $response->setHeader('Content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->setHeader('Content-Transfer-Encoding', 'binary');
            $response->setHeader('Content-Length', $size);
            $response->setHeader('Pragma', 'public');

            echo $batchfile;

        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function excelImportAction()
    {
        $log = $this->getLog();
        $log->enter();

        // Try to get the transferred file
        try {
            $file = $_FILES['file'];

            if ($file === null) $log->debug('file is null');
            $log->debug('filename: ' . $file['name']);
            $fileName = $file['name'];
            $tmpName = $_FILES['file']['tmp_name'];

            $fileBag = new FileBag($_FILES);

            /** @var UploadedFile $file */
            $file = $fileBag->get('file');
            $fileNamePos= strrpos ($tmpName, '/');
            $tmpPath= substr($tmpName, 0, $fileNamePos);
            $newFilePath = $tmpPath.'/' . $fileName; //'/../Config/' . $file['originalName'];
            $moveResult = move_uploaded_file($tmpName, $newFilePath);

            $excel = $this->getServices()->get(ExcelProductImport::class);
            $excel->import($newFilePath);
            $this->view->assign([ 'success' => true, 'message' => 'Settings successfully imported from Config/vapee.export.xlsx.' ]);

        } catch (Exception $e) {
            $log->except($e, true, false);
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
            //$this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        if ($file === null) {
            die(json_encode(['success' => false]));
        }

        $log->leave();

    }

    public function setStateSelectedAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $productUpdater = $this->getServices()->get(ProductStateUpdater::class);
            $this->view->assign($productUpdater->setStateOnSelectedProducts($this->request));
        } catch (Throwable $e) {
            $log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function checkRegularExpressionsAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $regularExpressions = $this->getServices()->get(RegularExpressions::class);
            if (! $regularExpressions->check()) {
                $this->view->assign(['success' => false, 'message' => 'Errors found in regular expressions. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No errors found in regular expressions.']);
            }
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function checkNameMappingConsistencyAction()
    {
        $log = $this->getLog();
        $log->enter();
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
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function remapAction()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            /** @var ImportMapper $client */
            $propertyMapper = $this->getServices()->get(PropertyMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $articles = $this->getModelManager()->getRepository(Product::class)->getAllIndexed();
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function remapSelectedAction() {
        $log = $this->getLog();
        $log->enter();
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $articles = $this->getModelManager()->getRepository(Product::class)->getByIds($ids);
            $propertyMapper = $this->getServices()->get(PropertyMapper::class);
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
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
        $result = $this->getServices()->get(ProductStateUpdater::class)->updateProductStates($product, $data);
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
        $this->getServices()->get(DetailMapper::class)->deleteInvalidDetails([$product]);

        $detail = $this->getDetail($product->getId());

        return ['success' => true, 'data' => $detail['data']];
    }

    public function testImport1Action()
    {
        $log = $this->getLog();
        $log->enter();
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
            $articleMapper = $this->services->get(ProductMapper::class);
            $articleMapper->processStateChangesProductList($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport2Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateFeldwerte.xml';
            $this->getServices()->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Values successfully updated.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport3Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $testDir = __DIR__ . '/../../Test/';
            $xmlFile = $testDir . 'TESTUpdateVarianten.xml';
            $this->getServices()->get(ImportClient::class)->importFromFile($xmlFile);;
            $this->view->assign([ 'success' => true, 'message' => 'Variants successfully updated.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function testImport4Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $xml = '<?xml version="1.0" encoding="utf-8"?><INNOCIGS_API_RESPONSE><PRODUCTS></PRODUCTS></INNOCIGS_API_RESPONSE>';
            $this->getServices()->get(ImportClient::class)->importFromXml($xml);;
            $this->view->assign([ 'success' => true, 'message' => 'Empty list successfully imported.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }
    public function dev1Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $missingFlavors = $this->getRepository()->getProductsWithFlavorMissing();
            (new ArrayReport())(['pmMissingFlavors' => $missingFlavors]);

            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev2Action()
    {
        $log = $this->getLog();
        $log->enter();
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
            $log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev3Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 3 slot is currently free.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev4Action()
    {
        $log = $this->getLog();
        $log->enter();
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 4 slot is currently free.' ]);
        } catch (Throwable $e) {
            $log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev5Action() {
        $log = $this->getLog();
        $log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 5 slot is currently free.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function dev6Action() {
        $log = $this->getLog();
        $log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 6 slot is currently free.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function dev7Action() {
        $log = $this->getLog();
        $log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 7 slot is currently free.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    public function dev8Action() {
        $log = $this->getLog();
        $log->enter();
        try {
            $params = $this->request->getParams();
            /** @noinspection PhpUnusedLocalVariableInspection */
            $ids = json_decode($params['ids'], true);
            // Do something with the ids
            $this->view->assign([ 'success' => true, 'message' => 'Development 8 slot is currently free.']);
        } catch (Throwable $e) {
            $log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $log->leave();
    }

    protected function getRepository() : ProductRepository
    {
        return $this->getManager()->getRepository(Product::class);
    }

}
