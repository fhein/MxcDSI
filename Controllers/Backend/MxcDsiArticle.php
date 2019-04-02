<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Mapping\Check\NameMappingConsistency;
use MxcDropshipInnocigs\Mapping\Check\RegularExpressions;
use MxcDropshipInnocigs\Mapping\Import\PropertyMapper;
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
            $articles = $modelManager->getRepository(Article::class)->getBrokenLinks();
            /** @var Article $article */
            foreach ($articles as $article) {
                if ($article->getArticle()) continue;
                $article->setActive(false);
                $article->setLinked(false);
            }
            $modelManager->flush();
            $this->view->assign([ 'success' => true, 'message' => 'Article links were successfully updated.']);
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

    public function setStateSelectedAction()
    {
        $this->log->enter();
        try {
            $params = $this->request->getParams();
            $field = $params['field'];
            $value = $params['value'] === 'true';
            $ids = json_decode($params['ids'], true);

            $services = $this->getServices();
            $modelManager = $services->get('modelManager');
            $icArticles = $modelManager->getRepository(Article::class)->getArticlesByIds($ids);

            $articleMapper = $services->get(ArticleMapper::class);

            if (in_array($field, ['accepted', 'active', 'linked'])) {
                $setter = 'set' . ucfirst($field);
                /** @var Article $icArticle */
                foreach ($icArticles as $icArticle) {
                    $icArticle->$setter($value);
                }
                $articleMapper->processStateChangesArticleList($icArticles);
                $this->view->assign(['success' => true, 'message' => 'Articles were successfully updated.']);
            } else {
                $this->view->assign([ 'success' => false, 'message' => 'Unknown state property: ' . $field]);
                $this->log->leave();
                return;
            }
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
        $this->log->leave();
    }

    public function checkRegularExpressionsAction()
    {
        $this->log->enter();
        try {
            $regularExpressions = $this->getServices()->get(RegularExpressions::class);
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
            $propertyMapper = $this->services->get(PropertyMapper::class);
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
        $articleMapper = $this->services->get(ArticleMapper::class);
        foreach ($this->articleStates as $state => $values) {
            $newValue = $values['new'];
            if ($values['current'] === $newValue) continue;
            $articleMapper->processStateChangesArticle($article, true);
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

    public function dev1Action()
    {
        $this->log->enter();
        try {
            $this->view->assign([ 'success' => true, 'message' => 'Development 1 slot is currrently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
            $this->view->assign([ 'success' => false, 'message' => $e->getMessage() ]);
        }
    }

    public function dev2Action()
    {
        $this->log->enter();
        try {
            $dql = 'SELECT a.icNumber, a.name, a.dosage FROM MxcDropshipInnocigs\Models\Article a INDEX BY a.icNumber '
                . 'WHERE a.type = \'AROMA\' ORDER BY a.name';
            $articles = $this->getManager()->createQuery($dql)->getResult();
            $fn = __DIR__ . '/../../Config/dosage.config.php';
            Factory::toFile($fn, $articles);

            $this->view->assign([ 'success' => true, 'message' => 'Development 2 slot is currrently free.' ]);
        } catch (Throwable $e) {
            $this->log->except($e, true, false);
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
            $this->log->except($e, true, false);
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
