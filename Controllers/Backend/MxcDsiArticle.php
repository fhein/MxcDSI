<?php

use Mxc\Shopware\Plugin\Controller\BackendApplicationController;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipInnocigs\Import\ImportMapper;
use MxcDropshipInnocigs\Import\PropertyMapper;
use MxcDropshipInnocigs\Mapping\ArticleMapper;
use MxcDropshipInnocigs\Models\Article;

class Shopware_Controllers_Backend_MxcDsiArticle extends BackendApplicationController
{
    protected $model = Article::class;
    protected $alias = 'innocigs_article';

    public function indexAction() {
        $this->log->enter();
        try {
            $client = $this->services->get(ImportClient::class);
            if ($client === null) {
                $this->log->err('client is null.');
            }
            $client->import();
            parent::indexAction();
        } catch (Throwable $e) {
            $this->log->except($e);
            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
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
            $this->log->except($e);
            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function importAction()
    {
        $this->log->enter();
        try {
            $client = $this->services->get(ImportClient::class);
            $client->import();
        } catch (Throwable $e) {
            $this->log->except($e);
            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    public function remapAction()
    {
        $this->log->enter();
        try {
            /** @var ImportMapper $client */
            $mapper = $this->services->get(PropertyMapper::class);
            $mapper->reapplyPropertyMapping();
        } catch (Throwable $e) {
            $this->log->except($e);
            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        $this->log->leave();
    }

    protected function getAdditionalDetailData(array $data) {
        $data['variants'] = [];
        return $data;
    }

    public function save($data) {
        /** @var Article $article */
        if (! empty($data['id'])) {
            // this is a request to update an existing article
            $article = $this->getRepository()->find($data['id']);
            // currently stored $active state
            $sActive = $article->isActive();
        } else {
            // this is a request to create a new article (not supported via our UI)
            $article = new $this->model();
            $this->getManager()->persist($article);
            // default $active state
            $sActive = false;
        }
        // Variant data is empty only if the request comes from the list view (not the detail view)
        // We prevent storing an article with empty variant list by unsetting empty variant data.
        if (isset($data['variants']) && empty($data['variants'])) {
            unset($data['variants']);
        }

        // hydrate (new or existing) article from UI data
        $data = $this->resolveExtJsData($data);
        $article->fromArray($data);

        // updated $active state
        $uActive = $article->isActive();

        $articleMapper = $this->services->get(ArticleMapper::class);
        if ($uActive !== $sActive) {
            // User request to change active state of article
            if (! $articleMapper->handleActiveStateChange($article)) {
                return [
                    'success' => false,
                    'message' => 'Shopware article not created because it failed to validate.',
                ];
            }
        }
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
}
