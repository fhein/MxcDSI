<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUnhandledExceptionInspection */

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelProductImport;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\UpdateStockCronJob;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Check\VariantMappingConsistency;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\CategoryTreeBuilder;
use MxcDropshipInnocigs\Mapping\Import\DescriptionMapper;
use MxcDropshipInnocigs\Mapping\Import\ProductSeoMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ImportPriceMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Pullback\DescriptionPullback;
use MxcDropshipInnocigs\Mapping\Shopware\CategoryMapper as ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Mapping\Shopware\PriceMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Models\Variant;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Toolbox\Shopware\SupplierTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Supplier;
use Zend\Config\Factory;

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

    public function importAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->import(true));
            $this->view->assign(['success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function importSequentialAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importSequential(true));
            $this->view->assign(['success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updatePricesAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportPriceMapper::class);
            $mapper->import($client->import(false));
            $this->view->assign(['success' => true, 'message' => 'Prices were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function refreshAction() {
        try {
            $modelManager = $this->getModelManager();
            /** @noinspection PhpUndefinedMethodInspection */
            $modelManager->getRepository(Product::class)->refreshProductStates();
            $this->view->assign([ 'success' => true, 'message' => 'Product links were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateAssociatedProductsAction()
    {
        try {
            $modelManager = $this->getManager();
            /** @noinspection PhpUndefinedMethodInspection */
            $associatedProducts = $modelManager->getRepository(Product::class)->getLinkedProductIds();
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
            $productMapper->updateAssociatedProducts($associatedProducts);
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Associated products were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateImagesAction()
    {
        try {
            $modelManager = $this->getManager();
            $services = MxcDropshipInnocigs::getServices();
            $imageMapper = $services->get(ImageMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getSelectedProducts($this->request);
            foreach ($products as $product) {
                $imageMapper->setArticleImages($product);
            }
            $modelManager->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Images were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapCategoriesAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $categoryMapper = $services->get(CategoryMapper::class);
            $products = $this->getSelectedProducts($this->request);
            /** @var Product $product */
            foreach ($products as $product) {
                $categoryMapper->remap($product);
                /** @var Article $article */
                $article = $product->getArticle();
                if ($article === null) continue;
                $article->getCategories()->clear();
            }
            $categoryMapper->report();
            $this->getManager()->flush();

            $categoryMapper = $services->get(ShopwareCategoryMapper::class);
            $categoryMapper->removeEmptyProductCategories();
            $this->getManager()->flush();

            foreach ($products as $product) {
                $categoryMapper->map($product, true);
                $this->getManager()->flush();
            }

            $this->view->assign([ 'success' => true, 'message' => 'Categories were successfully remapped.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function removeEmptyCategoriesAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $mapper = $services->get(ShopwareCategoryMapper::class);
            $count = $mapper->removeEmptyProductCategories();
            switch ($count) {
                case 0: $message = 'No empty categories found.'; break;
                case 1: $message = 'One empty category was successfully removed.'; break;
                default: $message = $count . ' empty categories were successfully removed.'; break;
            }
            $this->view->assign([ 'success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function buildCategoryTreeAction() {
        try {
            $this->view->assign([ 'success' => false, 'message' => 'Function was removed.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }

    }

    public function exportConfigAction()
    {
        try {
            $modelManager = $this->getModelManager();
            $modelManager->getRepository(Product::class)->exportMappedProperties();
            $modelManager->getRepository(Variant::class)->exportMappedProperties();
            $this->view->assign([ 'success' => true, 'message' => 'Product configuration was successfully exported.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function excelExportAction()
    {
        try {
            $this->get('plugins')->Controller()->ViewRenderer()->setNoRender();
            $this->Front()->Plugins()->Json()->setRenderer(false);
            $services = MxcDropshipInnocigs::getServices();
            $excel = $services->get(ExcelExport::class);
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
            $this->handleException($e);
        }
    }

    public function excelImportAction()
    {
        // Try to get the transferred file
        try {
            $file = $_FILES['file'];
            $services = MxcDropshipInnocigs::getServices();
            $log = $services->get('logger');

            if ($file === null) $log->debug('file is null');
            $fileName = $file['name'];
            $tmpName = $_FILES['file']['tmp_name'];
            $fileNamePos= strrpos ($tmpName, '/');
            $tmpPath= substr($tmpName, 0, $fileNamePos);
            $newFilePath = $tmpPath.'/' . $fileName; //'/../Config/' . $file['originalName'];
            move_uploaded_file($tmpName, $newFilePath);

            $services = MxcDropshipInnocigs::getServices();
            $excel = $services->get(ExcelProductImport::class);
            $result = $excel->import($newFilePath);

            unlink($newFilePath);
            if ($result){
                $this->view->assign([ 'success' => $result, 'message' => 'Settings successfully imported from ' . $fileName . '.' ]);
            }else{
                $this->view->assign([ 'success' => $result, 'message' => 'File ' . $fileName . ' could not be imported.' ]);
            }
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function excelImportPricesAction()
    {
        $this->excelImportSheet('Preise');
    }

    public function excelImportDescriptionsAction()
    {
        $this->excelImportSheet('Beschreibung');
    }

    public function excelImportFlavorsAction()
    {
        $this->excelImportSheet('Geschmack');
    }

    public function excelImportDosagesAction()
    {
        $this->excelImportSheet('Dosierung');
    }

    public function excelImportMappingsAction()
    {
        $this->excelImportSheet('Mapping');
    }

    protected function excelImportSheet(string $sheet)
    {
        // Try to get the transferred file
        try {
            $file = $_FILES['file'];
            $services = MxcDropshipInnocigs::getServices();
            $log = $services->get('logger');

            if ($file === null) $log->debug('file is null');
            $fileName = $file['name'];
            $tmpName = $_FILES['file']['tmp_name'];
            $fileNamePos= strrpos ($tmpName, '/');
            $tmpPath= substr($tmpName, 0, $fileNamePos);
            $newFilePath = $tmpPath.'/' . $fileName; //'/../Config/' . $file['originalName'];
            move_uploaded_file($tmpName, $newFilePath);

            $services = MxcDropshipInnocigs::getServices();
            $excel = $services->get(ExcelProductImport::class);
            $result = $excel->importSheet($sheet, $newFilePath);

            unlink($newFilePath);
            if ($result){
                $msg = [
                    'Preise'        => 'Prices',
                    'Dosierung'     => 'Dosages',
                    'Geschmack'     => 'Flavours',
                    'Beschreibung'  => 'Descriptions',
                    'Mapping'       => 'Mappings',
                ];
                $this->view->assign([ 'success' => $result, 'message' => $msg[$sheet] . ' successfully imported from ' . $fileName . '.' ]);
            }else{
                $this->view->assign([ 'success' => $result, 'message' => 'File ' . $fileName . ' could not be imported.' ]);
            }
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
        $products = $this->getRepository()->getProductsByIds($ids);
        return [$value, $products];
    }

    public function relinkProductsAction() {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $repository = $this->getRepository();
            $repository->setStateByIds('linked', false, $ids);
            $products = $this->getRepository()->getProductsByIds($ids);

            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
            $productMapper->deleteArticles($products);

            $repository->setStateByIds('linked', true, $ids);
            $productMapper->controllerUpdateArticles($products, true);

            $this->getRepository()->refreshProductStates();
            $this->view->assign(['success' => true, 'message' => 'Articles were successfully recreated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function linkSelectedProductsAction()
    {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
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
            $this->getRepository()->refreshProductStates();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function acceptSelectedProductsAction() {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
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
            $this->getRepository()->refreshProductStates();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function activateSelectedProductsAction()
    {
        try {
            list($value, $products) = $this->setStatePropertyOnSelected();
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
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
            $this->getRepository()->refreshProductStates();
            $this->view->assign(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function checkRegularExpressionsAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $regularExpressions = $services->get(RegularExpressions::class);
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
            $services = MxcDropshipInnocigs::getServices();
            $nameMappingConsistency = $services->get(NameMappingConsistency::class);
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

    public function checkVariantMappingConsistencyAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $variantMappingConsistency = $services->get(VariantMappingConsistency::class);
            $issueCount = $variantMappingConsistency->check();
            if ($issueCount > 0) {
                $issue = 'issue';
                if ($issueCount > 1) $issue .= 's';
                $this->view->assign(['success' => false, 'message' => 'Found ' . $issueCount . ' variant mapping ' . $issue  . '. See log for details.']);
            } else {
                $this->view->assign(['success' => true, 'message' => 'No variant mapping issues found.']);
            }

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function createAllAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
            $products = $this->getRepository()->findAll();
            foreach ($products as $product) {
                $productMapper->createArticle($product);
            }
            $message = 'Products were successfully created.';
            $this->view->assign(['success' => true, 'message' => $message]);
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getRepository()->refreshProductStates();
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function deleteAllAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getLinkedProducts();
            $productMapper->deleteArticles($products);
            $message = 'Products were successfully deleted.';
            $this->view->assign(['success' => true, 'message' => $message]);
            /** @noinspection PhpUndefinedMethodInspection */
            $this->getRepository()->refreshProductStates();
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapAction()
    {
        try {
            /** @var ImportMapper $client */
            $services = MxcDropshipInnocigs::getServices();
            $propertyMapper = $services->get(PropertyMapper::class);
            $categoryMapper = $services->get(CategoryMapper::class);
            $productMapper = $services->get(ProductMapper::class);
            $repository = $this->getModelManager()->getRepository(Product::class);

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $repository->getAllIndexed();
            $propertyMapper->mapProperties($products, true);
            $categoryMapper->buildCategoryTree();

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
            $services = MxcDropshipInnocigs::getServices();
            $propertyMapper = $services->get(PropertyMapper::class);
            $productMapper = $services->get(ProductMapper::class);
            $modelManager = $this->getManager();
            $repository = $modelManager->getRepository(Product::class);

            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);

            $products = $repository->getProductsByIds($ids);
            $propertyMapper->mapProperties($products, true);
            $modelManager->flush();

            $products = $repository->getLinkedProductsFromProductIds($ids);
            $productMapper->updateArticles($products, false);
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Product properties were successfully remapped.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateStockInfoAction()
    {
        try {
            $updateCronJob = new UpdateStockCronJob();
            $updateCronJob->onUpdateStockCronJob(null);

            $this->view->assign([ 'success' => true, 'message' => 'Successfully updated stock info from InnoCigs.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }

    }

    protected function getSelectedProducts(Enlight_Controller_Request_RequestHttp $request) {
        $params = $request->getParams();
        $repository = $this->getManager()->getRepository(Product::class);
        if (empty($params['ids'])) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $repository->getAllIndexed();
        }
        $ids = json_decode($params['ids'], true);
        return $repository->getProductsByIds($ids);
    }

    public function remapDescriptionsAction() {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $mapper = $services->get(DescriptionMapper::class);

            $products = $this->getSelectedProducts($this->request);

            /** @var Product $product */
            foreach ($products as $product) {
                $mapper->remap($product);
                $article = $product->getArticle();
                /** @var Article $article */
                if ($article !== null) {
                    $description = $product->getDescription();
                    $article->setDescriptionLong($description);
                }
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Descriptions successfully remapped.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function pullShopwareDescriptionsAction()
    {
        try {
            /** @var ImportMapper $client */
            $services = MxcDropshipInnocigs::getServices();
            $descriptions = $services->get(DescriptionPullback::class);
            $repository = $this->getManager()->getRepository(Product::class);

            $products = $this->getSelectedProducts($this->request);
            $descriptions->pullDescriptions($products);
            $repository->exportMappedProperties();

            $this->view->assign([ 'success' => true, 'message' => 'Successfully pulled Shopware descriptions.']);
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
            $articleResource = new ArticleResource();
            $articleResource->setManager($modelManager);
            /** @var Article $article */
            foreach ($articles as $article) {
                $articleResource->delete($article->getId());
            }

            $xmlFile = $testDir . 'TESTErstimport.xml';
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile, true));

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $services = MxcDropshipInnocigs::getServices();
            $productMapper = $services->get(ProductMapper::class);
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

            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile));
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
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile));
            $this->view->assign([ 'success' => true, 'message' => 'Variants successfully updated.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport4Action()
    {
        try {
            $xml = '<?xml version="1.0" encoding="utf-8"?><INNOCIGS_API_RESPONSE><PRODUCTS></PRODUCTS></INNOCIGS_API_RESPONSE>';
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromXml($xml));
            $this->view->assign([ 'success' => true, 'message' => 'Empty list successfully imported.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function testImport5Action()
    {
        try {
            $testDir = __DIR__ . '/../../Test/';
            $modelManager = $this->getManager();
            $modelManager->createQuery('DELETE MxcDropshipInnocigs\Models\Model ir')->execute();
            $articles = $modelManager->getRepository(Article::class)->findAll();
            $articleResource = new ArticleResource();
            $articleResource->setManager($modelManager);
            /** @var Article $article */
            foreach ($articles as $article) {
                $articleResource->delete($article->getId());
            }

            $xmlFile = $testDir . 'TESTHugeImport.xml';
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFileSequential($xmlFile, true));

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $productMapper = $services->get(ProductMapper::class);
            $productMapper->updateArticles($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function testImport6Action()
    {
        try {
            $testDir = __DIR__ . '/../../Test/';
            $modelManager = $this->getManager();
            $modelManager->createQuery('DELETE MxcDropshipInnocigs\Models\Model ir')->execute();
            $articles = $modelManager->getRepository(Article::class)->findAll();
            $articleResource = new ArticleResource();
            $articleResource->setManager($modelManager);
            /** @var Article $article */
            foreach ($articles as $article) {
                $articleResource->delete($article->getId());
            }

            $xmlFile = $testDir . 'TESTHugeImport.xml';
            $services = MxcDropshipInnocigs::getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile, true));

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $productMapper = $services->get(ProductMapper::class);
            $productMapper->updateArticles($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
   }

    public function checkInactiveVariantsAction()
    {
        try {
            $products = $this->getManager()->getRepository(Product::class)->findAll();
            /** @var Product $product */
            $log = MxcDropshipInnocigs::getServices()->get('logger');
            foreach ($products as $product) {
                $variants = $product->getVariants();
                /** @var Variant $variant */
                foreach ($variants as $variant) {
                    if ($variant->isValid() && $variant->getDetail() === null) {
                        $log->debug('Product with inactive variant: '. $product->getName());
                    }
                }
            }
            $this->view->assign([ 'success' => true, 'message' => 'Development 2 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapProductSeoInformationAction() {
        try {
            $products = $this->getManager()->getRepository(Product::class)->findall();
            /** @var Product $product */
            foreach ($products as $product) {
                /** @var Article $article */
                $article = $product->getArticle();
                if ($article === null) continue;
                $article->setDescription($product->getSeoDescription());
                $article->setMetaTitle($product->getSeoTitle());
                $article->setKeywords($product->getSeoKeywords());
                $seoUrl = $product->getSeoUrl();
                if (! empty($seoUrl)) {
                    ArticleTool::setArticleAttribute($article, 'attr4', $seoUrl);
                }
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Product SEO information was successfully refreshed.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function pullAssociatedProductsAction()
    {
        try {
            $articles = $this->getManager()->getRepository(Article::class)->findAll();
            $repo = $this->getManager()->getRepository(Product::class);
            /** @var Article $article */
            $relations = [];
            foreach ($articles as $article) {
                /** @var Product $product */
                $product = $repo->getProduct($article);
                if ($product === null) continue;
                $number = $product->getIcNumber();
                $related = $article->getRelated();
                foreach ($related as $relatedArticle) {
                    $product = $repo->getProduct($relatedArticle);
                    $relations['related'][$number][] = $product->getIcNumber();
                }
                $similar = $article->getSimilar();
                foreach ($similar as $similarArticle) {
                    $product = $repo->getProduct($similarArticle);
                    $relations['similar'][$number][] = $product->getIcNumber();
                }
            }
            $fileNameShort = 'Config/CrossSelling.config.php';
            $fileName = __DIR__ . '/../../' . $fileNameShort;
            Factory::toFile($fileName, $relations);
            $this->view->assign([ 'success' => true, 'message' => 'Cross selling products saved to ' . $fileNameShort . '.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function pushAssociatedProductsAction()
    {
        try {
            $repository = $this->getManager()->getRepository(Product::class);
            $fileNameShort = 'Config/CrossSelling.config.php';
            /** @noinspection PhpIncludeInspection */
            $relations = include __DIR__ . '/../../' . $fileNameShort;
            $similar = $relations['similar'];
            foreach ($similar as $number => $similarList) {
                $product = $repository->findOneBy(['icNumber' => $number]);
                if (! $product) continue;
                /** @var Article $article */
                $article = $product->getArticle();
                if (! $article) continue;
                $similarArticles = $article->getSimilar();
                $similarArticles->clear();
                foreach ($similarList as $similarArticleNumber) {
                    $product = $repository->findOneBy(['icNumber' => $similarArticleNumber]);
                    if (! $product) continue;
                    $article = $product->getArticle();
                    if (! $article) continue;
                    $similarArticles->add($article);
                }
            }

            $related = $relations['related'];
            foreach ($related as $number => $relatedList) {
                $product = $repository->findOneBy(['icNumber' => $number]);
                if (! $product) continue;
                /** @var Article $article */
                $article = $product->getArticle();
                if (! $article) continue;
                $relatedArticles = $article->getRelated();
                $relatedArticles->clear();
                foreach ($relatedList as $relatedArticleNumber) {
                    $product = $repository->findOneBy(['icNumber' => $relatedArticleNumber]);
                    if (! $product) continue;
                    $article = $product->getArticle();
                    if (! $article) continue;
                    $relatedArticles->add($article);
                }
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Cross selling products restored from ' . $fileNameShort . '.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapCategorySeoInformationAction() {
        try {
            // create category seo information for InnoCigs products
            $services = MxcDropshipInnocigs::getServices();
            $seoCategoryMapper = $services->get(CategoryMapper::class);
            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $model = new Model();
            foreach ($products as $product) {
                $seoCategoryMapper->map($model, $product, true);
            }

            // update Shopware articles
            $seoCategoryMapper->report();
            $categoryMapper = $services->get(ShopwareCategoryMapper::class);
            $categoryMapper->rebuildCategorySeoInformation();
            $this->getManager()->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Category SEO information successfully refreshed.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function setReferencePricesAction()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $priceMapper = $services->get(PriceMapper::class);
            $products = $this->getManager()->getRepository(Product::class)->findAll();
            foreach ($products as $product) {
                $priceMapper->setReferencePrice($product);
            }
            $this->getManager()->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Successfully set reference prices.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function checkSupplierLogoAction()
    {
        try {
            $missingLogos = [];
            $suppliers = $this->getManager()->getRepository(\Shopware\Models\Article\Supplier::class)->findBy(array('image' => ''), array('name' => 'ASC'));
            foreach ($suppliers as $supplier) {
                array_push($missingLogos, $supplier->getName());
            }
            $this->view->assign([ 'success' => true, 'message' => 'The following Logos are missing:', 'value' => $missingLogos ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function remapSupplierSeoInformationAction()
    {
        try {
            $suppliers = $this->getManager()->getRepository(Supplier::class)->findAll();
            /** @var Supplier $supplier */
            $title = 'E-Zigaretten und E-Liquids: Unsere Produkte von %s';
            $description = 'Produkte für Dampfer von %s ✓ vapee.de bietet ein breites Sortiment von E-Zigaretten und E-Liquids zu fairen Preisen ► Besuchen Sie uns!';

            foreach ($suppliers as $supplier) {
                $name = $supplier->getName();
                $metaTitle = sprintf($title, $name);
                $metaDescription = sprintf($description, $name);
                SupplierTool::setSupplierMetaInfo($supplier, $metaTitle, $metaDescription, $name);
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Supplier meta information successfully applied.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev1Action()
    {
        // find mismatches between purchase prices reported by model and variant and adjust variant accordingly
        try {
            $log = MxcDropshipInnocigs::getServices()->get('logger');
            $manager = $this->getManager();
            $models = $manager->getRepository(Model::class)->getAllIndexed();
            $variants = $manager->getRepository(Variant::class)->getAllIndexed();
            /**
             * @var string $number
             * @var  Model $model
             */
            foreach ($models as $number => $model) {
                /** @var Variant $variant */
                $variant = $variants[$number];
                $mPrice = str_replace(',', '.', $model->getPurchasePrice());
                $vPrice = $variant->getPurchasePrice();
                if ($mPrice !== $vPrice) {
                    $log->debug('Purchase price mismatch: ' . $variant->getName() . ': Model: '. $mPrice . ', variant: '. $vPrice);
                    $variant->setPurchasePrice($mPrice);
                }
            }
            $manager->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev2Action()
    {
        try {
            $log = MxcDropshipInnocigs::getServices()->get('logger');
            $articles = $this->getManager()->getRepository(Article::class)->findAll();
            $repository = $this->getManager()->getRepository(Product::class);
            foreach ($articles as $article) {
                $product = $repository->getProduct($article);
                if ($product === null) {
                    $log->debug('Article without product (1): ' . $article->getName());
                }
            }
            $articles = $repository->getArticlesWithoutProduct();
            foreach ($articles as $article) {
                $log->debug('Article without product (2): ' . $article->getName());
            }

            $this->view->assign([ 'success' => true, 'message' => 'Development 2 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev3Action()
    {
        try {
            $services = MxcDropshipInnocigs::getServices();
            $log = $services->get('logger');
            $seoMapper = $services->get(ProductSeoMapper::class);
            $repository = $this->getManager()->getRepository(Product::class);
            $products = $repository->getAllIndexed();
            $model = new Model();
            foreach ($products as $product) {
                $seoMapper->map($model, $product);
            }
            $this->getManager()->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Development 3 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev4Action()
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $variants = $this->getManager()->getRepository(Variant::class)->getAllIndexed();
            $models = $this->getManager()->getRepository(Model::class)->getAllIndexed();
            $services = MxcDropshipInnocigs::getServices();
            $log = $services->get('logger');
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                $icNumber = $variant->getIcNumber();
                /** @var Model $model */
                $model = $models[$icNumber];
                if ($model === null || strpos($model->getOptions(), '1er Packung') === false) continue;
                if (! $variant->isActive() && $variant->isValid()) {
                    $log->debug('Inactive valid variant: ' . $model->getName());
                }
            }
            $this->view->assign([ 'success' => true, 'message' => 'Inactive variant check done.' ]);
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
            $this->view->assign([ 'success' => true, 'message' => 'Development slot #5 is currently free.']);
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
        $services = MxcDropshipInnocigs::getServices();
        $productMapper = $services->get(ProductMapper::class);

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
        $services = MxcDropshipInnocigs::getServices();
        $productMapper = $services->get(ProductMapper::class);
        if (! $fromListView) {
            $productMapper->updateArticleStructure($product);
            $this->getManager()->flush();
        }

        $this->getRepository()->exportMappedProperties();

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

    protected function handleException(Throwable $e, bool $rethrow = false) {
        MxcDropshipInnocigs::getServices()->get('logger')->except($e, true, $rethrow);
        $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
    }

    public function checkVariantsWithoutOptionsAction()
    {
        try {
            $variants = $this->getManager()->getRepository(Variant::class)->findAll();
            $log = MxcDropshipInnocigs::getServices()->get('logger');
            $issues = [];
            /** @var Variant $variant */
            foreach ($variants as $variant) {
                if ($variant->getOptions() === null) {
                    $issues[] = $variant->getIcNumber();
                }
            }
            $c = count($issues);
            if (count($issues) > 0 ) {
                $msg = 'Found ' . $c . ' variants without options. See log for details.';
                $log->debug('Variants without options:');
                $log->debug(var_export($issues, true));

            } else {
                $msg = 'No variants without options were found.';
            }
            $this->view->assign([ 'success' => true, 'message' => $msg]);
            // Do something with the ids
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
}
