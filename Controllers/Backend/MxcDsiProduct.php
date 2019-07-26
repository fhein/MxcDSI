<?php /** @noinspection PhpUnhandledExceptionInspection */

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Excel\ExcelExport;
use MxcDropshipInnocigs\Excel\ExcelProductImport;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Check\VariantMappingConsistency;
use MxcDropshipInnocigs\Mapping\Import\CategoryMapper;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ImportPriceMapper;
use MxcDropshipInnocigs\Mapping\ProductMapper;
use MxcDropshipInnocigs\Mapping\Pullback\DescriptionPullback;
use MxcDropshipInnocigs\Mapping\Shopware\CategoryMapper as ShopwareCategoryMapper;
use MxcDropshipInnocigs\Mapping\Shopware\ImageMapper;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Models\Product;
use MxcDropshipInnocigs\Models\ProductRepository;
use MxcDropshipInnocigs\Toolbox\Shopware\CategoryTool;
use MxcDropshipInnocigs\Toolbox\Shopware\DatabaseTool;
use Shopware\Components\Api\Resource\Article as ArticleResource;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Article\Article;

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
            $client = $this->getServices()->get(ImportClient::class);
            $client->setLoadExtendedList(true);
            $mapper = $this->getServices()->get(ImportMapper::class);
            $mapper->import($client->import());
            $this->view->assign(['success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function importSequentialAction()
    {
        try {
            $client = $this->getServices()->get(ImportClient::class);
            $client->setLoadExtendedList(true);
            $mapper = $this->getServices()->get(ImportMapper::class);
            $mapper->import($client->importSequential());
            $this->view->assign(['success' => true, 'message' => 'Items were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updatePricesAction()
    {
        try {
            $client = $this->getServices()->get(ImportClient::class);
            $client->setLoadExtendedList(false);
            $mapper = $this->getServices()->get(ImportPriceMapper::class);
            $mapper->import($client->import());
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

    public function createRelatedSelectedAction()
    {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $products = $this->getRepository()->getProductsByIds($ids);
            $productMapper = $this->getServices()->get(ProductMapper::class);
            $productMapper->createRelatedArticles($products);
            $this->view->assign([ 'success' => true, 'message' => 'Related articles were successfully created.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function createSimilarSelectedAction()
    {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $products = $this->getRepository()->getProductsByIds($ids);
            $productMapper = $this->getServices()->get(ProductMapper::class);
            $productMapper->createSimilarArticles($products);
            $this->view->assign([ 'success' => true, 'message' => 'Similar articles were successfully created.']);
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

            $productMapper = $this->getServices()->get(ProductMapper::class);
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
            $imageMapper = $this->getServices()->get(ImageMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getLinkedProducts();
            foreach ($products as $product) {
                $imageMapper->setArticleImages($product);
            }
            $modelManager->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Images were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateImagesSelectedAction()
    {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getLinkedProductsFromProductIds($ids);
            $imageMapper = $this->getServices()->get(ImageMapper::class);
            foreach ($products as $product) {
                $imageMapper->setArticleImages($product);
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Images were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function updateCategoriesAction()
    {
        try {
            $modelManager = $this->getManager();
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getLinkedProducts();

            $categoryMapper = $this->getServices()->get(ShopwareCategoryMapper::class);
            foreach ($products as $product) {
                $categoryMapper->map($product);
            }
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Categories were successfully updated.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function removeEmptyCategoriesAction()
    {
        try {
            $count = $this->getServices()->get(CategoryTool::class)->removeEmptyCategories();
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
            $categoryMapper = $this->getServices()->get(CategoryMapper::class);
            $products = $this->getRepository()->findAll();
            $model = new Model();
            foreach ($products as $product) {
                $categoryMapper->map($model, $product, true);
            }
            $this->getManager()->flush();
            $categoryMapper->buildCategoryTree();
            $this->view->assign([ 'success' => true, 'message' => 'Category tree configuration successfully rebuilt.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }

    }

    public function updateCategoriesSelectedAction()
    {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            /** @noinspection PhpUndefinedMethodInspection */
            $products = $this->getRepository()->getLinkedProductsFromProductIds($ids);
            $categoryMapper = $this->getServices()->get(ShopwareCategoryMapper::class);
            foreach ($products as $product) {
                $categoryMapper->map($product);
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Categories were successfully updated.']);
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
            $this->get('plugins')->Controller()->ViewRenderer()->setNoRender();
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
            $this->handleException($e);
        }
    }

    public function excelImportAction()
    {
        // Try to get the transferred file
        try {
            $file = $_FILES['file'];

            if ($file === null) $this->getLog()->debug('file is null');
            $fileName = $file['name'];
            $tmpName = $_FILES['file']['tmp_name'];
            $fileNamePos= strrpos ($tmpName, '/');
            $tmpPath= substr($tmpName, 0, $fileNamePos);
            $newFilePath = $tmpPath.'/' . $fileName; //'/../Config/' . $file['originalName'];
            move_uploaded_file($tmpName, $newFilePath);

            $excel = $this->getServices()->get(ExcelProductImport::class);
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

            if ($file === null) $this->getLog()->debug('file is null');
            $fileName = $file['name'];
            $tmpName = $_FILES['file']['tmp_name'];
            $fileNamePos= strrpos ($tmpName, '/');
            $tmpPath= substr($tmpName, 0, $fileNamePos);
            $newFilePath = $tmpPath.'/' . $fileName; //'/../Config/' . $file['originalName'];
            move_uploaded_file($tmpName, $newFilePath);


            $excel = $this->getServices()->get(ExcelProductImport::class);
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

    public function relinkSelectedProductsAction() {
        try {
            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);
            $repository = $this->getRepository();
            $repository->setStateByIds('linked', false, $ids);
            $products = $this->getRepository()->getProductsByIds($ids);

            $productMapper = $this->getServices()->get(ProductMapper::class);
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
            $this->getRepository()->refreshProductStates();
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
            $this->getRepository()->refreshProductStates();
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

    public function checkVariantMappingConsistencyAction()
    {
        try {
            $variantMappingConsistency = $this->getServices()->get(VariantMappingConsistency::class);
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
            $productMapper = $this->getServices()->get(ProductMapper::class);
            $products = $this->getRepository()->findAll();
            $productMapper->controllerUpdateArticles($products, true);
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
            $productMapper = $this->getServices()->get(ProductMapper::class);
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
            $services = $this->getServices();
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
            $services = $this->getServices();
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

    public function pullShopwareDescriptionsAction()
    {
        try {
            /** @var ImportMapper $client */
            $services = $this->getServices();
            $descriptions = $services->get(DescriptionPullback::class);
            $repository = $this->getManager()->getRepository(Product::class);

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $repository->getAllIndexed();
            $descriptions->pullDescriptions($products);
            $repository->exportMappedProperties();

            $this->view->assign([ 'success' => true, 'message' => 'Successfully pulled Shopware descriptions.']);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function pullShopwareDescriptionsSelectedAction()
    {
        try {
            /** @var ImportMapper $client */
            $services = $this->getServices();
            $descriptions = $services->get(DescriptionPullback::class);
            $repository = $this->getModelManager()->getRepository(Product::class);

            $params = $this->request->getParams();
            $ids = json_decode($params['ids'], true);

            /** @noinspection PhpUndefinedMethodInspection */
            $products = $repository->getProductsByIds($ids);
            $descriptions->pullDescriptions($products);

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
            $services = $this->getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile, true));

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

            $services = $this->getServices();
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
            $services = $this->getServices();
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
            $services = $this->getServices();
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
            $services = $this->getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFileSequential($xmlFile, true));

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $productMapper = $this->services->get(ProductMapper::class);
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
            $services = $this->getServices();
            $client = $services->get(ImportClient::class);
            $mapper = $services->get(ImportMapper::class);
            $mapper->import($client->importFromFile($xmlFile, true));

            $products = $this->getManager()->getRepository(Product::class)->findAll();
            $productMapper = $this->services->get(ProductMapper::class);
            $productMapper->updateArticles($products, true);

            $this->view->assign([ 'success' => true, 'message' => 'Erstimport successful.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function dev1Action()
    {
        try {
            $repair = $this->getServices()->get(DatabaseTool::class);
            $repair->removeOrphanedDetails();
            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }


    public function dev2Action()
    {
        try {
            $modelManager = $this->getManager();
            $dql = 'SELECT c FROM Shopware\Models\Category\Category c WHERE c.parentId IS NOT null AND c.blog = 0 ';
            $query = $modelManager->createQuery($dql);
            $categories = $query->getResult();
            /** @var \Shopware\Models\Category\Category $category */
            foreach ($categories as $category) {
                $category->setHideFilter(true);
                $category->setHideSortings(true);
            }
            $modelManager->flush();

            $this->view->assign([ 'success' => true, 'message' => 'Development 2 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function dev3Action()
    {
        try {
            // Replace all commata with dots in price fields to ensure correct casting to float

            /** @noinspection PhpUndefinedMethodInspection */
            $variants = $this->getManager()->getRepository(\MxcDropshipInnocigs\Models\Variant::class)->getAllIndexed();
            /** @var \MxcDropshipInnocigs\Models\Variant $variant */
            foreach ($variants as $variant) {
                $price = str_replace(',', '.', $variant->getRecommendedRetailPrice());
                $variant->setRecommendedRetailPrice($price);
                $price = str_replace(',', '.', $variant->getPurchasePrice());
                $variant->setPurchasePrice($price);

                $price = str_replace(',', '.', $variant->getRecommendedRetailPriceOld());
                $variant->setRecommendedRetailPriceOld($price);
                $price = str_replace(',', '.', $variant->getPurchasePriceOld());
                $variant->setPurchasePriceOld($price);
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
            $this->getManager()->getRepository(Product::class)->exportMappedPropertiesNew();
            $products = $this->getManager()->getRepository(Product::class)->findAll();
            /** @var Product $product */
            foreach ($products as $product) {
                $name = $product->getName();
                $name = str_replace('Clearomizer (Set)', 'Verdampfer', $name);
                $product->setName($name);
                $article = $product->getArticle();
                /** @var Article $article */
                if ($article !== null) {
                    $article->setName($name);
                }
            }
            $this->getManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Clearomizer (Set) successfully replaced.' ]);
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
        $services = $this->getServices();
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
}
