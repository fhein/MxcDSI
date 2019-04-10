<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Csv\ArticlePrices;
use MxcDropshipInnocigs\Mapping\Import\ImportPropertyMapper;
use MxcDropshipInnocigs\Mapping\ImportMapper;
use MxcDropshipInnocigs\Mapping\ShopwareMapper;
use MxcDropshipInnocigs\Models\Article;

class Shopware_Controllers_Backend_MxcDsiArticle extends BackendApplicationController
{
    protected $model = Article::class;
    protected $alias = 'innocigs_article';

    protected $articleStateProperties = ['active', 'accepted', 'linked'];

    /** @var array */
    protected $articleStates;

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
            if ($modelManager->getRepository(Article::class)->refreshLinks()) {
                $this->view->assign([ 'success' => true, 'message' => 'Article links were successfully updated.']);
            } else {
                $this->view->assign([ 'success' => false, 'message' => 'Failed to update article links.']);
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
            $modelManager->getRepository(Article::class)->exportMappedProperties();
            $this->view->assign([ 'success' => true, 'message' => 'Article configuration was successfully exported.']);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function exportPricesAction()
    {
        $this->log->enter();
        try {
            $prices = $this->services->get(ArticlePrices::class);
            $prices->export();
            $this->view->assign([ 'success' => true, 'message' => 'Prices successfully exported to Config/article.prices.xlsx.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function importPricesAction()
    {
        $this->log->enter();
        try {
            $prices = $this->services->get(ArticlePrices::class);
            $prices->import();
            $this->view->assign([ 'success' => true, 'message' => 'Prices successfully imported from Config/article.prices.xlsx.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function setStateSelectedAction()
    {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            $field = $params['field'];
            $value = $params['value'] === 'true';
            $ids = json_decode($params['ids'], true);

            $modelManager = $this->getModelManager();
            $icArticles = $modelManager->getRepository(Article::class)->getArticlesByIds($ids);

            $articleMapper = $this->services->get(ShopwareMapper::class);

            if (in_array($field, ['accepted', 'active', 'linked'])) {
                $setter = 'set' . ucfirst($field);
                /** @var Article $icArticle */
                foreach ($icArticles as $icArticle) {
                    $icArticle->$setter($value);
                }
            } else {
                $this->view->assign([ 'success' => false, 'message' => 'Unknown state property: ' . $field]);
                $this->log->leave();
                return;
            }
            switch ($field) {
                case 'accepted':
                    $articleMapper->setArticleAcceptedState($icArticles, $value);
                    break;
                case 'linked':
                case 'active':
                $articleMapper->processStateChangesArticleList($icArticles, true);
                break;
            }
            $this->view->assign(['success' => true, 'message' => 'Articles were successfully updated.']);
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
            $propertyMapper = $this->services->get(ImportPropertyMapper::class);
            /** @noinspection PhpUndefinedMethodInspection */
            $articles = $this->getModelManager()->getRepository(Article::class)->getAllIndexed();
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Article properties were successfully remapped.']);
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
            $articles = $this->getModelManager()->getRepository(Article::class)->getArticlesByIds($ids);
            $propertyMapper = $this->services->get(ImportPropertyMapper::class);
            $propertyMapper->mapProperties($articles);
            $this->getModelManager()->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Article properties were successfully remapped.']);
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

    protected function processStateUpdates(Article $article)
    {
        $articleMapper = $this->services->get(ShopwareMapper::class);
        foreach ($this->articleStates as $state => $values) {
            $newValue = $values['new'];
            if ($values['current'] === $newValue) continue;
            switch ($state) {
                case 'accepted':
                    $articleMapper->setArticleAcceptedState([$article], $article->isAccepted());
                    break;
                default:
                    $articleMapper->processStateChangesArticle($article, true);
                    break;
            }
            $getState = 'is' . ucFirst($state);
            if ($article->$getState() === $newValue) continue;
            $message = sprintf("Failed to set article's %s state to %s.", $state, var_export($newValue, true));
            return ['success' => false, 'message' => $message];
        }
        return true;
    }

    public function getCurrentArticleStates(Article $article) {
        foreach ($this->articleStateProperties as $property) {
            $getState = 'is' . ucfirst($property);
            $this->articleStates[$property]['current'] = $article->$getState();
        }
    }

    public function getNewArticleStates(array $data) {
        foreach ($this->articleStateProperties as $property) {
            $this->articleStates[$property]['new'] = $data[$property];
        }
    }

    public function save($data) {
        $this->log->enter();
        $this->log->leave();

        if (! empty($data['id'])) {
            // this is a request to update an existing article
            $article = $this->getRepository()->find($data['id']);
        } else {
            // this is a request to create a new article (not supported via our UI)
            $article = new $this->model();
            $this->getManager()->persist($article);
        }
        /** @var Article $article */
        $this->articleStates = [];
        $this->getCurrentArticleStates($article);
        $this->getNewArticleStates($data);

        // Variant data is empty only if the request comes from the list view (not the detail view)
        // We prevent storing an article with empty variant list by unsetting empty variant data.
        if (isset($data['variants']) && empty($data['variants'])) {
            unset($data['variants']);
        }

        // hydrate (new or existing) article from UI data
        $data = $this->resolveExtJsData($data);
        unset($data['relatedArticles']);
        unset($data['similarArticles']);
        $article->fromArray($data);

        $result = $this->processStateUpdates($article);
        if ($result !== true) return $result;

        // Our customization ends here.
        // The rest below is default Shopware behaviour copied from parent implementation
        $violations = $this->getManager()->validate($article);
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

        $detail = $this->getDetail($article->getId());

        return ['success' => true, 'data' => $detail['data']];
    }

    public function testImport1Action()
    {
        $this->log->enter();
        try {
            $testDir = __DIR__ . '/../../Test/';
            $modelManager = $this->getManager();
            $modelManager->createQuery('DELETE MxcDropshipInnocigs\Models\Model m')->execute();
            $swArticles = $modelManager->getRepository(\Shopware\Models\Article\Article::class)->findAll();
            $articleResource = new \Shopware\Components\Api\Resource\Article();
            $articleResource->setManager($modelManager);
            foreach ($swArticles as $swArticle) {
                $articleResource->delete($swArticle->getId());
            }

            $xml = $testDir . 'TESTErstimport.xml';
            $this->services->get(ImportClient::class)->import($xml, true);

            $icArticles = $this->getManager()->getRepository(Article::class)->findAll();
            $articleMapper = $this->services->get(ShopwareMapper::class);
            $articleMapper->processStateChangesArticleList($icArticles, true);

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
            $xml = $testDir . 'TESTUpdateFeldwerte.xml';
            $this->services->get(ImportClient::class)->import($xml);;
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
            $xml = $testDir . 'TESTUpdateVarianten.xml';
            $this->services->get(ImportClient::class)->import($xml);;
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
            $testDir = __DIR__ . '/../../Test/';
            $xml = $testDir . 'TESTEmpty.xml';
            $this->services->get(ImportClient::class)->import($xml);;
            $this->view->assign([ 'success' => true, 'message' => 'Empty list successfully imported.' ]);
//            $this->view->assign([ 'success' => true, 'message' => 'Development 4 slot is currently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, true);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }
    public function dev1Action()
    {
        $this->log->enter();
        try {
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
            $this->view->assign([ 'success' => true, 'message' => 'Development 2 slot is currently free.' ]);
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

}
